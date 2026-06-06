<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use App\Support\AuthSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function admin(): User
    {
        return User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'role'     => 'admin',
            'is_admin' => true,
        ]);
    }

    /** A full valid payload; individual tests override what they exercise. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'otp_expiry_minutes'          => '5',
            'otp_resend_cooldown_seconds' => '60',
            'otp_max_attempts'            => '5',
        ], $overrides);
    }

    public function test_index_renders_for_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.auth-settings.index'))
            ->assertOk()
            ->assertSee('OTP / মোবাইল সেটিং');
    }

    public function test_saving_persists_otp_settings(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auth-settings.update'), $this->payload([
                'customer_otp_login' => '1',
                'otp_sms_enabled'    => '1',
                'otp_email_enabled'  => '1',
                'otp_expiry_minutes' => '8',
                'otp_max_attempts'   => '4',
            ]))
            ->assertRedirect(route('admin.auth-settings.index'));

        $this->assertTrue(AuthSettings::customerOtpLogin());
        $this->assertTrue(AuthSettings::smsEnabled());
        $this->assertSame(['sms', 'email'], AuthSettings::enabledChannels());
        $this->assertSame(8, AuthSettings::otpExpiryMinutes());
        $this->assertSame(4, AuthSettings::otpMaxAttempts());
    }

    public function test_unchecked_box_is_stored_as_off(): void
    {
        WebsiteSetting::query()->updateOrCreate(['key' => 'otp_sms_enabled'], ['value' => '1']);

        // Submit without otp_sms_enabled → it must flip to '0'.
        $this->actingAs($this->admin())
            ->post(route('admin.auth-settings.update'), $this->payload());

        $this->assertFalse(AuthSettings::smsEnabled());
    }

    public function test_unrendered_keys_are_not_clobbered(): void
    {
        // vendor_approval_required defaults to '1' and is NOT a form field;
        // saving the form must leave it untouched.
        WebsiteSetting::query()->updateOrCreate(['key' => 'vendor_approval_required'], ['value' => '1']);

        $this->actingAs($this->admin())
            ->post(route('admin.auth-settings.update'), $this->payload());

        $this->assertSame('1', WebsiteSetting::get('vendor_approval_required', '1'));
        $this->assertTrue(AuthSettings::vendorApprovalRequired());
    }

    public function test_numeric_bounds_are_validated(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.auth-settings.index'))
            ->post(route('admin.auth-settings.update'), $this->payload(['otp_expiry_minutes' => '0']))
            ->assertRedirect(route('admin.auth-settings.index'))
            ->assertSessionHasErrors('otp_expiry_minutes');
    }

    public function test_requires_admin(): void
    {
        $this->get(route('admin.auth-settings.index'))->assertRedirect(route('admin.login'));
    }
}
