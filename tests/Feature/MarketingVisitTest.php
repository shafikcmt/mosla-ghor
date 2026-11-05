<?php

namespace Tests\Feature;

use App\Models\{BdDivision, BdDistrict, Customer, DeliveryZone, MarketingSetting, MarketingVisit, Order, PaykariComboEnquiry, PriceSetting, Product, User, WebsiteSetting, WholesaleEnquiry, WholesaleQuote};
use App\Support\VisitLogger;
use Tests\TestCase;

class MarketingVisitTest extends TestCase
{
    private const UA = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';
    private const VID = '3f2b8c1e-5a4d-4e6f-9b7a-1c2d3e4f5a6b';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        MarketingSetting::current(); // MetaPixel::trackingAllowed() fails closed without the row
    }

    private function land(string $query = 'utm_source=facebook&utm_medium=automation&utm_campaign=wholesale_comment', string $path = '/', ?string $ua = self::UA)
    {
        return $this->withHeader('User-Agent', $ua ?? '')->get($path.'?'.$query);
    }

    private function attr(): string
    {
        return json_encode(['utm_source' => 'facebook', 'utm_medium' => 'automation', 'utm_campaign' => 'wholesale_comment', 'landing_path' => '/', 'referrer_host' => null]);
    }

    // ── G) visit logging ────────────────────────────────────────────────────
    public function test_utm_landing_creates_one_row_and_sets_visitor_cookie(): void
    {
        $r = $this->land('utm_source=facebook&utm_campaign=spring&fbclid=SECRET1', '/faq')->assertOk();
        $this->assertSame(1, MarketingVisit::count());
        $v = MarketingVisit::first();
        $this->assertSame('facebook', $v->utm_source);
        $this->assertSame('spring', $v->utm_campaign);
        $this->assertSame('/faq', $v->landing_path);
        $this->assertSame('mobile', $v->device);
        $vid = $r->getCookie('ms_vid')->getValue();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $vid);
        $this->assertSame($vid, $v->visitor_id);
        $this->assertTrue($r->getCookie('ms_vid', false)->isHttpOnly());
        // nothing identifying is stored
        $this->assertSame(['id', 'visitor_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'landing_path', 'referrer_host', 'device', 'created_at'], array_keys($v->getAttributes()));
        $this->assertStringNotContainsString('SECRET1', json_encode($v->getAttributes()));
    }

    public function test_same_visitor_and_campaign_within_30_minutes_is_not_logged_twice(): void
    {
        $this->land();
        $this->withCookie('ms_vid', self::VID)->withHeader('User-Agent', self::UA)->get('/?utm_source=facebook&utm_campaign=wholesale_comment');
        $this->withCookie('ms_vid', self::VID)->withHeader('User-Agent', self::UA)->get('/?utm_source=facebook&utm_campaign=wholesale_comment');
        $this->assertSame(2, MarketingVisit::count()); // first visitor (new id) + VID once

        $this->withCookie('ms_vid', self::VID)->withHeader('User-Agent', self::UA)->get('/?utm_source=facebook&utm_campaign=other');
        $this->assertSame(3, MarketingVisit::count()); // another campaign is a new row
    }

    public function test_normal_page_view_without_utm_logs_nothing(): void
    {
        $this->withHeader('User-Agent', self::UA)->get('/')->assertOk()->assertCookieMissing('ms_vid');
        $this->withHeader('User-Agent', self::UA)->get('/?fbclid=x')->assertOk();
        $this->assertSame(0, MarketingVisit::count());
    }

    public function test_bots_and_link_previews_are_not_logged(): void
    {
        foreach (['facebookexternalhit/1.1', 'meta-externalagent/1.1', 'WhatsApp/2.23', 'TelegramBot (like TwitterBot)', 'Googlebot/2.1', 'Mozilla/5.0 HeadlessChrome', 'Lighthouse', 'SomeLinkPreview', ''] as $ua) {
            $this->land('utm_source=facebook&utm_campaign=bots', '/', $ua);
        }
        $this->assertSame(0, MarketingVisit::count());
        $this->assertTrue(VisitLogger::isBot(null));
        $this->assertFalse(VisitLogger::isBot(self::UA));
    }

    public function test_admin_api_ajax_and_excluded_visitors_are_not_logged(): void
    {
        $this->land('utm_source=facebook', '/admin/login');
        $this->withHeader('User-Agent', self::UA)->getJson('/api/x?utm_source=facebook');
        $this->withHeaders(['User-Agent' => self::UA, 'X-Requested-With' => 'XMLHttpRequest'])->get('/?utm_source=facebook');
        $this->assertSame(0, MarketingVisit::count());

        config(['services.meta.exclude_ips' => ['127.0.0.1']]);
        $this->land();
        $this->assertSame(0, MarketingVisit::count());
        config(['services.meta.exclude_ips' => []]);

        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->actingAs($admin)->withHeader('User-Agent', self::UA)->get('/?utm_source=facebook');
        $this->assertSame(0, MarketingVisit::count());
    }

    public function test_toggle_off_logs_nothing_but_attribution_cookie_still_works(): void
    {
        WebsiteSetting::updateOrCreate(['key' => 'visit_logging_enabled'], ['value' => '0']);
        $r = $this->land()->assertCookie('ms_attr')->assertCookieMissing('ms_vid');
        $this->assertSame(0, MarketingVisit::count());
    }

    public function test_toggle_is_saved_from_marketing_settings_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), ['platform_pixel_scope' => 'all', 'visit_logging_enabled' => '0'])->assertSessionHasNoErrors();
        $this->assertSame('0', WebsiteSetting::get('visit_logging_enabled'));
        $this->actingAs($admin)->get(route('admin.marketing-settings.index'))->assertOk()->assertSee('ভিজিট পরিসংখ্যান সংরক্ষণ')->assertSee('পুরোনো ভিজিট মুছুন');
        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), ['platform_pixel_scope' => 'all', 'visit_logging_enabled' => '1']);
        $this->assertSame('1', WebsiteSetting::get('visit_logging_enabled'));
    }

    // ── H) visitor_id on conversions ────────────────────────────────────────
    private function wholesaleProduct(): Product
    {
        return Product::create(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500, 'is_active' => true,
            'show_in_retail' => false, 'show_in_wholesale' => true, 'min_order_quantity' => 10, 'min_order_unit' => 'kg']);
    }

    public function test_enquiries_and_orders_carry_visitor_id(): void
    {
        $product = $this->wholesaleProduct();
        $this->withCredentials()->withCookie('ms_attr', $this->attr())->withCookie('ms_vid', self::VID)
            ->postJson(route('products.enquiry.store', 'cumin'), ['customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 25])->assertCreated();
        $this->assertSame(self::VID, WholesaleEnquiry::sole()->visitor_id);

        $user = User::factory()->create(['role' => 'customer', 'phone' => '01722222222']);
        Customer::create(['name' => 'Karim', 'mobile_number' => '01722222222', 'is_active' => true]);
        $this->actingAs($user)->withCookie('ms_attr', $this->attr())->withCookie('ms_vid', self::VID)->post(route('paykari-combo.enquiry.store'), [
            'items' => [['product_id' => $product->id, 'quantity_kg' => 10]], 'delivery_location' => 'Dhaka',
            'customer_name' => 'Karim', 'customer_phone' => '01722222222',
        ])->assertSessionHasNoErrors();
        $this->assertSame(self::VID, PaykariComboEnquiry::sole()->visitor_id);

        // invalid ids are ignored
        $this->withCredentials()->withCookie('ms_vid', 'not-a-uuid')
            ->postJson(route('products.enquiry.store', 'cumin'), ['customer_name' => 'Rahim', 'customer_phone' => '01712345679', 'quantity_kg' => 25])->assertCreated();
        $this->assertNull(WholesaleEnquiry::latest('id')->first()->visitor_id);
    }

    public function test_retail_order_carries_visitor_id(): void
    {
        $division = BdDivision::create(['source_id' => '1', 'name' => 'D', 'bn_name' => 'D', 'is_active' => true]);
        $district = BdDistrict::create(['source_id' => '1', 'division_id' => $division->id, 'name' => 'X', 'bn_name' => 'X', 'is_active' => true]);
        $zone = DeliveryZone::create(['zone_name' => 'Dhaka City', 'zone_type' => 'inside_dhaka', 'delivery_charge' => 60, 'is_active' => true]);
        $location = $zone->locations()->create(['location_name' => 'City', 'is_active' => true]);
        PriceSetting::current()->fill(['default_packaging_cost' => 10, 'minimum_order_amount' => 0])->save();
        $product = Product::create(['name_bn' => 'Cumin', 'slug' => 'o-cumin', 'stock' => 100, 'retail_price_1kg' => 500, 'is_active' => true, 'show_in_retail' => true]);
        $product->syncPrices();
        $price = $product->prices()->where('sell_type', 'retail')->firstOrFail();

        $this->withCredentials()->withCookie('ms_attr', $this->attr())->withCookie('ms_vid', self::VID)->postJson(route('order.store'), [
            'full_name' => 'Buyer', 'mobile_number' => '01712345678', 'full_address' => 'House 1',
            'bd_division_id' => $division->id, 'bd_district_id' => $district->id,
            'delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id,
            'payment_method' => 'cash_on_delivery', 'items' => [['price_id' => $price->id]],
        ])->assertOk();
        $o = Order::sole();
        $this->assertSame(self::VID, $o->visitor_id);
        $this->assertSame('facebook', $o->utm_source);
    }

    public function test_quote_created_order_inherits_visitor_id(): void
    {
        $this->wholesaleProduct();
        $this->withCredentials()->withCookie('ms_attr', $this->attr())->withCookie('ms_vid', self::VID)
            ->postJson(route('products.enquiry.store', 'cumin'), ['customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 30])->assertCreated();
        $enquiry = WholesaleEnquiry::sole();
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = \App\Models\Vendor::create(['user_id' => $vendorUser->id, 'shop_name' => 'Shop', 'slug' => 'shop', 'owner_name' => 'Owner',
            'phone' => '01700000000', 'email' => 'v@example.com', 'status' => 'approved', 'is_active' => true]);
        $quote = WholesaleQuote::create(['enquiry_id' => $enquiry->id, 'customer_id' => $enquiry->customer_id, 'vendor_id' => $vendor->id,
            'unit_price' => 850, 'quantity' => 30, 'quantity_unit' => 'kg', 'subtotal' => 25500, 'delivery_charge' => 0,
            'status' => 'sent_to_customer', 'valid_until' => now()->addDays(7)]);

        $this->actingAs($enquiry->customer->user)->withCookie('ms_vid', '11111111-2222-4333-8444-555555555555')
            ->post(route('customer.wholesale.quote.confirm', $quote));
        $order = Order::where('enquiry_id', $enquiry->id)->firstOrFail();
        $this->assertSame(self::VID, $order->visitor_id);
        $this->assertSame('facebook', $order->utm_source);
    }

    // ── I) report ───────────────────────────────────────────────────────────
    private function order(array $extra): Order
    {
        static $n = 0;
        return Order::create($extra + [
            'order_number' => 'R-'.(++$n), 'customer_name' => 'X', 'mobile_number' => '01700000000', 'full_address' => 'Dhaka',
            'district' => 'Dhaka', 'area' => 'Dhaka', 'subtotal' => 100, 'packaging_cost' => 0, 'delivery_charge' => 0, 'grand_total' => 100,
            'payment_method' => 'cash_on_delivery', 'order_status' => 'pending',
        ]);
    }

    public function test_report_aggregates_visits_orders_revenue_and_conversion(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $fb = ['utm_source' => 'facebook', 'utm_campaign' => 'wholesale_comment'];
        foreach (['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc'] as $vid) {
            MarketingVisit::create($fb + ['visitor_id' => $vid, 'landing_path' => '/', 'device' => 'mobile', 'created_at' => now()->subDay()]);
        }
        MarketingVisit::create(['utm_source' => 'google', 'utm_campaign' => 'brand', 'visitor_id' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'landing_path' => '/', 'device' => 'desktop', 'created_at' => now()->subDay()]);
        MarketingVisit::create($fb + ['visitor_id' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'landing_path' => '/', 'device' => 'mobile', 'created_at' => now()->subDays(60)]); // out of range

        $this->order($fb + ['grand_total' => 500]);
        $this->order($fb + ['grand_total' => 300]);
        $this->order($fb + ['grand_total' => 999, 'order_status' => 'cancelled']);
        $this->order($fb + ['grand_total' => 777])->delete(); // soft-deleted
        $this->order(['grand_total' => 200]);                 // untagged -> direct

        $customer = Customer::create(['name' => 'K', 'mobile_number' => '01733333333', 'is_active' => true]);
        $product = $this->wholesaleProduct();
        WholesaleEnquiry::create($fb + ['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity_kg' => 5, 'delivery_location' => 'Dhaka', 'business_type' => 'shop', 'customer_name' => 'K', 'customer_phone' => '01733333333', 'product_name' => 'জিরা', 'status' => 'pending']);
        PaykariComboEnquiry::create($fb + ['customer_id' => $customer->id, 'customer_name' => 'K', 'customer_phone' => '01733333333', 'delivery_location' => 'Dhaka', 'business_type' => 'shop', 'status' => 'pending']);

        $html = $this->actingAs($admin)->get(route('admin.marketing-report.index'))->assertOk()->assertSee('মার্কেটিং রিপোর্ট')->assertSee('সরাসরি / অজানা')->getContent();

        $rows = $this->actingAs($admin)->get(route('admin.marketing-report.index'))->viewData('rows');
        $facebook = $rows->firstWhere('source', 'facebook');
        $this->assertSame(3, $facebook['visitors']);        // distinct a, b, c
        $this->assertSame(4, $facebook['visits']);          // old visit excluded
        $this->assertSame(2, $facebook['orders']);          // cancelled + trashed excluded
        $this->assertEquals(800.0, $facebook['revenue']);
        $this->assertSame(1, $facebook['wholesale']);
        $this->assertSame(1, $facebook['combo']);
        $this->assertSame(133.3, $facebook['rate']);        // (2+1+1)/3
        $direct = $rows->firstWhere('direct', true);
        $this->assertSame(1, $direct['orders']);
        $this->assertNull($direct['rate']);
        $this->assertTrue($rows->last()['direct']);
        $this->assertSame(1, $rows->firstWhere('source', 'google')['visitors']);
    }

    public function test_report_empty_state_and_access_control(): void
    {
        $this->get(route('admin.marketing-report.index'))->assertRedirect(route('admin.login'));
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('admin.marketing-report.index'))->assertRedirect();

        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.marketing-report.index'))->assertOk()->assertSee('এই সময়ে কোনো ডাটা নেই');
        $this->actingAs($admin)->get(route('admin.marketing-report.index', ['from' => 'garbage', 'to' => '???']))->assertOk();
    }

    // ── J) retention ────────────────────────────────────────────────────────
    private function seedVisits(): void
    {
        foreach ([100, 95, 40, 1] as $i => $daysAgo) {
            MarketingVisit::create(['visitor_id' => self::VID, 'utm_source' => 'facebook', 'utm_campaign' => 'c'.$i, 'landing_path' => '/', 'device' => 'mobile', 'created_at' => now()->subDays($daysAgo)]);
        }
    }

    public function test_self_prune_removes_only_rows_older_than_90_days_and_respects_the_cap(): void
    {
        $this->seedVisits();
        $this->order(['grand_total' => 10]);
        $this->assertSame(2, VisitLogger::selfPrune());
        $this->assertSame(2, MarketingVisit::count());
        $this->assertSame(1, Order::count());

        MarketingVisit::query()->delete();
        for ($i = 0; $i < 7; $i++) {
            MarketingVisit::create(['visitor_id' => self::VID, 'landing_path' => '/', 'device' => 'mobile', 'created_at' => now()->subDays(200)]);
        }
        $this->assertSame(5, MarketingVisit::pruneOlderThan(90, 5));
        $this->assertSame(2, MarketingVisit::count());
    }

    public function test_admin_prune_button_deletes_only_old_visits(): void
    {
        $this->seedVisits();
        $this->order(['grand_total' => 10]);
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.marketing-settings.prune-visits'), ['days' => 5])->assertSessionHasErrors('days');
        $this->assertSame(4, MarketingVisit::count());

        $this->actingAs($admin)->post(route('admin.marketing-settings.prune-visits'), ['days' => 30])
            ->assertRedirect(route('admin.marketing-settings.index'))->assertSessionHas('success');
        $this->assertSame(['c3'], MarketingVisit::pluck('utm_campaign')->all()); // only the 1-day-old row remains
        $this->assertSame(1, Order::count());
    }

    public function test_non_admins_cannot_prune(): void
    {
        $this->seedVisits();
        $this->post(route('admin.marketing-settings.prune-visits'), ['days' => 30])->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['role' => 'customer']))->post(route('admin.marketing-settings.prune-visits'), ['days' => 30])->assertRedirect();
        $this->assertSame(4, MarketingVisit::count());
    }

    public function test_opening_the_report_prunes_old_visits_at_most_once_per_ten_minutes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $old = fn () => MarketingVisit::create(['visitor_id' => self::VID, 'landing_path' => '/', 'device' => 'mobile', 'created_at' => now()->subDays(120)]);
        $old();
        MarketingVisit::create(['visitor_id' => self::VID, 'landing_path' => '/', 'device' => 'mobile', 'created_at' => now()->subDay()]);

        $this->actingAs($admin)->get(route('admin.marketing-report.index'))->assertOk();
        $this->assertSame(1, MarketingVisit::count()); // 120-day-old row pruned, recent one kept

        $old();
        $this->actingAs($admin)->get(route('admin.marketing-report.index'))->assertOk();
        $this->assertSame(2, MarketingVisit::count()); // throttled: not pruned again within 10 minutes
    }

    public function test_pages_with_personal_or_transactional_content_are_never_tracked(): void
    {
        foreach (['invoice/5', 'wholesale-invoice/9', 'v/abc', 'v', 'order/MSL-1', 'checkout/review', 'account/orders', 'set-password/token', 'track-order', 'admin/x', 'sitemap.xml'] as $path) {
            $req = \Illuminate\Http\Request::create('/'.$path, 'GET', ['utm_source' => 'facebook']);
            $this->assertFalse(\App\Http\Middleware\CaptureMarketingSource::shouldTrack($req), $path);
        }
        foreach (['', 'faq', 'products/cumin', 'vendors-list', 'orders-info'] as $path) {
            $req = \Illuminate\Http\Request::create('/'.$path, 'GET', ['utm_source' => 'facebook']);
            $this->assertTrue(\App\Http\Middleware\CaptureMarketingSource::shouldTrack($req), $path);
        }
        $this->get('/track-order?utm_source=facebook')->assertCookieMissing('ms_attr');
        $this->assertSame(0, MarketingVisit::count());
    }

    public function test_privacy_page_mentions_visit_statistics(): void
    {
        $this->get(route('legal.privacy'))->assertOk()->assertSee('ভিজিট পরিসংখ্যান')->assertSee('সাধারণত প্রায় ৯০ দিন পর মুছে ফেলা হয়')->assertSee('normally deleted after about 90 days')
            ->assertSee('৭. পরিবর্তন');
    }
}
