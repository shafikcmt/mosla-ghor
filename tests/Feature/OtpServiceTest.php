<?php

namespace Tests\Feature;

use App\Models\AuthOtp;
use App\Services\Otp\OtpChannelManager;
use App\Services\Otp\OtpProvider;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpThrottleException;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Capturing provider so tests can read the plain code that was "sent". */
    private function service(): array
    {
        $captured = (object) ['to' => null, 'code' => null, 'message' => null];

        $provider = new class($captured) implements OtpProvider {
            public function __construct(private object $captured)
            {
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function send(string $to, string $code, string $message): void
            {
                $this->captured->to = $to;
                $this->captured->code = $code;
                $this->captured->message = $message;
            }
        };

        $manager = new class($provider) extends OtpChannelManager {
            public function __construct(private OtpProvider $provider)
            {
            }

            public function for(string $channel): OtpProvider
            {
                return $this->provider;
            }
        };

        return [new OtpService($manager), $captured];
    }

    public function test_issue_persists_hashed_code_and_dispatches(): void
    {
        [$service, $captured] = $this->service();

        // Raw form with +880 prefix → canonical local 01XXXXXXXXX.
        $otp = $service->issue('+8801712345678', 'login', 'sms', 'customer', ['ip' => '127.0.0.1']);

        // Normalised identifier stored, type detected, code hashed (not plain).
        $this->assertSame('01712345678', $otp->identifier);
        $this->assertSame('phone', $otp->identifier_type);
        $this->assertNotSame($captured->code, $otp->otp_hash);
        $this->assertNull($otp->verified_at);

        // The plain code reached the provider and is 6 digits.
        $this->assertMatchesRegularExpression('/^\d{6}$/', $captured->code);
        $this->assertDatabaseCount('auth_otps', 1);
    }

    public function test_correct_code_verifies_once(): void
    {
        [$service, $captured] = $this->service();

        $service->issue('01712345678', 'login', 'sms');

        $this->assertTrue($service->verify('01712345678', 'login', $captured->code));
        // Single-use: the same code cannot verify again (now marked verified).
        $this->assertFalse($service->verify('01712345678', 'login', $captured->code));
    }

    public function test_wrong_code_burns_attempts_and_locks(): void
    {
        WebsiteSetting::query()->updateOrCreate(['key' => 'otp_max_attempts'], ['value' => '3']);

        [$service] = $this->service();
        $service->issue('01712345678', 'login', 'sms');

        $this->assertFalse($service->verify('01712345678', 'login', '000000'));
        $this->assertFalse($service->verify('01712345678', 'login', '000000'));
        $this->assertFalse($service->verify('01712345678', 'login', '000000'));

        $otp = AuthOtp::first();
        $this->assertTrue($otp->isLocked());
    }

    public function test_resend_inside_cooldown_throws(): void
    {
        WebsiteSetting::query()->updateOrCreate(['key' => 'otp_resend_cooldown_seconds'], ['value' => '60']);

        [$service] = $this->service();
        $service->issue('01712345678', 'login', 'sms');

        $this->expectException(OtpThrottleException::class);
        $service->issue('01712345678', 'login', 'sms');
    }

    public function test_email_identifier_is_lowercased_and_typed(): void
    {
        [$service, $captured] = $this->service();

        $otp = $service->issue('User@Example.COM', 'login', 'email');

        $this->assertSame('user@example.com', $otp->identifier);
        $this->assertSame('email', $otp->identifier_type);
        $this->assertTrue($service->verify('user@example.com', 'login', $captured->code));
    }
}
