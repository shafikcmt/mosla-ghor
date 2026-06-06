<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use App\Services\Otp\OtpChannelManager;
use App\Services\Otp\OtpProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorPhoneFirstAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->app->instance(OtpChannelManager::class, new class extends OtpChannelManager {
            public function for(string $channel): OtpProvider
            {
                return new class implements OtpProvider {
                    public function isConfigured(): bool
                    {
                        return true;
                    }

                    public function send(string $to, string $code, string $message): void
                    {
                        Cache::put('otp_test_code', $code, 120);
                    }
                };
            }
        });

        WebsiteSetting::query()->updateOrCreate(['key' => 'vendor_registration_enabled'], ['value' => '1']);
    }

    private function registerVendor(array $overrides = []): array
    {
        return array_merge([
            'shop_name'             => 'Test Shop',
            'owner_name'            => 'Test Owner',
            'phone'                 => '01712345678',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    public function test_vendor_can_register_phone_first_without_email(): void
    {
        $this->post(route('vendor.register.post'), $this->registerVendor())
            ->assertRedirect(route('vendor.dashboard'));

        $user = User::where('phone', '01712345678')->where('role', 'vendor')->first();
        $this->assertNotNull($user);
        $this->assertStringEndsWith('@mosla.local', $user->email); // placeholder minted
        $this->assertDatabaseHas('vendors', ['phone' => '01712345678', 'user_id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_normalises_phone(): void
    {
        $this->post(route('vendor.register.post'), $this->registerVendor(['phone' => '+8801712345678']));

        $this->assertDatabaseHas('vendors', ['phone' => '01712345678']);
        $this->assertDatabaseHas('users', ['phone' => '01712345678', 'role' => 'vendor']);
    }

    public function test_vendor_can_login_with_phone(): void
    {
        $this->post(route('vendor.register.post'), $this->registerVendor());
        $this->post(route('vendor.logout'));
        $this->assertGuest();

        $this->post(route('vendor.login.post'), ['identifier' => '01712345678', 'password' => 'secret123'])
            ->assertRedirect(route('vendor.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_legacy_vendor_without_user_phone_can_login_by_phone(): void
    {
        // Simulate an old vendor: user has email but no phone populated.
        $user = User::create([
            'name'     => 'Legacy Owner',
            'email'    => 'legacy@example.com',
            'password' => Hash::make('secret123'),
            'role'     => 'vendor',
            'is_admin' => false,
        ]);
        Vendor::create([
            'user_id'   => $user->id,
            'shop_name' => 'Legacy Shop',
            'slug'      => 'legacy-shop',
            'owner_name' => 'Legacy Owner',
            'phone'     => '01799999999',
            'email'     => 'legacy@example.com',
            'status'    => 'approved',
            'is_active' => true,
        ]);

        $this->post(route('vendor.login.post'), ['identifier' => '01799999999', 'password' => 'secret123'])
            ->assertRedirect(route('vendor.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_vendor_can_still_login_with_email(): void
    {
        $this->post(route('vendor.register.post'), $this->registerVendor(['email' => 'shop@example.com']));
        $this->post(route('vendor.logout'));

        $this->post(route('vendor.login.post'), ['identifier' => 'shop@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('vendor.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_vendor_otp_login_flow(): void
    {
        WebsiteSetting::query()->updateOrCreate(['key' => 'vendor_otp_login'], ['value' => '1']);
        WebsiteSetting::query()->updateOrCreate(['key' => 'otp_sms_enabled'], ['value' => '1']);

        $this->post(route('vendor.register.post'), $this->registerVendor());
        $this->post(route('vendor.logout'));

        $this->post(route('vendor.login.otp.send'), ['identifier' => '01712345678'])
            ->assertRedirect(route('vendor.login.otp.verify'));

        $code = Cache::get('otp_test_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->post(route('vendor.login.otp.verify.post'), ['code' => $code])
            ->assertRedirect(route('vendor.dashboard'));
        $this->assertAuthenticated();
    }
}
