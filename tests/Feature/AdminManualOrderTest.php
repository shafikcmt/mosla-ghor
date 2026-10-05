<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Notifications\OrderVoucherNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminManualOrderTest extends TestCase
{
    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->product = Product::create(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 50, 'retail_price_1kg' => 900,
            'is_active' => true, 'show_in_retail' => true, 'unit' => 'kg']);
        WebsiteSetting::updateOrCreate(['key' => 'whatsapp_number'], ['value' => '01768-987779']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'customer_name' => 'Rahim Store', 'customer_phone' => '01712345678', 'district' => 'Dhaka',
            'channel' => 'whatsapp', 'order_type' => 'wholesale', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 850]],
            'delivery_charge' => 150, 'discount' => 50, 'paid_amount' => 1000,
        ], $extra);
    }

    public function test_admin_creates_a_phone_order_with_stock_and_account(): void
    {
        $this->actingAs($this->admin)->get(route('admin.orders.manual.create'))->assertOk()->assertSee('ফোন / WhatsApp অর্ডার');

        $res = $this->post(route('admin.orders.manual.store'), $this->payload());
        $order = Order::sole();
        $res->assertRedirect(route('admin.orders.manual.share', $order));

        $this->assertSame('admin_manual_order', $order->order_source);
        $this->assertEquals(4250, $order->subtotal);
        $this->assertEquals(4350, $order->grand_total);
        $this->assertEquals(3350, $order->due_amount);
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(5000, (int) $order->items->first()->quantity_gram);
        $this->assertEquals(45, $this->product->fresh()->onHand());
        $this->assertNotNull($order->invoice_token);

        $customer = Customer::where('mobile_number', '01712345678')->sole();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(1, (int) $customer->total_orders);
        $this->assertNotNull(User::where('phone', '01712345678')->where('role', 'customer')->first());

        // Voucher page: WhatsApp message with PDF + registration link.
        $this->get(route('admin.orders.manual.share', $order))->assertOk()
            ->assertSee('https://wa.me/8801712345678?text=', false)
            ->assertSee('set-password', false)
            ->assertSee($order->invoicePdfUrl(), false);
    }

    public function test_voucher_email_is_sent_with_registration_link(): void
    {
        Notification::fake();
        $this->actingAs($this->admin)->post(route('admin.orders.manual.store'), $this->payload());
        $order = Order::sole();

        $this->post(route('admin.orders.manual.email', $order), ['email' => 'rahim@example.com'])->assertSessionHas('success');

        Notification::assertSentOnDemand(OrderVoucherNotification::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'rahim@example.com' && $n->setPasswordUrl);
        $this->assertSame('rahim@example.com', $order->customer->fresh()->email);
    }

    public function test_short_stock_is_refused_and_bad_phone_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.orders.manual.store'), $this->payload(['items' => [['product_id' => $this->product->id, 'quantity' => 500, 'unit_price' => 850]]]))
            ->assertSessionHas('error');
        $this->post(route('admin.orders.manual.store'), $this->payload(['customer_phone' => '12345']))->assertSessionHasErrors('customer_phone');
        $this->assertSame(0, Order::count());
    }

    public function test_dummy_customers_can_be_deleted_but_not_buyers(): void
    {
        $dummy = Customer::create(['name' => 'andy andy', 'mobile_number' => '01700000001', 'is_active' => true]);
        User::create(['name' => 'andy', 'phone' => '01700000001', 'password' => bcrypt('x'), 'role' => 'customer']);
        $dummy2 = Customer::create(['name' => 'test', 'mobile_number' => '01700000002', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.orders.manual.store'), $this->payload());
        $buyer = Customer::where('mobile_number', '01712345678')->sole();

        $this->actingAs($this->admin)->get(route('admin.customers.index'))->assertOk()->assertSee('cust-bulk', false);

        $this->delete(route('admin.customers.destroy', $dummy))->assertSessionHas('success');
        $this->assertModelMissing($dummy);
        $this->assertNull(User::where('phone', '01700000001')->first());

        $this->delete(route('admin.customers.destroy', $buyer))->assertSessionHas('error');
        $this->assertModelExists($buyer);

        $this->post(route('admin.customers.bulk-destroy'), ['ids' => [$dummy2->id, $buyer->id]])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Rahim Store'));
        $this->assertModelMissing($dummy2);
        $this->assertModelExists($buyer);
        $this->assertModelExists($this->admin);
    }
}
