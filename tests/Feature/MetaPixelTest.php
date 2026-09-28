<?php

namespace Tests\Feature;

use App\Models\MarketingSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMarketingSetting;
use App\Models\WholesaleEnquiry;
use App\Support\MetaPixel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MetaPixelTest extends TestCase
{
    private const PLATFORM = '111111111111111';
    private const VENDOR_A = '222222222222222';
    private const VENDOR_B = '333333333333333';

    private Product $adminProduct;
    private Product $productA;
    private Product $productB;
    private Vendor $vendorA;
    private Vendor $vendorB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->vendorA = $this->vendor('a');
        $this->vendorB = $this->vendor('b');
        $this->adminProduct = $this->product('admin-cumin', null);
        $this->productA = $this->product('vendor-a-chili', $this->vendorA->id);
        $this->productB = $this->product('vendor-b-turmeric', $this->vendorB->id);
    }

    // ── fixtures ────────────────────────────────────────────────────────────

    private function vendor(string $key): Vendor
    {
        $user = User::factory()->create(['role' => 'vendor']);
        return Vendor::create(['user_id' => $user->id, 'shop_name' => "Shop $key", 'slug' => "shop-$key", 'owner_name' => 'Owner',
            'phone' => '0170000000'.ord($key) % 10, 'email' => "$key@example.com", 'status' => 'approved', 'is_active' => true]);
    }

    private function product(string $slug, ?int $vendorId): Product
    {
        $p = Product::create(['name_bn' => $slug, 'slug' => $slug, 'stock' => 10, 'retail_price_1kg' => 1000, 'is_active' => true,
            'show_in_retail' => true, 'show_in_wholesale' => true, 'vendor_id' => $vendorId, 'approval_status' => 'approved']);
        $p->syncPrices();
        return $p;
    }

    private function pack(Product $p, int $grams = 1000)
    {
        return $p->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->where('quantity_gram', $grams)->first();
    }

    private function enable(array $platform = [], bool $vendors = true): MarketingSetting
    {
        $s = MarketingSetting::current();
        $s->update(array_replace(['pixel_enabled' => true, 'pixel_ids' => [self::PLATFORM], 'platform_pixel_scope' => 'all',
            'vendor_pixels_enabled' => $vendors, 'track_admin_users' => false], $platform));
        return $s;
    }

    private function vendorPixel(Vendor $vendor, string $pixelId, bool $blocked = false): VendorMarketingSetting
    {
        $row = VendorMarketingSetting::updateOrCreate(['vendor_id' => $vendor->id], ['pixel_enabled' => true, 'pixel_id' => $pixelId]);
        $row->forceFill(['admin_blocked' => $blocked])->save();
        return $row;
    }

    /** The config JSON the page hands to mosla-pixel.js (null when no pixel output). */
    private function config(string $html): ?array
    {
        return preg_match('/window\.MoslaPixelConfig = (.*?);<\/script>/s', $html, $m) ? json_decode($m[1], true) : null;
    }

    private function eventsFor(array $config, string $pixel, ?string $name = null): array
    {
        return array_values(array_filter($config['queue'], fn ($e) => $e['pixel'] === $pixel && (! $name || $e['name'] === $name)));
    }

    private function order(array $lines, float $grandTotal): Order
    {
        $row = ['order_number' => 'MM-PX-'.random_int(1000, 9999), 'mobile_number' => '01712345678', 'grand_total' => $grandTotal,
            'created_at' => now(), 'updated_at' => now()];
        $orderId = DB::table('orders')->insertGetId($this->fillRequired('orders', $row));
        foreach ($lines as [$product, $pack, $lineTotal]) {
            DB::table('order_items')->insert($this->fillRequired('order_items', [
                'order_id' => $orderId, 'product_id' => $product->id, 'price_id' => $pack->id, 'vendor_id' => $product->vendor_id,
                'product_name' => $product->name_bn, 'line_total' => $lineTotal, 'unit_price' => $lineTotal,
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
        return Order::findOrFail($orderId);
    }

    private function fillRequired(string $table, array $row): array
    {
        foreach (Schema::getColumns($table) as $col) {
            if ($col['nullable'] || $col['default'] !== null || $col['auto_increment'] || array_key_exists($col['name'], $row)) {
                continue;
            }
            $type = strtolower($col['type_name']);
            $row[$col['name']] = match (true) {
                str_contains($type, 'int'), str_contains($type, 'dec'), str_contains($type, 'num'), str_contains($type, 'real'),
                str_contains($type, 'float'), str_contains($type, 'double') => 0,
                str_contains($type, 'date'), str_contains($type, 'time') => now(),
                default => 'test',
            };
        }
        return $row;
    }

    // ── tests ───────────────────────────────────────────────────────────────

    public function test_no_pixel_output_when_disabled(): void
    {
        $html = $this->get('/products/admin-cumin')->assertOk()->getContent();
        $this->assertStringNotContainsString('fbevents.js', $html);
        $this->assertNull($this->config($html));

        MarketingSetting::current()->update(['pixel_enabled' => false, 'pixel_ids' => [self::PLATFORM]]);
        $this->assertStringNotContainsString('fbevents.js', $this->get('/')->getContent());
    }

    public function test_catalogue_only_exposes_rendered_product_vendors_and_add_to_cart_still_routes(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $this->vendorPixel($this->vendorB, self::VENDOR_B);
        $this->productB->update(['is_active' => false]);

        $html = $this->get('/')->assertOk()->getContent();
        $cfg = $this->config($html);
        $this->assertSame([$this->vendorA->id => self::VENDOR_A], $cfg['vendors']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);
        $this->assertStringNotContainsString(self::VENDOR_B, $html);

        // Execute the actual browser helper with the real page config and shared cart API.
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const calls = [];
let items = [];
const window = {
    MoslaPixelConfig: input.config,
    fbq: (...args) => calls.push(args),
    msCart: { get: () => items, replace: next => { items = next; } }
};
vm.runInNewContext(fs.readFileSync('public/js/mosla-pixel.js', 'utf8'), {
    window, document: { readyState: 'complete' }
});
window.msCart.replace([input.item]);
window.msCart.replace([input.item]);
process.stdout.write(JSON.stringify(calls.filter(call => call[2] === 'AddToCart')));
JS;
        $pack = $this->pack($this->productA);
        $process = new \Symfony\Component\Process\Process(['node', '-e', $script], base_path());
        $process->setInput(json_encode(['config' => $cfg, 'item' => [
            'uid' => 'test-pack', 'productId' => $this->productA->id, 'priceId' => $pack->id,
            'price' => (float) $pack->final_price,
        ]]));
        $process->mustRun();
        $calls = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([self::PLATFORM, self::VENDOR_A], array_column($calls, 1));
        $this->assertSame([$this->productA->id.'-'.$pack->id], $calls[1][3]['content_ids']);
    }

    public function test_pages_without_commerce_context_expose_no_vendor_maps(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $cfg = $this->config($this->get('/faq')->assertOk()->getContent());
        $this->assertSame([], $cfg['vendors']);
        $this->assertSame([], $cfg['productVendors']);
    }

    public function test_category_page_excludes_enabled_vendors_outside_rendered_catalogue_and_featured_cards(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $this->vendorPixel($this->vendorB, self::VENDOR_B);
        $category = \App\Models\Category::create(['name_bn' => 'Chili', 'slug' => 'chili', 'is_active' => true]);
        $this->productA->update(['category_id' => $category->id]);
        // Featured products are independent of the category filter: move B outside that strip too.
        for ($i = 0; $i < 8; $i++) {
            $this->product('featured-admin-'.$i, null);
        }
        $html = $this->get('/?category=chili')->assertOk()->getContent();
        $cfg = $this->config($html);
        $this->assertSame([$this->vendorA->id => self::VENDOR_A], $cfg['vendors']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);
        $this->assertStringNotContainsString(self::VENDOR_B, $html);
        $this->assertStringNotContainsString('vendor-b-turmeric', $html);

        // A vendor outside the selected category is relevant when its featured card is rendered.
        $featured = $this->product('featured-vendor-b', $this->vendorB->id);
        $cfg = $this->config($this->get('/?category=chili')->assertOk()->getContent());
        $this->assertSame(self::VENDOR_B, $cfg['vendors'][$this->vendorB->id]);
        $this->assertSame($this->vendorB->id, $cfg['productVendors'][$featured->id]);
        $this->assertArrayNotHasKey($this->productB->id, $cfg['productVendors']);
    }

    public function test_platform_pixel_on_storefront_but_never_on_admin_or_vendor_panels(): void
    {
        $this->enable();
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('fbevents.js', $home);
        $this->assertStringContainsString('"'.self::PLATFORM.'"', $home);
        $this->assertStringContainsString("fbq('trackSingle', id, 'PageView')", $home);

        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertDontSee('fbevents.js', false);
        $this->actingAs($this->vendorA->user)->get('/vendor/dashboard')->assertOk()->assertDontSee('fbevents.js', false);
    }

    public function test_logged_in_admin_not_tracked_unless_enabled(): void
    {
        $this->enable();
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->actingAs($admin)->get('/')->assertOk()->assertDontSee('fbevents.js', false);

        $this->enable(['track_admin_users' => true]);
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('fbevents.js', false);
    }

    public function test_view_content_uses_product_group_and_only_the_owning_vendor_pixel(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $this->vendorPixel($this->vendorB, self::VENDOR_B);

        $cfg = $this->config($this->get('/products/vendor-a-chili')->assertOk()->getContent());
        $this->assertSame([$this->vendorA->id => self::VENDOR_A], $cfg['vendors']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);
        $platform = $this->eventsFor($cfg, self::PLATFORM, 'ViewContent');
        $this->assertCount(1, $platform);
        $this->assertSame([(string) $this->productA->id], $platform[0]['params']['content_ids']);
        $this->assertSame('product_group', $platform[0]['params']['content_type']);
        $this->assertCount(1, $this->eventsFor($cfg, self::VENDOR_A, 'ViewContent'));
        $this->assertSame([], $this->eventsFor($cfg, self::VENDOR_B), 'Vendor B must not receive vendor A product events.');

        $cfg = $this->config($this->get('/products/admin-cumin')->getContent());
        $this->assertSame([], $cfg['vendors']);
        $this->assertSame([], $cfg['productVendors']);
        $this->assertSame([], $this->eventsFor($cfg, self::VENDOR_A));
        $this->assertSame([], $this->eventsFor($cfg, self::VENDOR_B));
    }

    public function test_blocked_disabled_or_master_off_vendor_pixel_is_never_used(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A, blocked: true);
        $html = $this->get('/products/vendor-a-chili')->getContent();
        $this->assertStringNotContainsString(self::VENDOR_A, $html);

        VendorMarketingSetting::where('vendor_id', $this->vendorA->id)->update(['admin_blocked' => false]);
        $this->enable([], vendors: false);
        $this->assertStringNotContainsString(self::VENDOR_A, $this->get('/products/vendor-a-chili')->getContent());

        $this->enable();
        VendorMarketingSetting::where('vendor_id', $this->vendorA->id)->update(['pixel_enabled' => false]);
        $this->assertStringNotContainsString(self::VENDOR_A, $this->get('/products/vendor-a-chili')->getContent());
    }

    public function test_mixed_checkout_splits_items_and_values_per_vendor(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $this->vendorPixel($this->vendorB, self::VENDOR_B);
        $admin1kg = $this->pack($this->adminProduct);
        $a1kg = $this->pack($this->productA);

        $this->post(route('checkout.start'), ['items' => [$admin1kg->id, $a1kg->id]])->assertRedirect(route('checkout.review'));
        $cfg = $this->config($this->get(route('checkout.review'))->assertOk()->getContent());
        $this->assertSame([$this->vendorA->id => self::VENDOR_A], $cfg['vendors']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);

        $platform = $this->eventsFor($cfg, self::PLATFORM, 'InitiateCheckout')[0]['params'];
        $this->assertEqualsCanonicalizing([$this->adminProduct->id.'-'.$admin1kg->id, $this->productA->id.'-'.$a1kg->id], $platform['content_ids']);
        $this->assertEquals(round((float) $admin1kg->final_price + (float) $a1kg->final_price, 2), $platform['value']);

        $vendor = $this->eventsFor($cfg, self::VENDOR_A, 'InitiateCheckout')[0]['params'];
        $this->assertSame([$this->productA->id.'-'.$a1kg->id], $vendor['content_ids']);
        $this->assertEquals((float) $a1kg->final_price, $vendor['value']);
        $this->assertSame([], $this->eventsFor($cfg, self::VENDOR_B), 'Vendor B has no items in this cart.');

        $zone = \App\Models\DeliveryZone::create([
            'zone_name' => 'Pixel test zone', 'zone_type' => 'inside_dhaka',
            'delivery_charge' => 60, 'is_active' => true,
        ]);
        $location = \App\Models\DeliveryLocation::create([
            'zone_id' => $zone->id, 'location_name' => 'Pixel test location', 'is_active' => true,
        ]);
        $html = $this->withSession(['checkout.guest_address' => [
            'name' => 'Guest', 'phone' => '01712345678', 'full_address' => 'Test address',
            'delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id,
        ]])->get(route('checkout.payment'))->assertOk()->getContent();
        $cfg = $this->config($html);
        $this->assertSame([$this->vendorA->id => self::VENDOR_A], $cfg['vendors']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);
        $this->assertStringNotContainsString(self::VENDOR_B, $html);
        $this->assertCount(1, $this->eventsFor($cfg, self::VENDOR_A, 'AddPaymentInfo'));
    }

    public function test_scope_own_keeps_vendor_items_away_from_platform_pixel(): void
    {
        $this->enable(['platform_pixel_scope' => 'own']);
        $this->vendorPixel($this->vendorA, self::VENDOR_A);

        $cfg = $this->config($this->get('/products/vendor-a-chili')->getContent());
        $this->assertSame([], $this->eventsFor($cfg, self::PLATFORM, 'ViewContent'));
        $this->assertCount(1, $this->eventsFor($cfg, self::VENDOR_A, 'ViewContent'));
        $this->assertTrue($cfg['own']);
        $this->assertSame($this->vendorA->id, $cfg['productVendors'][$this->productA->id], 'AddToCart routing needs the product → vendor map.');

        $admin1kg = $this->pack($this->adminProduct);
        $a1kg = $this->pack($this->productA);
        $this->post(route('checkout.start'), ['items' => [$admin1kg->id, $a1kg->id]]);
        $platform = $this->eventsFor($this->config($this->get(route('checkout.review'))->getContent()), self::PLATFORM, 'InitiateCheckout')[0]['params'];
        $this->assertSame([$this->adminProduct->id.'-'.$admin1kg->id], $platform['content_ids']);
        $this->assertEquals((float) $admin1kg->final_price, $platform['value']);
    }

    public function test_purchase_fires_once_with_order_event_ids_and_only_for_the_placing_browser(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $this->vendorPixel($this->vendorB, self::VENDOR_B);
        $admin1kg = $this->pack($this->adminProduct);
        $a1kg = $this->pack($this->productA);
        $order = $this->order([[$this->adminProduct, $admin1kg, 1000], [$this->productA, $a1kg, 400]], 1520);

        // A stranger opening the success URL: nothing.
        $cfg = $this->config($this->get(route('order.success', $order->order_number))->assertOk()->getContent());
        $this->assertSame([$this->vendorA->id => self::VENDOR_A], $cfg['vendors']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);
        $this->assertSame([], $this->eventsFor($cfg, self::PLATFORM, 'Purchase'));

        // The placing browser (flag set by OrderController::store): fires once.
        $cfg = $this->withSession([MetaPixel::PURCHASE_KEY => $order->order_number])
            ->get(route('order.success', $order->order_number))->getContent();
        $cfg = $this->config($cfg);
        $platform = $this->eventsFor($cfg, self::PLATFORM, 'Purchase');
        $this->assertCount(1, $platform);
        $this->assertSame($order->order_number, $platform[0]['eventID']);
        $this->assertEquals(1520, $platform[0]['params']['value'], 'Platform (scope all) value = order grand total.');

        $vendor = $this->eventsFor($cfg, self::VENDOR_A, 'Purchase');
        $this->assertCount(1, $vendor);
        $this->assertSame($order->order_number.'-'.$this->vendorA->id, $vendor[0]['eventID']);
        $this->assertEquals(400, $vendor[0]['params']['value'], 'Vendor value = only its own items.');
        $this->assertSame([$this->productA->id.'-'.$a1kg->id], $vendor[0]['params']['content_ids']);
        $this->assertNotNull(VendorMarketingSetting::where('vendor_id', $this->vendorA->id)->value('last_event_at'));

        // Refresh: flag consumed → no second Purchase.
        $cfg = $this->config($this->get(route('order.success', $order->order_number))->getContent());
        $this->assertSame([], $this->eventsFor($cfg, self::PLATFORM, 'Purchase'));
    }

    public function test_order_store_sets_one_time_purchase_flag(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/OrderController.php'));
        $this->assertStringContainsString('session()->put(\App\Support\MetaPixel::PURCHASE_KEY, $order->order_number)', $src);
    }

    public function test_lead_fires_on_next_page_for_platform_and_product_vendor(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);

        $this->from('/products/vendor-a-chili')->post(route('products.enquiry.store', 'vendor-a-chili'), [
            'customer_name' => 'Guest', 'customer_phone' => '01712345678', 'delivery_location' => 'Dhaka', 'quantity_kg' => 50,
        ])->assertRedirect();
        $enquiry = WholesaleEnquiry::latest('id')->firstOrFail();

        $cfg = $this->config($this->get('/products/vendor-a-chili')->getContent());
        $this->assertSame('lead-'.$enquiry->id, $this->eventsFor($cfg, self::PLATFORM, 'Lead')[0]['eventID']);
        $this->assertCount(1, $this->eventsFor($cfg, self::VENDOR_A, 'Lead'));

        // Released once only.
        $cfg = $this->config($this->get('/products/vendor-a-chili')->getContent());
        $this->assertSame([], $this->eventsFor($cfg, self::PLATFORM, 'Lead'));
    }

    public function test_complete_registration_platform_only(): void
    {
        $this->enable();
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $this->post(route('customer.register.post'), [
            'name' => 'New', 'mobile_number' => '01799999999', 'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect();
        $user = User::where('phone', '01799999999')->firstOrFail();

        $cfg = $this->config($this->get('/')->getContent());
        $this->assertSame('reg-'.$user->id, $this->eventsFor($cfg, self::PLATFORM, 'CompleteRegistration')[0]['eventID']);
        $this->assertSame([], $this->eventsFor($cfg, self::VENDOR_A));
    }

    public function test_admin_rejects_script_or_non_numeric_pixel_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        foreach (['<script>alert(1)</script>', '12345', "1234567890123'onload", 'fbq("init","1")'] as $bad) {
            $this->actingAs($admin)->post(route('admin.marketing-settings.update'), [
                'pixel_enabled' => 1, 'pixel_ids' => $bad, 'platform_pixel_scope' => 'all',
            ])->assertSessionHasErrors('pixel_ids');
        }
        $this->assertFalse((bool) MarketingSetting::current()->pixel_enabled);

        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), [
            'pixel_enabled' => 1, 'pixel_ids' => self::PLATFORM.", 444444444444444", 'platform_pixel_scope' => 'own',
        ])->assertSessionHasNoErrors();
        $this->assertSame([self::PLATFORM, '444444444444444'], MarketingSetting::current()->pixel_ids);
    }

    public function test_vendor_marketing_tab_gated_validated_and_cannot_unblock_itself(): void
    {
        $user = $this->vendorA->user;
        $this->actingAs($user)->get(route('vendor.profile.marketing'))->assertNotFound();
        $this->actingAs($user)->get(route('vendor.profile.index'))->assertOk()->assertDontSee('Marketing</a>', false);

        $this->enable();
        $this->actingAs($user)->get(route('vendor.profile.index'))->assertOk()->assertSee('Marketing</a>', false);
        $this->actingAs($user)->get(route('vendor.profile.marketing'))->assertOk()->assertSee('Pixel ID কোথায় পাবেন?');

        $this->actingAs($user)->put(route('vendor.profile.marketing.update'), ['pixel_enabled' => 1, 'pixel_id' => '<img src=x onerror=alert(1)>'])
            ->assertSessionHasErrors('pixel_id');

        $this->vendorPixel($this->vendorA, self::VENDOR_A, blocked: true);
        $this->actingAs($user)->put(route('vendor.profile.marketing.update'), [
            'pixel_enabled' => 1, 'pixel_id' => self::VENDOR_A, 'admin_blocked' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertTrue((bool) VendorMarketingSetting::where('vendor_id', $this->vendorA->id)->value('admin_blocked'));
    }

    public function test_admin_can_block_a_vendor_pixel_from_the_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->enable();
        $row = $this->vendorPixel($this->vendorA, self::VENDOR_A);

        $this->actingAs($admin)->get(route('admin.marketing-settings.index'))->assertOk()->assertSee(self::VENDOR_A)->assertSee('চালু');
        $this->actingAs($admin)->post(route('admin.marketing-settings.vendors.toggle', $row))->assertRedirect();
        $this->assertTrue((bool) $row->fresh()->admin_blocked);

        auth()->logout();
        $this->assertStringNotContainsString(self::VENDOR_A, $this->get('/products/vendor-a-chili')->getContent());
    }

    public function test_meta_cookies_are_not_encrypted(): void
    {
        $this->assertTrue(app(\Illuminate\Cookie\Middleware\EncryptCookies::class)->isDisabled('_fbp'));
        $this->assertTrue(app(\Illuminate\Cookie\Middleware\EncryptCookies::class)->isDisabled('_fbc'));
    }

    // ── Risk 1: scope 'own' AddToCart must fail closed ─────────────────────

    public function test_scope_own_sends_admin_product_allow_list_without_vendor_data(): void
    {
        $this->enable(['platform_pixel_scope' => 'own'], false);

        $html = $this->get('/products/admin-cumin')->assertOk()->getContent();
        $cfg = $this->config($html);
        $this->assertTrue($cfg['own']);
        $this->assertContains($this->adminProduct->id, $cfg['adminProducts']);
        $this->assertNotContains($this->productA->id, $cfg['adminProducts'], 'Vendor products are never on the platform allow-list.');
        $this->assertNotContains($this->productB->id, $cfg['adminProducts']);
        // Only product ids: no vendor ids or vendor pixel ids are exposed by the allow-list.
        $this->assertStringNotContainsString(self::VENDOR_A, $html);
        $this->assertStringNotContainsString(self::VENDOR_B, $html);

        // A vendor product page: its id stays off the allow-list, so JS cannot send it to the platform.
        $cfg = $this->config($this->get('/products/vendor-b-turmeric')->getContent());
        $this->assertNotContains($this->productB->id, $cfg['adminProducts']);
    }

    public function test_scope_all_has_no_admin_product_list(): void
    {
        $this->enable();
        $cfg = $this->config($this->get('/products/admin-cumin')->getContent());
        $this->assertFalse($cfg['own']);
        $this->assertArrayNotHasKey('adminProducts', $cfg);
    }

    public function test_add_to_cart_script_fails_closed_for_unknown_owner_in_scope_own(): void
    {
        $js = file_get_contents(public_path('js/mosla-pixel.js'));
        $this->assertStringContainsString('adminProducts', $js);
        $this->assertMatchesRegularExpression('/platformAllowed\s*=\s*!cfg\.own\s*\|\|\s*\(!vendorId\s*&&/', $js);
    }

    // ── Risk 2: fixed combos carry the product owner for tracking only ─────

    private function combo(array $lines): int
    {
        $comboId = DB::table('combos')->insertGetId($this->fillRequired('combos', [
            'name' => 'Pixel combo', 'slug' => 'pixel-combo-'.random_int(1000, 9999), 'sell_type' => 'retail',
            'sell_price' => array_sum(array_column($lines, 2)), 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]));
        foreach ($lines as [$product, $pack, $lineTotal]) {
            DB::table('combo_items')->insert($this->fillRequired('combo_items', [
                'combo_id' => $comboId, 'sell_type' => 'retail', 'product_id' => $product->id, 'product_price_id' => $pack->id,
                'quantity_gram' => 1000, 'unit_price' => $lineTotal, 'line_total' => $lineTotal,
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
        return $comboId;
    }

    /** A fixed-combo order exactly as OrderController stores it: order_items.vendor_id is NULL. */
    private function comboOrder(int $comboId, array $lines, float $grandTotal): Order
    {
        $row = ['order_number' => 'MM-PXC-'.random_int(1000, 9999), 'mobile_number' => '01712345678', 'grand_total' => $grandTotal,
            'combo_id' => $comboId, 'order_type' => 'fixed_combo', 'created_at' => now(), 'updated_at' => now()];
        $orderId = DB::table('orders')->insertGetId($this->fillRequired('orders', $row));
        foreach ($lines as [$product, $pack, $lineTotal]) {
            DB::table('order_items')->insert($this->fillRequired('order_items', [
                'order_id' => $orderId, 'product_id' => $product->id, 'price_id' => $pack->id, 'vendor_id' => null,
                'product_name' => $product->name_bn, 'line_total' => $lineTotal, 'unit_price' => $lineTotal,
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
        return Order::findOrFail($orderId);
    }

    public function test_fixed_combo_checkout_splits_vendor_items(): void
    {
        $this->enable(['platform_pixel_scope' => 'own']);
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $admin1kg = $this->pack($this->adminProduct);
        $a1kg = $this->pack($this->productA);
        $comboId = $this->combo([[$this->adminProduct, $admin1kg, 1000], [$this->productA, $a1kg, 400]]);

        $this->post(route('checkout.start'), ['combo_id' => $comboId])->assertRedirect(route('checkout.review'));
        $cfg = $this->config($this->get(route('checkout.review'))->assertOk()->getContent());

        $platform = $this->eventsFor($cfg, self::PLATFORM, 'InitiateCheckout')[0]['params'];
        $this->assertSame([$this->adminProduct->id.'-'.$admin1kg->id], $platform['content_ids'], 'Scope own: vendor combo item stays off the platform pixel.');
        $this->assertEquals(1000, $platform['value']);

        $vendor = $this->eventsFor($cfg, self::VENDOR_A, 'InitiateCheckout');
        $this->assertCount(1, $vendor, 'The vendor now sees its combo item.');
        $this->assertSame([$this->productA->id.'-'.$a1kg->id], $vendor[0]['params']['content_ids']);
        $this->assertEquals(400, $vendor[0]['params']['value']);
    }

    public function test_fixed_combo_purchase_uses_product_owner_without_changing_the_order(): void
    {
        $this->enable(['platform_pixel_scope' => 'own']);
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $admin1kg = $this->pack($this->adminProduct);
        $a1kg = $this->pack($this->productA);
        $lines = [[$this->adminProduct, $admin1kg, 1000], [$this->productA, $a1kg, 400]];
        $order = $this->comboOrder($this->combo($lines), $lines, 1460);

        $cfg = $this->config($this->withSession([MetaPixel::PURCHASE_KEY => $order->order_number])
            ->get(route('order.success', $order->order_number))->assertOk()->getContent());

        $platform = $this->eventsFor($cfg, self::PLATFORM, 'Purchase');
        $this->assertCount(1, $platform);
        $this->assertSame([$this->adminProduct->id.'-'.$admin1kg->id], $platform[0]['params']['content_ids']);
        $this->assertEquals(1000, $platform[0]['params']['value']);

        $vendor = $this->eventsFor($cfg, self::VENDOR_A, 'Purchase');
        $this->assertCount(1, $vendor);
        $this->assertSame($order->order_number.'-'.$this->vendorA->id, $vendor[0]['eventID']);
        $this->assertEquals(400, $vendor[0]['params']['value']);
        $this->assertSame([$this->productA->id => $this->vendorA->id], $cfg['productVendors']);

        // Analytics only: the stored order is untouched.
        $this->assertSame(0, DB::table('order_items')->where('order_id', $order->id)->whereNotNull('vendor_id')->count());
    }

    public function test_non_combo_order_keeps_null_vendor_as_admin(): void
    {
        // A regular order line with NULL vendor_id is an admin item even if the product is
        // later reassigned; the owner fallback applies to fixed combos only.
        $this->enable(['platform_pixel_scope' => 'own']);
        $this->vendorPixel($this->vendorA, self::VENDOR_A);
        $a1kg = $this->pack($this->productA);
        $order = $this->order([[$this->productA, $a1kg, 400]], 460);
        DB::table('order_items')->where('order_id', $order->id)->update(['vendor_id' => null]);

        $cfg = $this->config($this->withSession([MetaPixel::PURCHASE_KEY => $order->order_number])
            ->get(route('order.success', $order->order_number))->getContent());
        $this->assertSame([], $this->eventsFor($cfg, self::VENDOR_A, 'Purchase'));
        $this->assertCount(1, $this->eventsFor($cfg, self::PLATFORM, 'Purchase'));
    }
}
