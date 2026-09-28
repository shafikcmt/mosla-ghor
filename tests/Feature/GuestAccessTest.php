<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\WebsiteSetting;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->product = Product::create(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => true]);
        $this->product->syncPrices();
    }

    private function set(array $settings): void
    {
        foreach ($settings as $key => $value) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function packId(): int
    {
        return (int) $this->product->prices()->where('sell_type', 'retail')->value('id');
    }

    private function review(): array
    {
        return ['rating' => 5, 'comment' => 'চমৎকার', 'customer_name' => 'Guest'];
    }

    private function enquiry(): array
    {
        return ['customer_name' => 'Guest', 'customer_phone' => '01712345678', 'delivery_location' => 'Dhaka', 'quantity_kg' => 50];
    }

    public function test_defaults_keep_todays_guest_behaviour(): void
    {
        $this->post(route('checkout.start'), ['items' => [$this->packId()]])->assertRedirect(route('checkout.review'));
        $this->get(route('checkout.review'))->assertOk();

        // Reaches the controller (validation runs) instead of a login redirect. A full guest insert is
        // not asserted: product_reviews.user_id/order_id are only made nullable on MySQL (see migration).
        $this->from('/products/cumin')->post(route('products.reviews.store', 'cumin'), ['rating' => 5])
            ->assertRedirect('/products/cumin')->assertSessionHasErrors('comment');

        $response = $this->from('/products/cumin')->post(route('products.enquiry.store', 'cumin'), $this->enquiry());
        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('/login', (string) $response->headers->get('Location'));

        $this->get('/products/cumin')->assertOk()->assertDontSee('data-guest-login-prompt', false);
    }

    public function test_guest_checkout_off_keeps_cart_and_sends_guest_to_login_then_review(): void
    {
        $this->set(['guest_checkout_enabled' => '0']);

        $this->post(route('checkout.start'), ['items' => [$this->packId()]])
            ->assertRedirect(route('customer.login').'?redirect='.urlencode('/checkout/review'))
            ->assertSessionHas('error', 'অর্ডার করতে লগইন করুন।');
        $this->assertSame([$this->packId()], session('checkout.price_ids'), 'Cart must survive in the session.');

        $this->post(route('checkout.start'), ['items' => [$this->packId()], 'auth_intent' => 'register'])
            ->assertRedirect(route('customer.register').'?redirect='.urlencode('/checkout/review'));

        foreach (['checkout.review', 'checkout.payment'] as $route) {
            $this->get(route($route))->assertRedirect(route('customer.login').'?redirect='.urlencode('/checkout/review'));
        }

        // Logged in (any user) → Review works with the same session cart.
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get(route('checkout.review'))->assertOk();
    }

    public function test_guest_checkout_off_blocks_json_order_with_401(): void
    {
        $this->set(['guest_checkout_enabled' => '0']);
        $before = Order::count();

        $this->postJson(route('order.store'), ['customer_name' => 'A'])->assertStatus(401)
            ->assertJson(['message' => 'অর্ডার করতে লগইন করুন।'])
            ->assertJsonPath('login_url', route('customer.login').'?redirect='.urlencode('/checkout/review'));
        $this->assertSame($before, Order::count());
    }

    public function test_mini_cart_shows_login_and_register_when_guest_checkout_off(): void
    {
        $this->set(['guest_checkout_enabled' => '0']);
        $this->get('/products/cumin')->assertOk()
            ->assertSee('অর্ডার করতে লগইন করুন')
            ->assertSee("msCartCheckout('login')", false)->assertSee("msCartCheckout('register')", false);

        $this->set(['customer_registration_enabled' => '0']);
        $this->get('/products/cumin')->assertDontSee("msCartCheckout('register')", false);

        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/products/cumin')
            ->assertDontSee("msCartCheckout('login')", false);
    }

    public function test_guest_enquiry_off_blocks_both_enquiry_types(): void
    {
        $this->set(['guest_enquiry_enabled' => '0']);

        $this->from('/products/cumin')->post(route('products.enquiry.store', 'cumin'), $this->enquiry())
            ->assertRedirect(route('customer.login').'?redirect='.urlencode('/products/cumin'))
            ->assertSessionHas('error', 'Enquiry পাঠাতে লগইন করুন।');
        $this->postJson(route('paykari-combo.enquiry.store'), [])->assertStatus(401);

        // The wholesale view always renders the enquiry section.
        $this->get(route('customer.wholesale.products.show', 'cumin'))->assertOk()->assertSee('data-guest-login-prompt="enquiry"', false);
    }

    public function test_guest_review_off_blocks_guest_but_not_customer(): void
    {
        $this->set(['guest_review_enabled' => '0']);

        $this->from('/products/cumin')->post(route('products.reviews.store', 'cumin'), $this->review())
            ->assertRedirect(route('customer.login').'?redirect='.urlencode('/products/cumin'));
        $this->assertSame(0, ProductReview::count());
        $this->get('/products/cumin')->assertSee('data-guest-login-prompt="review"', false);

        // Logged-in customer reaches the controller (validation), not the login page.
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->from('/products/cumin')->post(route('products.reviews.store', 'cumin'), ['rating' => 5])
            ->assertRedirect('/products/cumin')->assertSessionHasErrors('comment');
    }

    public function test_maintenance_has_priority_over_guest_login_redirect(): void
    {
        $this->set(['guest_checkout_enabled' => '0', 'maintenance_enabled' => '1', 'maintenance_mode' => 'full']);

        $this->get(route('checkout.review'))->assertStatus(503)->assertSee('ওয়েবসাইটের কাজ চলছে');
        $this->postJson(route('order.store'), [])->assertStatus(503)->assertJson(['maintenance' => true]);
    }

    public function test_admin_cannot_turn_guest_checkout_off_without_customer_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $base = ['otp_expiry_minutes' => 5, 'otp_resend_cooldown_seconds' => 60, 'otp_max_attempts' => 5,
            'guest_enquiry_enabled' => 'on', 'guest_review_enabled' => 'on', 'customer_registration_enabled' => 'on'];

        // Login page off → rejected, nothing saved.
        $this->actingAs($admin)->post(route('admin.auth-settings.update'), $base + ['customer_password_login' => 'on'])
            ->assertSessionHasErrors('guest_checkout_enabled');
        $this->assertSame('1', WebsiteSetting::get('guest_checkout_enabled', '1'));

        // Login on but no method → rejected.
        $this->actingAs($admin)->post(route('admin.auth-settings.update'), $base + ['customer_login_enabled' => 'on'])
            ->assertSessionHasErrors('guest_checkout_enabled');

        // Login + a method → saved.
        $this->actingAs($admin)->post(route('admin.auth-settings.update'), $base + ['customer_login_enabled' => 'on', 'customer_otp_login' => 'on'])
            ->assertSessionHasNoErrors();
        $this->assertSame('0', WebsiteSetting::get('guest_checkout_enabled'));
    }

    public function test_settings_page_warns_when_new_customers_cannot_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->set(['guest_checkout_enabled' => '0']);
        $this->actingAs($admin)->get(route('admin.auth-settings.index'))->assertOk()->assertDontSee('নতুন কাস্টমার অর্ডার করতে পারবে না');

        $this->set(['customer_registration_enabled' => '0']);
        $this->actingAs($admin)->get(route('admin.auth-settings.index'))->assertOk()->assertSee('নতুন কাস্টমার অর্ডার করতে পারবে না');
    }
}
