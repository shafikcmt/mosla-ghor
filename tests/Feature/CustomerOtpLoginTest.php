<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\Otp\OtpChannelManager;
use App\Services\Otp\OtpProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Swap in a provider that captures the plain code instead of delivering.
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
    }

    private function enableOtp(): void
    {
        WebsiteSetting::query()->updateOrCreate(['key' => 'customer_otp_login'], ['value' => '1']);
        WebsiteSetting::query()->updateOrCreate(['key' => 'otp_sms_enabled'], ['value' => '1']);
    }

    private function customer(string $phone = '01712345678'): User
    {
        return User::create([
            'name'     => 'Test Customer',
            'phone'    => $phone,
            'password' => Hash::make('secret123'),
            'role'     => 'customer',
            'is_admin' => false,
        ]);
    }

    /**
     * Run the OTP flow the way the form does (?redirect= rides on the send URL)
     * and return the verify response.
     */
    private function otpLogin(?string $redirect = null)
    {
        $this->enableOtp();
        $user = $this->customer();

        // Request a code.
        $sendUrl = route('customer.login.otp.send', $redirect !== null ? ['redirect' => $redirect] : []);
        $this->post($sendUrl, ['identifier' => '01712345678'])
            ->assertRedirect(route('customer.login.otp.verify'));

        $code = Cache::get('otp_test_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // Verify it → logged in.
        $response = $this->post(route('customer.login.otp.verify.post'), ['code' => $code]);
        $this->assertAuthenticatedAs($user);

        return $response;
    }

    // Post-auth destination is ecommerce-friendly (78f3752): never the forced dashboard.
    public function test_full_otp_login_flow_without_redirect_goes_home(): void
    {
        $this->otpLogin()
            ->assertRedirect(url('/'))
            ->assertSessionHas('success', 'সফলভাবে লগইন হয়েছে।');
    }

    public function test_otp_login_returns_to_safe_redirect(): void
    {
        $this->otpLogin('/products/whole-cumin?mode=retail')
            ->assertRedirect(url('/products/whole-cumin?mode=retail'));
    }

    public function test_otp_login_ignores_external_redirects(): void
    {
        $this->otpLogin('https://evil.com/phish')->assertRedirect(url('/'));
    }

    public function test_otp_login_ignores_protocol_relative_redirects(): void
    {
        $this->otpLogin('//evil.com/phish')->assertRedirect(url('/'));
    }

    public function test_unknown_identifier_is_rejected_without_sending(): void
    {
        $this->enableOtp();

        $this->from(route('customer.login.otp'))
            ->post(route('customer.login.otp.send'), ['identifier' => '01900000000'])
            ->assertRedirect(route('customer.login.otp'))
            ->assertSessionHasErrors('identifier');

        $this->assertNull(Cache::get('otp_test_code'));
        $this->assertGuest();
    }

    public function test_wrong_code_does_not_authenticate(): void
    {
        $this->enableOtp();
        $this->customer();

        $this->post(route('customer.login.otp.send'), ['identifier' => '01712345678']);

        $this->post(route('customer.login.otp.verify.post'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_otp_routes_redirect_to_password_login_when_disabled(): void
    {
        WebsiteSetting::query()->updateOrCreate(['key' => 'customer_otp_login'], ['value' => '0']);

        $this->get(route('customer.login.otp'))->assertRedirect(route('customer.login'));
    }
}
