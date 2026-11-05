<?php

namespace Tests\Feature;

use App\Models\{BdDivision, BdDistrict, Customer, DeliveryZone, Order, PriceSetting, Product, User, WholesaleEnquiry, WholesaleQuote, PaykariComboEnquiry};
use Tests\TestCase;

class MarketingSourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function attr(array $over = []): string
    {
        return json_encode($over + [
            'utm_source' => 'facebook', 'utm_medium' => 'automation', 'utm_campaign' => 'wholesale_comment',
            'landing_path' => '/products/cumin', 'referrer_host' => 'l.facebook.com', 'captured_at' => now()->toIso8601String(),
        ]);
    }

    private function wholesaleProduct(): Product
    {
        return Product::create(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500, 'is_active' => true,
            'show_in_retail' => false, 'show_in_wholesale' => true, 'min_order_quantity' => 10, 'min_order_unit' => 'kg']);
    }

    // ── A) capture ──────────────────────────────────────────────────────────
    public function test_utm_landing_sets_cookie_with_clean_values(): void
    {
        $r = $this->get('/?utm_source=face<b>book&utm_campaign=spring_sale&fbclid=ABC123&utm_term=%20%20');
        $r->assertOk()->assertCookie('ms_attr');
        $cookie = $r->getCookie('ms_attr', false);
        $data = json_decode($r->getCookie('ms_attr')->getValue(), true);
        $this->assertSame('facebbook', $data['utm_source']);
        $this->assertSame('spring_sale', $data['utm_campaign']);
        $this->assertArrayNotHasKey('utm_term', $data);
        $this->assertSame('/', $data['landing_path']);
        $this->assertStringNotContainsString('ABC123', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_cookie_is_encrypted_on_the_wire(): void
    {
        $r = $this->get('/?utm_source=facebook');
        $raw = $r->getCookie('ms_attr', false)->getValue();
        $this->assertStringNotContainsString('facebook', $raw);
        $this->assertNotNull($r->getCookie('ms_attr', true));
    }

    public function test_values_are_capped_at_100_chars(): void
    {
        $r = $this->get('/?utm_source='.str_repeat('a', 300));
        $data = json_decode($r->getCookie('ms_attr')->getValue(), true);
        $this->assertSame(100, strlen($data['utm_source']));
    }

    public function test_request_without_utm_does_not_set_or_overwrite_cookie(): void
    {
        $this->get('/')->assertCookieMissing('ms_attr');
        $this->withCookie('ms_attr', $this->attr())->get('/?fbclid=zzz')->assertCookieMissing('ms_attr');
    }

    public function test_new_utm_landing_overwrites_old_value(): void
    {
        $r = $this->withCookie('ms_attr', $this->attr())->get('/?utm_source=google&utm_campaign=brand');
        $data = json_decode($r->getCookie('ms_attr')->getValue(), true);
        $this->assertSame('google', $data['utm_source']);
        $this->assertSame('brand', $data['utm_campaign']);
    }

    public function test_admin_api_and_ajax_requests_never_set_cookie(): void
    {
        $this->get('/admin/login?utm_source=facebook')->assertCookieMissing('ms_attr');
        $this->getJson('/api/anything?utm_source=facebook')->assertCookieMissing('ms_attr');
        $this->get('/?utm_source=facebook', ['X-Requested-With' => 'XMLHttpRequest'])->assertCookieMissing('ms_attr');
        $this->get('/robots.txt?utm_source=facebook')->assertCookieMissing('ms_attr');
        $this->post('/?utm_source=facebook')->assertCookieMissing('ms_attr');
    }

    // ── B) records ──────────────────────────────────────────────────────────
    private function orderFixture(): array
    {
        $division = BdDivision::create(['source_id' => '1', 'name' => 'D', 'bn_name' => 'D', 'is_active' => true]);
        $district = BdDistrict::create(['source_id' => '1', 'division_id' => $division->id, 'name' => 'X', 'bn_name' => 'X', 'is_active' => true]);
        $zone = DeliveryZone::create(['zone_name' => 'Dhaka City', 'zone_type' => 'inside_dhaka', 'delivery_charge' => 60, 'is_active' => true]);
        $location = $zone->locations()->create(['location_name' => 'City', 'is_active' => true]);
        PriceSetting::current()->fill(['default_packaging_cost' => 10, 'minimum_order_amount' => 0])->save();
        $product = Product::create(['name_bn' => 'Cumin', 'slug' => 'o-cumin', 'stock' => 100, 'retail_price_1kg' => 500, 'is_active' => true, 'show_in_retail' => true]);
        $product->syncPrices();
        $price = $product->prices()->where('sell_type', 'retail')->firstOrFail();

        return ['full_name' => 'Buyer', 'mobile_number' => '01712345678', 'full_address' => 'House 1',
            'bd_division_id' => $division->id, 'bd_district_id' => $district->id,
            'delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id,
            'payment_method' => 'cash_on_delivery', 'items' => [['price_id' => $price->id]]];
    }

    public function test_order_after_utm_landing_saves_source_columns(): void
    {
        $payload = $this->orderFixture();
        $this->withCredentials()->withCookie('ms_attr', $this->attr())->postJson(route('order.store'), $payload)->assertOk()->assertJson(['success' => true]);
        $o = Order::firstOrFail();
        $this->assertSame('facebook', $o->utm_source);
        $this->assertSame('automation', $o->utm_medium);
        $this->assertSame('wholesale_comment', $o->utm_campaign);
        $this->assertSame('/products/cumin', $o->landing_path);
        $this->assertSame('l.facebook.com', $o->referrer_host);
    }

    public function test_order_without_utm_saves_nulls_and_bad_cookie_is_ignored(): void
    {
        $payload = $this->orderFixture();
        $this->postJson(route('order.store'), $payload)->assertOk();
        $this->withCredentials()->withCookie('ms_attr', 'not-json{')->postJson(route('order.store'), $payload)->assertOk();
        foreach (Order::all() as $o) {
            $this->assertNull($o->utm_source);
            $this->assertNull($o->landing_path);
        }
        $this->assertSame(2, Order::count());
    }

    public function test_guest_wholesale_enquiry_saves_source(): void
    {
        $this->wholesaleProduct();
        $this->withCredentials()->withCookie('ms_attr', $this->attr())->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 25,
        ])->assertCreated();
        $e = WholesaleEnquiry::sole();
        $this->assertSame('facebook', $e->utm_source);
        $this->assertSame('wholesale_comment', $e->utm_campaign);
        $this->assertSame('/products/cumin', $e->landing_path);
    }

    public function test_logged_in_wholesale_enquiry_saves_source(): void
    {
        $product = $this->wholesaleProduct();
        $user = User::factory()->create(['role' => 'customer', 'phone' => '01711111111']);
        Customer::create(['name' => 'Karim', 'mobile_number' => '01711111111', 'is_active' => true]);

        $this->actingAs($user)->withCookie('ms_attr', $this->attr())->post(route('customer.wholesale.enquiry.store'), [
            'product_id' => $product->id, 'quantity_kg' => 25, 'business_type' => 'shop', 'delivery_location' => 'Dhaka',
            'customer_name' => 'Karim', 'customer_phone' => '01711111111',
        ]);
        $e = WholesaleEnquiry::first();
        $this->assertNotNull($e, 'enquiry should be created');
        $this->assertSame('facebook', $e->utm_source);
    }

    public function test_paykari_combo_enquiry_saves_source_on_public_and_account_routes(): void
    {
        // SQLite keeps paykari_combo_enquiries.customer_id NOT NULL (guest relax is MySQL-only), so use a customer.
        $product = $this->wholesaleProduct();
        $user = User::factory()->create(['role' => 'customer', 'phone' => '01722222222']);
        Customer::create(['name' => 'Karim', 'mobile_number' => '01722222222', 'is_active' => true]);
        $payload = [
            'items' => [['product_id' => $product->id, 'quantity_kg' => 10]],
            'delivery_location' => 'Dhaka', 'customer_name' => 'Karim', 'customer_phone' => '01722222222',
        ];

        foreach (['paykari-combo.enquiry.store', 'customer.paykari-combo.store'] as $routeName) {
            $this->actingAs($user)->withCookie('ms_attr', $this->attr())->post(route($routeName), $payload)->assertSessionHasNoErrors();
        }
        $this->assertSame(2, PaykariComboEnquiry::count());
        foreach (PaykariComboEnquiry::all() as $e) {
            $this->assertSame('facebook', $e->utm_source);
            $this->assertSame('wholesale_comment', $e->utm_campaign);
        }
    }

    public function test_order_from_accepted_quote_inherits_enquiry_source(): void
    {
        $this->wholesaleProduct();
        $this->withCredentials()->withCookie('ms_attr', $this->attr())->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 30,
        ])->assertCreated();
        $enquiry = WholesaleEnquiry::sole();
        $user = $enquiry->customer->user;
        $this->assertNotNull($user);

        // SQLite keeps wholesale_quotes.vendor_id NOT NULL (relaxed on MySQL only).
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = \App\Models\Vendor::create(['user_id' => $vendorUser->id, 'shop_name' => 'Shop', 'slug' => 'shop', 'owner_name' => 'Owner',
            'phone' => '01700000000', 'email' => 'v@example.com', 'status' => 'approved', 'is_active' => true]);
        $quote = WholesaleQuote::create([
            'enquiry_id' => $enquiry->id, 'customer_id' => $enquiry->customer_id, 'vendor_id' => $vendor->id,
            'unit_price' => 850, 'quantity' => 30, 'quantity_unit' => 'kg', 'subtotal' => 25500, 'delivery_charge' => 0,
            'status' => 'sent_to_customer', 'valid_until' => now()->addDays(7),
        ]);

        // A *different* (later) cookie must not win over the enquiry's own source.
        $this->actingAs($user)->withCookie('ms_attr', $this->attr(['utm_source' => 'google', 'utm_campaign' => 'other']))
            ->post(route('customer.wholesale.quote.confirm', $quote));

        $order = Order::where('enquiry_id', $enquiry->id)->first();
        $this->assertNotNull($order, 'order should be created from the quote');
        $this->assertSame('facebook', $order->utm_source);
        $this->assertSame('wholesale_comment', $order->utm_campaign);
    }

    // ── D) redirect ─────────────────────────────────────────────────────────
    public function test_products_redirect_keeps_whitelisted_params(): void
    {
        $this->get('/products?mode=wholesale&utm_source=facebook&utm_campaign=x')
            ->assertStatus(302)->assertRedirect('/?mode=wholesale&utm_source=facebook&utm_campaign=x#products');
    }

    public function test_products_redirect_drops_unknown_params_and_maps_paykari(): void
    {
        $this->get('/products?mode=paykari&foo=bar&fbclid=AbC_1&category=spice&utm_medium=a%20b')
            ->assertStatus(302)->assertRedirect('/?mode=wholesale&utm_medium=a+b&fbclid=AbC_1&category=spice#products');
        $this->get('/products?mode=bogus&evil=1')->assertRedirect('/#products');
        $this->get('/products?tab=retail')->assertRedirect('/?mode=retail#products');
    }

    // ── E) SEO regression ───────────────────────────────────────────────────
    public function test_canonical_and_og_url_never_contain_utm(): void
    {
        $product = $this->wholesaleProduct();
        foreach (['/', '/products/cumin', '/faq'] as $path) {
            $html = $this->get($path.'?utm_source=facebook&utm_campaign=x&fbclid=zzz')->assertOk()->getContent();
            preg_match_all('/<link rel="canonical" href="([^"]*)"/', $html, $c);
            preg_match_all('/<meta property="og:url" content="([^"]*)"/', $html, $o);
            foreach (array_merge($c[1], $o[1]) as $url) {
                $this->assertStringNotContainsString('utm_', $url);
                $this->assertStringNotContainsString('fbclid', $url);
            }
        }
    }
}
