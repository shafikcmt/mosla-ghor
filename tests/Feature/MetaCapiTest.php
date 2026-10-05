<?php

namespace Tests\Feature;

use App\Jobs\SendMetaCapiEvents;
use App\Models\MarketingSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WholesaleEnquiry;
use App\Support\MetaCapi;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MetaCapiTest extends TestCase
{
    private const PLATFORM = '111111111111111';
    private const TOKEN = 'EAAGtestTOKEN123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function enable(array $extra = []): MarketingSetting
    {
        $s = MarketingSetting::current();
        $s->update(array_replace(['pixel_enabled' => true, 'pixel_ids' => [self::PLATFORM], 'platform_pixel_scope' => 'all',
            'capi_enabled' => true, 'capi_access_token' => self::TOKEN], $extra));
        return $s;
    }

    private function product(string $slug, ?int $vendorId = null): Product
    {
        $p = Product::create(['name_bn' => $slug, 'slug' => $slug, 'stock' => 10, 'retail_price_1kg' => 1000, 'is_active' => true,
            'show_in_retail' => true, 'show_in_wholesale' => true, 'vendor_id' => $vendorId, 'approval_status' => 'approved']);
        $p->syncPrices();
        return $p;
    }

    private function order(Product $product, float $total): Order
    {
        $pack = $product->prices()->where('sell_type', 'retail')->first();
        $id = DB::table('orders')->insertGetId($this->fillRequired('orders', [
            'order_number' => 'MM-CAPI-1', 'mobile_number' => '01712345678', 'customer_name' => 'Rahim Uddin',
            'grand_total' => $total, 'created_at' => now(), 'updated_at' => now(),
        ]));
        DB::table('order_items')->insert($this->fillRequired('order_items', [
            'order_id' => $id, 'product_id' => $product->id, 'price_id' => $pack->id, 'vendor_id' => $product->vendor_id,
            'product_name' => $product->name_bn, 'line_total' => $total, 'unit_price' => $total, 'created_at' => now(), 'updated_at' => now(),
        ]));
        return Order::findOrFail($id);
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

    /** Events posted to Meta in this test (decoded). */
    private function sentEvents(): array
    {
        $events = [];
        foreach (Http::recorded() as [$request]) {
            $events = array_merge($events, json_decode($request['data'], true));
        }
        return $events;
    }

    // ── tests ───────────────────────────────────────────────────────────────

    public function test_token_is_encrypted_at_rest_and_hidden(): void
    {
        $s = $this->enable();
        $raw = DB::table('marketing_settings')->value('capi_access_token');
        $this->assertNotSame(self::TOKEN, $raw);
        $this->assertSame(self::TOKEN, $s->fresh()->capi_access_token);
        $this->assertArrayNotHasKey('capi_access_token', $s->fresh()->toArray());
    }

    public function test_purchase_is_queued_with_browser_event_id_and_hashed_user_data(): void
    {
        Bus::fake();
        $this->enable();
        $order = $this->order($this->product('cumin'), 1520);

        MetaCapi::purchase($order, 'Buyer@Example.com ');

        Bus::assertDispatchedAfterResponse(SendMetaCapiEvents::class, function ($job) use ($order) {
            $e = $job->events[0];
            return $e['event_name'] === 'Purchase'
                && $e['event_id'] === $order->order_number
                && $e['custom_data']['value'] == 1520
                && $e['custom_data']['currency'] === 'BDT'
                && $e['user_data']['ph'] === [hash('sha256', '8801712345678')]
                && $e['user_data']['em'] === [hash('sha256', 'buyer@example.com')]
                && $e['user_data']['fn'] === [hash('sha256', 'rahim')]
                && $e['action_source'] === 'website';
        });
    }

    public function test_nothing_is_sent_when_disabled_or_missing_token(): void
    {
        Bus::fake();
        $order = $this->order($this->product('cumin'), 100);

        $this->enable(['capi_enabled' => false]);
        MetaCapi::purchase($order);
        $this->app->instance('request', \Illuminate\Http\Request::create('/')); // settings are cached per request
        $this->enable(['capi_access_token' => null]);
        MetaCapi::purchase($order);

        Bus::assertNothingDispatched();
    }

    public function test_scope_own_skips_vendor_only_orders(): void
    {
        Bus::fake();
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $vendorUser->id, 'shop_name' => 'V', 'slug' => 'v', 'owner_name' => 'O',
            'phone' => '01700000009', 'email' => 'v@example.com', 'status' => 'approved', 'is_active' => true]);
        $this->enable(['platform_pixel_scope' => 'own']);

        MetaCapi::purchase($this->order($this->product('vendor-chili', $vendor->id), 400));

        Bus::assertNothingDispatched();
    }

    public function test_logged_in_admin_is_not_tracked(): void
    {
        Bus::fake();
        $this->enable();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        MetaCapi::purchase($this->order($this->product('cumin'), 100));

        Bus::assertNothingDispatched();
    }

    public function test_order_store_sends_server_purchase(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/OrderController.php'));
        $this->assertStringContainsString('\App\Support\MetaCapi::purchase($order, $validated[\'customer_email\'] ?? null);', $src);
    }

    public function test_enquiry_sends_lead_to_meta_after_response(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        $this->enable(['test_event_code' => 'TEST123']);
        $this->product('cumin');

        $this->from('/products/cumin')->post(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Karim', 'customer_phone' => '01812345678', 'delivery_location' => 'Dhaka', 'quantity_kg' => 50,
        ])->assertRedirect();
        $enquiry = WholesaleEnquiry::latest('id')->firstOrFail();

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/'.self::PLATFORM.'/events')
            && $r['access_token'] === self::TOKEN && $r['test_event_code'] === 'TEST123');
        $events = $this->sentEvents();
        $this->assertSame('Lead', $events[0]['event_name']);
        $this->assertSame('lead-'.$enquiry->id, $events[0]['event_id']);
        $this->assertSame([hash('sha256', '8801812345678')], $events[0]['user_data']['ph']);
        $this->assertNotNull(MarketingSetting::first()->capi_last_success_at);
    }

    public function test_registration_sends_complete_registration(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        $this->enable();

        $this->post(route('customer.register.post'), [
            'name' => 'New', 'mobile_number' => '01799999999', 'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect();
        $user = User::where('phone', '01799999999')->firstOrFail();

        $events = $this->sentEvents();
        $this->assertSame('CompleteRegistration', $events[0]['event_name']);
        $this->assertSame('reg-'.$user->id, $events[0]['event_id']);
    }

    public function test_meta_error_is_recorded_and_never_breaks_the_request(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token.']], 400)]);
        $this->enable();
        $this->product('cumin');

        $this->from('/products/cumin')->post(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Karim', 'customer_phone' => '01812345678', 'delivery_location' => 'Dhaka', 'quantity_kg' => 50,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $s = MarketingSetting::first();
        $this->assertStringContainsString('Invalid OAuth access token.', $s->capi_last_error);
        $this->assertNotNull($s->capi_last_error_at);
    }

    public function test_admin_saves_token_keeps_it_when_blank_and_can_clear_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $base = ['pixel_enabled' => 1, 'pixel_ids' => self::PLATFORM, 'platform_pixel_scope' => 'all'];

        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), $base + ['capi_enabled' => 1])
            ->assertSessionHasErrors('capi_access_token');

        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), $base + ['capi_enabled' => 1, 'capi_access_token' => self::TOKEN])
            ->assertSessionHasNoErrors();
        $this->assertSame(self::TOKEN, MarketingSetting::first()->capi_access_token);

        // Blank field on a later save keeps the stored token.
        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), $base + ['capi_enabled' => 1, 'capi_access_token' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame(self::TOKEN, MarketingSetting::first()->capi_access_token);

        $page = $this->actingAs($admin)->get(route('admin.marketing-settings.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::TOKEN, $page);

        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), $base + ['capi_token_clear' => 1])
            ->assertSessionHasNoErrors();
        $this->assertNull(MarketingSetting::first()->capi_access_token);
    }

    public function test_admin_test_button_requires_test_code_and_reports_result(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->enable();

        $this->actingAs($admin)->post(route('admin.marketing-settings.capi-test'))->assertSessionHas('error');
        Http::assertNothingSent();

        $this->enable(['test_event_code' => 'TEST123']);
        $this->actingAs($admin)->post(route('admin.marketing-settings.capi-test'))->assertSessionHas('success');
        Http::assertSent(fn (HttpRequest $r) => $r['test_event_code'] === 'TEST123');
    }
}
