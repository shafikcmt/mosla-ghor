<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ManualOrderController;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualOrderAndStockOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('secret123'), 'role' => 'admin', 'is_admin' => true]);
    }

    private function vendor(string $mode = 'stock_only'): Vendor
    {
        $user = User::factory()->create(['role' => 'vendor']);

        return Vendor::create(['user_id' => $user->id, 'shop_name' => 'Stock shop', 'slug' => 'stock-shop', 'owner_name' => 'Owner', 'phone' => '01700000000', 'email' => 'v@example.com', 'status' => 'approved', 'is_active' => true, 'panel_mode' => $mode]);
    }

    private function orderPayload(array $items, array $extra = []): array
    {
        return array_merge([
            'order_channel'   => 'whatsapp',
            'customer_name'   => 'রহিম',
            'mobile_number'   => '01811111111',
            'full_address'    => 'মিরপুর ১০, ঢাকা',
            'items'           => $items,
            'delivery_charge' => 60,
            'discount_amount' => 10,
            'paid_amount'     => 60,
            'payment_method'  => 'bkash',
            'order_status'    => 'confirmed',
            'deduct_stock'    => 1,
        ], $extra);
    }

    public function test_admin_creates_phone_order_with_invoice_and_stock_deduction(): void
    {
        $legacy = Product::create(['name_bn' => 'হলুদ', 'slug' => 'holud', 'retail_price_1kg' => 400, 'stock' => 10]);
        $pack   = ProductPrice::create(['product_id' => $legacy->id, 'sell_type' => 'retail', 'label' => '250 গ্রাম', 'quantity_gram' => 250, 'auto_price' => 100, 'final_price' => 110, 'is_active' => true]);
        $pcs    = Product::create(['name_bn' => 'বয়াম', 'slug' => 'boyam', 'retail_price_1kg' => 50, 'unit' => 'pcs', 'stock_qty' => 20]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.orders.create'))->assertOk()
            ->assertViewHas('catalog', fn ($c) => $c->firstWhere('id', $legacy->id)['packs'][0]['price'] === 110.0);

        $res = $this->actingAs($admin)->post(route('admin.orders.manual.store'), $this->orderPayload([
            ['product_id' => $legacy->id, 'price_id' => $pack->id, 'quantity' => 6, 'unit_price' => 110],   // 1.5 kg → 2 kg legacy
            ['product_id' => $pcs->id, 'price_id' => '', 'quantity' => 3, 'unit_price' => 50],
        ]));

        $order = Order::where('order_source', 'admin_manual_order')->firstOrFail();
        $res->assertRedirect(route('admin.orders.send', $order));

        // Send screen: one-click WhatsApp, new customer gets the account link.
        $this->actingAs($admin)->get(route('admin.orders.send', $order))
            ->assertOk()->assertSee('WhatsApp এ ইনভয়েস পাঠান')->assertSee($order->accountUrl());

        $this->assertSame('whatsapp', $order->order_channel);
        $this->assertEquals(660 + 150, (float) $order->subtotal);
        $this->assertEquals(810 + 60 - 10, (float) $order->grand_total);
        $this->assertEquals(800, (float) $order->due_amount);
        $this->assertNotNull($order->invoice_token);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(8, $legacy->fresh()->stock);
        $this->assertEquals(17, (float) $pcs->fresh()->stock_qty);

        // Show page offers the filled WhatsApp message.
        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()->assertSee('WhatsApp এ পাঠান')->assertSee($order->invoiceUrl());

        $this->actingAs($admin)->post(route('admin.orders.whatsapp', $order), ['phone' => '01811111111', 'message' => 'hello'])
            ->assertRedirect('https://wa.me/8801811111111?text=hello');
        $this->assertNotNull($order->fresh()->whatsapp_sent_at);

        // Public invoice works without a vendor.
        $this->get(route('invoice.show', $order->invoice_token))->assertOk()->assertSee($order->order_number);

        // Cancelling restores exactly what was taken.
        $this->actingAs($admin)->post(route('admin.orders.updateStatus', $order), ['payment_status' => 'pending', 'order_status' => 'cancelled']);
        $this->assertSame(10, $legacy->fresh()->stock);
        $this->assertEquals(20, (float) $pcs->fresh()->stock_qty);
    }

    public function test_short_stock_blocks_order_unless_deduction_is_off(): void
    {
        $p = Product::create(['name_bn' => 'মরিচ', 'slug' => 'morich', 'retail_price_1kg' => 300, 'unit' => 'pcs', 'stock_qty' => 1]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.orders.manual.store'), $this->orderPayload([
            ['product_id' => $p->id, 'quantity' => 5, 'unit_price' => 300],
        ]))->assertSessionHas('error');
        $this->assertSame(0, Order::count());

        $this->actingAs($admin)->post(route('admin.orders.manual.store'), $this->orderPayload([
            ['product_id' => $p->id, 'quantity' => 5, 'unit_price' => 300],
        ], ['deduct_stock' => 0]))->assertRedirect();
        $this->assertSame(1, Order::count());
        $this->assertEquals(1, (float) $p->fresh()->stock_qty);
    }

    public function test_website_order_can_get_invoice_link(): void
    {
        $order = Order::create(['order_number' => 'MSL-1', 'customer_name' => 'X', 'mobile_number' => '01900000000', 'full_address' => 'A', 'district' => 'D', 'area' => 'A', 'grand_total' => 500, 'subtotal' => 500]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('ইনভয়েস লিংক তৈরি করুন');
        $this->actingAs($admin)->post(route('admin.orders.invoice-link', $order))->assertRedirect(route('admin.orders.show', $order));
        $this->assertNotNull($order->fresh()->invoice_token);
        $this->assertEquals(500, $order->fresh()->effectiveDue());
    }

    public function test_stock_only_vendor_sees_only_stock_screens(): void
    {
        $vendor = $this->vendor();
        $user   = $vendor->user;

        $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.stock.index'));
        $this->actingAs($user)->get(route('vendor.products.index'))->assertRedirect(route('vendor.stock.index'));
        $this->actingAs($user)->get(route('vendor.stock.index'))->assertOk()
            ->assertSee('স্টক খাতা')->assertDontSee('নতুন বিক্রয় (POS)');
        $this->actingAs($user)->get(route('vendor.stock.history'))->assertOk();
    }

    public function test_full_vendor_keeps_full_panel(): void
    {
        $vendor = $this->vendor('full');
        $this->actingAs($vendor->user)->get(route('vendor.products.index'))->assertOk();
    }

    public function test_quick_add_and_json_stock_moves(): void
    {
        $vendor = $this->vendor();
        $user   = $vendor->user;

        $this->actingAs($user)->postJson(route('vendor.stock.quick-add'), [
            'name' => 'জিরা', 'unit' => 'kg', 'opening_stock' => 12.5, 'purchase_price' => 600, 'low_stock_threshold' => 5,
        ])->assertOk()->assertJsonPath('product.onhand', 12.5);

        $p = Product::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertFalse((bool) $p->is_active);
        $this->assertFalse((bool) $p->show_in_retail);

        $this->actingAs($user)->postJson(route('vendor.stock.adjust'), ['product_id' => $p->id, 'mode' => 'reduce', 'quantity' => 8])
            ->assertOk()->assertJsonPath('product.status', 'low_stock');
        $this->actingAs($user)->postJson(route('vendor.stock.adjust'), ['product_id' => $p->id, 'mode' => 'reduce', 'quantity' => 100])
            ->assertStatus(422);
        $this->actingAs($user)->postJson(route('vendor.stock.adjust'), ['product_id' => $p->id, 'mode' => 'set', 'quantity' => 30])
            ->assertOk()->assertJsonPath('product.onhand', 30);
        $this->actingAs($user)->postJson(route('vendor.stock.threshold'), ['product_id' => $p->id, 'low_stock_threshold' => 2])
            ->assertOk()->assertJsonPath('product.threshold', 2);

        $this->assertSame(3, $vendor->stockMovements()->count()); // opening + reduce + set

        // Another vendor's product is off-limits.
        $other = Product::create(['name_bn' => 'x', 'slug' => 'x', 'retail_price_1kg' => 1]);
        $this->actingAs($user)->postJson(route('vendor.stock.adjust'), ['product_id' => $other->id, 'mode' => 'add', 'quantity' => 1])
            ->assertForbidden();
    }

    public function test_admin_sets_panel_mode(): void
    {
        $vendor = $this->vendor('full');
        $admin  = $this->admin();

        $this->actingAs($admin)->put(route('admin.vendors.update', $vendor), [
            'shop_name' => 'Stock shop', 'owner_name' => 'Owner', 'phone' => '01700000000', 'email' => 'v@example.com',
            'status' => 'approved', 'panel_mode' => 'stock_only',
            'business_type' => '', 'address' => '', 'district' => '', 'city' => '', 'trade_license' => '', 'nid' => '',
            'commission_type' => '', 'commission_value' => '', 'admin_note' => '',
        ])->assertRedirect();

        $this->assertTrue($vendor->fresh()->isStockOnly());
    }

    public function test_phone_only_order_and_customer_completes_info_via_link(): void
    {
        $p = Product::create(['name_bn' => 'আদা', 'slug' => 'ada', 'retail_price_1kg' => 200, 'unit' => 'pcs', 'stock_qty' => 10]);
        $admin = $this->admin();

        // Only phone + products — no name, no address.
        $this->actingAs($admin)->post(route('admin.orders.manual.store'), [
            'mobile_number' => '+880 1911-222333',
            'items'         => [['product_id' => $p->id, 'quantity' => 2, 'unit_price' => 200]],
            'delivery_charge' => 60,
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('01911222333', $order->mobile_number);
        $this->assertSame('', $order->full_address);
        $this->assertSame('confirmed', $order->order_status);

        $message = ManualOrderController::renderMessage($order);
        $this->assertStringContainsString('ডেলিভারির জন্য আপনার ঠিকানা দিন', $message);
        $this->assertStringContainsString($order->accountUrl(), $message);

        // Customer opens the link (logged out) and fills the form.
        auth()->logout();
        $this->get($order->accountUrl())->assertOk()->assertSee('01911222333');
        $this->post(route('invoice.account.store', $order->invoice_token), [
            'name' => 'করিম', 'full_address' => 'উত্তরা সেক্টর ৭', 'district' => 'ঢাকা',
            'password' => 'secret12', 'password_confirmation' => 'secret12',
        ])->assertRedirect(route('customer.orders.show', $order->id));

        $user = User::where('phone', '01911222333')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertAuthenticatedAs($user);
        $order->refresh();
        $this->assertSame('উত্তরা সেক্টর ৭', $order->full_address);
        $this->assertSame('করিম', $order->customer_name);
        $this->assertNotNull($order->customer_id);
        $this->assertNotNull($order->customer_confirmed_at);

        // Customer can now see the order in their account.
        $this->get(route('customer.orders.show', $order->id))->assertOk();
    }

    public function test_registered_customer_gets_login_link_not_signup(): void
    {
        User::create(['name' => 'Old', 'email' => 'old@example.com', 'phone' => '01722333444', 'password' => Hash::make('x123456'), 'role' => 'customer']);
        $p = Product::create(['name_bn' => 'রসুন', 'slug' => 'roshun', 'retail_price_1kg' => 200, 'unit' => 'pcs', 'stock_qty' => 10]);
        $admin = $this->admin();

        $this->actingAs($admin)->getJson(route('admin.orders.manual.lookup', ['phone' => '01722333444']))
            ->assertJson(['registered' => true]);

        $this->actingAs($admin)->post(route('admin.orders.manual.store'), [
            'mobile_number' => '01722333444',
            'items'         => [['product_id' => $p->id, 'quantity' => 1, 'unit_price' => 200]],
        ]);
        $order = Order::firstOrFail();

        $message = ManualOrderController::renderMessage($order);
        $this->assertStringNotContainsString($order->accountUrl(), $message);
        $this->assertStringContainsString('/login?redirect=', $message);

        auth()->logout();
        $this->get($order->accountUrl())->assertRedirect();
        $this->post(route('invoice.account.store', $order->invoice_token), [
            'name' => 'Hacker', 'full_address' => 'x', 'password' => 'secret12', 'password_confirmation' => 'secret12',
        ])->assertRedirect();
        $this->assertSame(1, User::where('phone', '01722333444')->count());
        $this->assertTrue(Hash::check('x123456', User::where('phone', '01722333444')->first()->password));
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $p = Product::create(['name_bn' => 'x', 'slug' => 'x1', 'retail_price_1kg' => 1, 'unit' => 'pcs', 'stock_qty' => 5]);
        $this->actingAs($this->admin())->post(route('admin.orders.manual.store'), [
            'mobile_number' => '12345',
            'items'         => [['product_id' => $p->id, 'quantity' => 1, 'unit_price' => 1]],
        ])->assertSessionHasErrors('mobile_number');
    }
}
