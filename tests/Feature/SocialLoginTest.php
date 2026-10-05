<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\WebsiteSetting;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        config([
            'services.google.client_id' => 'gid', 'services.google.client_secret' => 'gsecret',
            'services.facebook.client_id' => null, 'services.facebook.client_secret' => null,
        ]);
    }

    private function fakeGoogle(string $id, ?string $email, string $name = 'Rahim Uddin'): void
    {
        $social = (new SocialUser())->map(['id' => $id, 'name' => $name, 'email' => $email, 'avatar' => 'https://x.test/a.png']);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($social);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_buttons_show_only_for_configured_and_enabled_providers(): void
    {
        $this->get(route('customer.login'))->assertOk()
            ->assertSee('data-social-login="google"', false)
            ->assertDontSee('data-social-login="facebook"', false);
        // The quick enquiry popup offers it to guests too.
        $this->get('/?mode=wholesale')->assertOk()->assertSee('msQuickEnquirySocial(this)', false);

        WebsiteSetting::updateOrCreate(['key' => 'customer_google_login'], ['value' => '0']);
        $this->get(route('customer.login'))->assertDontSee('data-social-login="google"', false);
        $this->get(route('customer.social.redirect', 'google'))->assertNotFound();
    }

    public function test_new_google_user_is_asked_for_phone_then_returned(): void
    {
        $this->fakeGoogle('g-1', 'rahim@gmail.com');

        $this->withSession(['social_login.redirect' => '/?mode=wholesale'])
            ->get(route('customer.social.callback', ['provider' => 'google', 'code' => 'abc']))
            ->assertRedirect(route('customer.social.phone'));

        $user = User::where('google_id', 'g-1')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->phone);

        $this->post(route('customer.social.phone.save'), ['mobile_number' => '01712345678'])
            ->assertRedirect('/?mode=wholesale');

        $this->assertSame('01712345678', $user->fresh()->phone);
        $this->assertNotNull($user->fresh()->customer);
    }

    public function test_existing_customer_is_linked_by_email_and_skips_phone_step(): void
    {
        $user = User::create(['name' => 'Old', 'email' => 'old@gmail.com', 'phone' => '01811111111',
            'password' => bcrypt('x'), 'role' => 'customer']);
        $this->fakeGoogle('g-2', 'old@gmail.com');

        $this->get(route('customer.social.callback', ['provider' => 'google', 'code' => 'abc']))->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-2', $user->fresh()->google_id);
    }

    public function test_admin_email_cannot_be_taken_over(): void
    {
        User::create(['name' => 'Admin', 'email' => 'boss@gmail.com', 'password' => bcrypt('x'), 'role' => 'admin', 'is_admin' => true]);
        $this->fakeGoogle('g-3', 'boss@gmail.com');

        $this->get(route('customer.social.callback', ['provider' => 'google', 'code' => 'abc']))->assertRedirect();
        $this->assertGuest();
    }

    public function test_phone_of_another_account_is_refused(): void
    {
        Customer::create(['name' => 'Someone', 'mobile_number' => '01799999999', 'is_active' => true]);
        $this->fakeGoogle('g-4', 'new@gmail.com');
        $this->get(route('customer.social.callback', ['provider' => 'google', 'code' => 'abc']));

        $this->post(route('customer.social.phone.save'), ['mobile_number' => '01799999999'])
            ->assertSessionHasErrors('mobile_number');
        $this->assertNull(User::where('google_id', 'g-4')->value('phone'));
    }

    public function test_cancelled_login_goes_back_to_login(): void
    {
        $this->get(route('customer.social.callback', ['provider' => 'google', 'error' => 'access_denied']))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertGuest();
    }
}
