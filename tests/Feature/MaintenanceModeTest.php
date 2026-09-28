<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function maintenance(array $settings = []): void
    {
        foreach (array_replace([
            'maintenance_enabled' => '1', 'maintenance_mode' => 'full',
            'maintenance_title' => 'ওয়েবসাইটের কাজ চলছে', 'maintenance_message' => 'শীঘ্রই ফিরছি',
        ], $settings) as $key => $value) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function vendorUser(): User
    {
        $user = User::factory()->create(['role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'shop_name' => 'QA shop', 'slug' => 'qa-shop', 'owner_name' => 'Owner',
            'phone' => '01700000000', 'email' => 'qa@example.com', 'status' => 'approved', 'is_active' => true]);
        return $user;
    }

    /** Minimal order with an active invoice token; fills whatever NOT NULL columns the schema has. */
    private function orderWithInvoice(): Order
    {
        $row = ['invoice_token' => Str::random(40), 'order_number' => 'MM-TEST-1', 'created_at' => now(), 'updated_at' => now()];
        foreach (Schema::getColumns('orders') as $col) {
            if ($col['nullable'] || $col['default'] !== null || $col['auto_increment'] || array_key_exists($col['name'], $row)) {
                continue;
            }
            $type = strtolower($col['type_name']);
            $row[$col['name']] = match (true) {
                str_contains($type, 'int'), str_contains($type, 'dec'), str_contains($type, 'num'),
                str_contains($type, 'float'), str_contains($type, 'double'), str_contains($type, 'real') => 0,
                str_contains($type, 'date'), str_contains($type, 'time') => now(),
                default => 'test',
            };
        }
        $row['mobile_number'] = '01712345678';
        return Order::findOrFail(DB::table('orders')->insertGetId($row));
    }

    public function test_off_keeps_site_unchanged(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Maintenance চালু আছে')->assertDontSee('data-mn-banner', false);
    }

    public function test_full_mode_shows_503_notice_with_retry_after_and_noindex(): void
    {
        $this->maintenance(['maintenance_contact' => '01711111111']);
        $this->get('/')->assertStatus(503)
            ->assertHeader('Retry-After', '3600')
            ->assertSee('ওয়েবসাইটের কাজ চলছে')->assertSee('শীঘ্রই ফিরছি')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('https://wa.me/8801711111111', false);
        $this->get('/checkout/review')->assertStatus(503);
        $this->get('/login')->assertStatus(503);
        $this->get('/account')->assertStatus(503);
    }

    public function test_full_mode_retry_after_follows_end_time_and_expired_hides_countdown(): void
    {
        $this->maintenance(['maintenance_until' => now('Asia/Dhaka')->addHours(2)->format('Y-m-d H:i')]);
        $response = $this->get('/')->assertStatus(503)->assertSee('data-mn-until', false);
        $this->assertEqualsWithDelta(7200, (int) $response->headers->get('Retry-After'), 120);

        $this->maintenance(['maintenance_until' => now('Asia/Dhaka')->subHour()->format('Y-m-d H:i')]);
        $this->get('/')->assertStatus(503)->assertHeader('Retry-After', '3600')->assertDontSee('data-mn-until', false);
    }

    public function test_full_mode_json_requests_get_503_json(): void
    {
        $this->maintenance();
        $this->getJson('/wholesale/variants')->assertStatus(503)->assertJson(['maintenance' => true, 'message' => 'শীঘ্রই ফিরছি']);
    }

    public function test_full_mode_admin_panel_and_login_keep_working(): void
    {
        $this->maintenance();
        $this->get('/admin/login')->assertOk();
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->assertSee('Maintenance চালু');
    }

    public function test_full_mode_admin_sees_real_storefront_with_admin_bar(): void
    {
        $this->maintenance();
        $this->actingAs($this->admin())->get('/')->assertOk()
            ->assertSee('Maintenance চালু আছে')->assertSee('আসল অর্ডার হবে (স্টক কমবে)');
    }

    public function test_full_mode_track_order_and_invoice_read_only(): void
    {
        $this->maintenance();
        $order = $this->orderWithInvoice();

        $this->get('/track-order')->assertOk();
        $this->post('/track-order', ['order_number' => 'X', 'phone' => '01700000000'])->assertOk();
        $this->get('/invoice/'.$order->invoice_token)->assertOk();

        $this->get('/invoice/'.$order->invoice_token.'/pay')->assertStatus(503);
        $this->post('/invoice/'.$order->invoice_token.'/pay', ['amount' => 100])->assertStatus(503)
            ->assertSee('মেইনটেন্যান্স চলাকালীন এই কাজটি করা যাবে না');
        $this->post('/invoice/'.$order->invoice_token.'/reorder')->assertStatus(503);
    }

    public function test_full_mode_vendor_allowed_by_default_and_blocked_when_enabled(): void
    {
        $this->maintenance();
        $vendor = $this->vendorUser();
        $this->actingAs($vendor)->get('/vendor/dashboard')->assertOk();

        $this->maintenance(['maintenance_block_vendors' => '1']);
        $this->actingAs($vendor)->get('/vendor/dashboard')->assertStatus(503)->assertSee('মার্চেন্ট প্যানেল');
        auth()->logout();
        $this->get('/vendor/login')->assertOk();
        $this->get('/vendor/register')->assertStatus(503);
    }

    public function test_allowed_ip_passes(): void
    {
        $this->maintenance(['maintenance_allowed_ips' => '203.0.113.7, 198.51.100.1']);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->get('/')->assertOk()->assertDontSee('Maintenance চালু আছে');
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])->get('/')->assertStatus(503);
    }

    public function test_banner_mode_shows_banner_and_site_works(): void
    {
        $this->maintenance(['maintenance_mode' => 'banner']);
        $this->get('/')->assertOk()->assertSee('data-mn-banner', false)->assertSee('শীঘ্রই ফিরছি');
        $this->get('/checkout/review')->assertDontSee('অর্ডার সাময়িকভাবে বন্ধ আছে');
    }

    public function test_banner_with_block_orders_stops_orders_but_not_for_admin(): void
    {
        $this->maintenance(['maintenance_mode' => 'banner', 'maintenance_block_orders' => '1']);
        $before = Order::count();

        $this->postJson('/order', ['customer_name' => 'A'])->assertStatus(503)
            ->assertJson(['message' => 'অর্ডার সাময়িকভাবে বন্ধ আছে। শীঘ্রই ফিরছি']);
        $this->get('/checkout/review')->assertStatus(503)->assertSee('অর্ডার সাময়িকভাবে বন্ধ আছে');
        $this->assertSame($before, Order::count());

        // Admin test orders pass the maintenance check (normal validation applies).
        $this->actingAs($this->admin())->postJson('/order', [])->assertStatus(422);
    }

    public function test_health_check_always_ok(): void
    {
        $this->maintenance();
        $this->get('/up')->assertOk();
    }

    public function test_customer_logout_allowed_in_full_mode(): void
    {
        $this->maintenance();
        $customer = User::factory()->create(['role' => 'customer', 'is_admin' => false]);
        $this->actingAs($customer)->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_missing_config_falls_back_to_safe_defaults(): void
    {
        $this->maintenance();
        config(['maintenance' => null]); // e.g. stale config:cache without config/maintenance.php

        $this->get('/')->assertStatus(503);
        $this->get('/track-order')->assertOk();
        $this->get('/up')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_is_never_blocked_even_if_config_omits_it(): void
    {
        $this->maintenance();
        config(['maintenance.always_allowed' => ['up']]);

        $this->get('/admin/login')->assertOk();
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk();
    }

    public function test_errors_in_the_check_fail_open_and_are_logged(): void
    {
        $this->maintenance();
        config(['maintenance.always_allowed' => 'broken-not-an-array']);
        \Illuminate\Support\Facades\Log::spy();

        $this->get('/')->assertOk();

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg, $ctx) => $msg === 'Maintenance check failed; request allowed' && $ctx['path'] === '/');
    }

    public function test_admin_saves_settings_validates_and_previews(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.maintenance.update'), [
            'maintenance_enabled' => 1, 'maintenance_mode' => 'banner', 'maintenance_title' => 'কাজ চলছে',
            'maintenance_until' => '2030-01-01T10:30', 'maintenance_allowed_ips' => "203.0.113.7\n198.51.100.1",
        ])->assertRedirect(route('admin.website-settings.index').'#maintenance');

        $this->assertSame('1', WebsiteSetting::get('maintenance_enabled'));
        $this->assertSame('2030-01-01 10:30', WebsiteSetting::get('maintenance_until'));
        $this->assertSame('203.0.113.7, 198.51.100.1', WebsiteSetting::get('maintenance_allowed_ips'));

        $this->actingAs($admin)->post(route('admin.maintenance.update'), [
            'maintenance_mode' => 'full', 'maintenance_allowed_ips' => '999.1.1.1',
        ])->assertSessionHasErrorsIn('maintenance', ['maintenance_allowed_ips' => 'IP ঠিকানা সঠিক নয়: 999.1.1.1']);

        $this->actingAs($admin)->get(route('admin.website-settings.index'))->assertOk()->assertSee('মেইনটেন্যান্স / নোটিশ মোড');
        $this->actingAs($admin)->get(route('admin.maintenance.preview'))->assertOk()->assertSee('কাজ চলছে')->assertSee('প্রিভিউ');
    }
}
