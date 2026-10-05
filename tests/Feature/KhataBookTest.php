<?php

namespace Tests\Feature;

use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataParty;
use App\Models\Khata\KhataTransaction;
use App\Models\User;
use App\Models\Vendor;
use Tests\TestCase;

class KhataBookTest extends TestCase
{
    private User $user;
    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        [$this->user, $this->vendor] = $this->makeVendor('tamim', 'inventory');
    }

    private function makeVendor(string $key, string $mode): array
    {
        $user = User::factory()->create(['role' => 'vendor', 'phone' => '0170000'.str_pad((string) crc32($key) % 10000, 4, '0')]);
        $vendor = Vendor::create(['user_id' => $user->id, 'shop_name' => "মেসার্স {$key}", 'slug' => $key, 'owner_name' => 'Owner',
            'phone' => $user->phone, 'email' => "{$key}@example.com", 'status' => 'approved', 'is_active' => true, 'panel_mode' => $mode]);

        return [$user, $vendor];
    }

    private function item(string $name = 'mosla gura', float $stock = 50): KhataItem
    {
        $this->actingAs($this->user)->post(route('vendor.khata.items.store'), [
            'name' => $name, 'unit' => 'kg', 'sale_price' => 1820, 'purchase_price' => 1500, 'opening_stock' => $stock,
        ])->assertSessionHasNoErrors();

        return KhataItem::where('name', $name)->sole();
    }

    private function party(string $type = 'customer', float $opening = 0, string $side = 'receivable'): KhataParty
    {
        $this->actingAs($this->user)->post(route('vendor.khata.parties.store'), [
            'name' => 'Sofik vi '.$type, 'phone' => '01601987779', 'type' => $type,
            'opening_amount' => $opening, 'opening_side' => $side,
        ])->assertSessionHasNoErrors();

        return KhataParty::latest('id')->firstOrFail();
    }

    public function test_inventory_only_vendor_only_sees_the_khata(): void
    {
        $this->actingAs($this->user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.khata.home'));
        $this->get(route('vendor.products.index'))->assertRedirect(route('vendor.khata.home'));
        $this->get(route('vendor.khata.home'))->assertOk()->assertSee('পাওনা')->assertSee('বকেয়া');

        // A normal (e-commerce) vendor keeps the full panel and can also open the khata.
        [$full] = $this->makeVendor('full', 'full');
        $this->actingAs($full)->get(route('vendor.dashboard'))->assertOk();
        $this->get(route('vendor.khata.home'))->assertOk();
    }

    public function test_sale_purchase_payments_stock_and_balances(): void
    {
        $item  = $this->item();
        $this->assertEquals(50, $item->stock);
        $this->assertSame(1, KhataTransaction::where('type', 'stock_in')->count());

        $customer = $this->party('customer');
        // The chalan from the sample: 50 × ৳1,820 + লেবার ভাড়া ৳200, nothing paid yet.
        $this->post(route('vendor.khata.trade.store', 'sale'), [
            'party_id' => $customer->id, 'date' => now()->toDateString(), 'payment_mode' => 'cash',
            'lines' => [['item_id' => $item->id, 'quantity' => 50, 'price' => 1820]],
            'extra_label' => 'levor vhara', 'extra_charge' => 200, 'paid' => 0,
        ])->assertSessionHasNoErrors();

        $sale = KhataTransaction::where('type', 'sale')->sole();
        $this->assertSame(1, $sale->number);
        $this->assertEquals(91200, $sale->total);
        $this->assertSame('credit', $sale->payment_mode);
        $this->assertEquals(0, $item->fresh()->stock);
        $this->assertEquals(91200, $customer->fresh()->balance());

        $this->get(route('vendor.khata.tx.show', $sale))->assertOk()
            ->assertSee('বিক্রয় বিবরণ')->assertSee('একানব্বই হাজার দুই শত টাকা মাত্র')->assertSee('levor vhara')
            ->assertSee('wa.me/8801601987779', false);
        $this->get($sale->fresh()->publicUrl())->assertOk()->assertSee('বিক্রয় বিবরণ');

        // Payment in settles it.
        $this->post(route('vendor.khata.amount.store', 'payment_in'), [
            'party_id' => $customer->id, 'amount' => 91200, 'date' => now()->toDateString(), 'payment_mode' => 'cash',
        ])->assertRedirect(route('vendor.khata.parties.show', $customer));
        $this->assertEquals(0, $customer->fresh()->balance());

        // Purchase on credit from a supplier with an opening payable.
        $supplier = $this->party('supplier', 5000, 'payable');
        $this->assertEquals(-5000, $supplier->balance());
        $this->post(route('vendor.khata.trade.store', 'purchase'), [
            'party_id' => $supplier->id, 'date' => now()->toDateString(), 'payment_mode' => 'cash',
            'lines' => [['item_id' => $item->id, 'quantity' => 20, 'price' => 1600]], 'paid' => 2000,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(20, $item->fresh()->stock);
        $this->assertEquals(1600, $item->fresh()->purchase_price);
        $this->assertEquals(-5000 - 30000, $supplier->fresh()->balance());

        $this->post(route('vendor.khata.amount.store', 'payment_out'), [
            'party_id' => $supplier->id, 'amount' => 35000, 'date' => now()->toDateString(), 'payment_mode' => 'bkash',
        ]);
        $this->assertEquals(0, $supplier->fresh()->balance());

        $this->post(route('vendor.khata.amount.store', 'expense'), [
            'category' => 'দোকান ভাড়া', 'amount' => 3000, 'date' => now()->toDateString(), 'payment_mode' => 'cash',
        ]);

        // Report: sales 91,200; COGS 50 × 1,500; profit = (91,000 − 75,000) − 3,000.
        $s = app(\App\Services\KhataBook::class)->summary($this->vendor, now()->startOfMonth(), now()->endOfMonth());
        $this->assertEquals(91200, $s['sales']);
        $this->assertEquals(75000, $s['cogs']);
        $this->assertEquals(16000, $s['gross_profit']);
        $this->assertEquals(13000, $s['net_profit']);
        // "Today" must include today's rows (date stored with a time part on some drivers).
        $today = app(\App\Services\KhataBook::class)->summary($this->vendor, now()->startOfDay(), now()->endOfDay());
        $this->assertEquals(91200, $today['sales']);
        $this->get(route('vendor.khata.report', ['period' => 'year']))->assertOk()->assertSee('নিট লাভ');

        // Deleting the sale puts the stock back.
        $this->delete(route('vendor.khata.tx.destroy', $sale))->assertSessionHas('success');
        $this->assertEquals(70, $item->fresh()->stock);
    }

    public function test_due_sale_needs_a_party_and_other_shops_are_isolated(): void
    {
        $item = $this->item();
        $this->post(route('vendor.khata.trade.store', 'sale'), [
            'date' => now()->toDateString(), 'payment_mode' => 'cash',
            'lines' => [['item_id' => $item->id, 'quantity' => 1, 'price' => 1820]], 'paid' => 500,
        ])->assertSessionHas('error');
        $this->assertSame(0, KhataTransaction::where('type', 'sale')->count());

        // Fully paid walk-in sale is fine.
        $this->post(route('vendor.khata.trade.store', 'sale'), [
            'date' => now()->toDateString(), 'payment_mode' => 'cash',
            'lines' => [['item_id' => $item->id, 'quantity' => 1, 'price' => 1820]], 'paid' => 1820,
        ])->assertSessionHasNoErrors();

        [$other] = $this->makeVendor('other', 'inventory');
        $this->actingAs($other)->get(route('vendor.khata.items.show', $item))->assertNotFound();
        $this->post(route('vendor.khata.trade.store', 'sale'), [
            'date' => now()->toDateString(), 'payment_mode' => 'cash',
            'lines' => [['item_id' => $item->id, 'quantity' => 1, 'price' => 1]], 'paid' => 1,
        ])->assertSessionHasErrors('lines.0.item_id');
    }

    public function test_pages_render(): void
    {
        $item = $this->item();
        $party = $this->party();
        foreach ([
            route('vendor.khata.items.index'), route('vendor.khata.items.create'), route('vendor.khata.items.show', $item),
            route('vendor.khata.items.edit', $item), route('vendor.khata.parties.index'), route('vendor.khata.parties.create'),
            route('vendor.khata.parties.show', $party), route('vendor.khata.parties.statement', $party),
            route('vendor.khata.trade.create', 'sale'), route('vendor.khata.trade.create', 'purchase'),
            route('vendor.khata.amount.create', 'payment_in'), route('vendor.khata.amount.create', 'expense'),
            route('vendor.khata.transactions'), route('vendor.khata.settings'), route('vendor.khata.report', ['period' => 'today']),
        ] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk();
        }
    }

    public function test_admin_switches_a_vendor_between_panel_modes(): void
    {
        [, $shop] = $this->makeVendor('shop', 'full');
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.vendors.edit', $shop))->assertOk()->assertSee('name="panel_mode"', false);
        $this->get(route('admin.vendors.create'))->assertOk()->assertSee('name="panel_mode"', false);

        $this->put(route('admin.vendors.update', $shop), [
            'shop_name' => $shop->shop_name, 'owner_name' => 'Owner', 'phone' => $shop->phone, 'email' => $shop->email,
            'status' => 'approved', 'panel_mode' => 'inventory', 'business_type' => '', 'address' => '', 'district' => '',
            'city' => '', 'trade_license' => '', 'nid' => '', 'commission_type' => '', 'commission_value' => '', 'admin_note' => '',
        ])->assertRedirect(route('admin.vendors.show', $shop));
        $this->assertTrue($shop->fresh()->isInventoryOnly());

        $this->get(route('vendor.register'))->assertOk();
    }
}
