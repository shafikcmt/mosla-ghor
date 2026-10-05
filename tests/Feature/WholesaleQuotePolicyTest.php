<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleQuote;
use Tests\TestCase;

class WholesaleQuotePolicyTest extends TestCase
{
    private User $admin;
    private WholesaleEnquiry $enquiry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Product::create(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500, 'is_active' => true,
            'show_in_retail' => false, 'show_in_wholesale' => true]);
        $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 30,
        ])->assertCreated();
        $this->enquiry = WholesaleEnquiry::sole();
        $this->admin   = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        // SQLite keeps wholesale_quotes.vendor_id NOT NULL (relaxed on MySQL only).
        $vendorUser = User::factory()->create(['role' => 'vendor']);
        $vendor = \App\Models\Vendor::create(['user_id' => $vendorUser->id, 'shop_name' => 'Shop', 'slug' => 'shop', 'owner_name' => 'Owner',
            'phone' => '01700000000', 'email' => 'v@example.com', 'status' => 'approved', 'is_active' => true]);
        $this->enquiry->update(['vendor_id' => $vendor->id]);
    }

    private function quote(array $extra = []): WholesaleQuote
    {
        $this->actingAs($this->admin)->post(route('admin.wholesale.quote.store', $this->enquiry), array_merge([
            'unit_price' => 850, 'quantity' => 30, 'quantity_unit' => 'kg', 'validity_days' => 7,
        ], $extra))->assertSessionHasNoErrors();

        return WholesaleQuote::latest('id')->firstOrFail();
    }

    private function pastOrders(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Order::create([
                'order_number' => 'W-'.$i, 'customer_name' => 'Rahim', 'mobile_number' => '01712345678',
                'full_address' => 'Dhaka', 'district' => 'Dhaka', 'area' => 'Dhaka', 'subtotal' => 100, 'packaging_cost' => 0,
                'delivery_charge' => 0, 'grand_total' => 100, 'payment_method' => 'cash_on_delivery', 'order_status' => 'delivered',
                'customer_id' => $this->enquiry->customer_id, 'enquiry_id' => $this->enquiry->id,
            ]);
        }
    }

    public function test_delivery_charge_later_shows_applicable_and_adds_terms(): void
    {
        $quote = $this->quote(['delivery_charge_mode' => 'later', 'add_cod_policy' => '1']);

        $this->assertSame('later', $quote->delivery_charge_mode);
        $this->assertEquals(0, $quote->delivery_charge);
        $this->assertEquals(25500, $quote->grandTotal());
        $this->assertSame('প্রযোজ্য (পরে জানানো হবে)', $quote->deliveryChargeLabel());
        $this->assertSame('মোট (ডেলিভারি চার্জ ছাড়া)', $quote->totalLabel());
        $this->assertCount(2, $quote->terms);
        $this->assertStringContainsString('প্রথম 2টি অর্ডার', $quote->terms[1]);

        $this->get(route('admin.wholesale.quote.show', $quote))->assertOk()
            ->assertSee('প্রযোজ্য (পরে জানানো হবে)')->assertSee('শর্তাবলী');
    }

    public function test_fixed_charge_still_needs_an_amount(): void
    {
        $this->actingAs($this->admin)->post(route('admin.wholesale.quote.store', $this->enquiry), [
            'unit_price' => 850, 'quantity' => 30, 'quantity_unit' => 'kg', 'delivery_charge_mode' => 'fixed',
        ])->assertSessionHasErrors('delivery_charge');

        $quote = $this->quote(['delivery_charge_mode' => 'fixed', 'delivery_charge' => 150, 'add_cod_policy' => '0']);
        $this->assertEquals(25650, $quote->grandTotal());
        $this->assertNull($quote->terms);
    }

    public function test_form_suggests_cod_for_new_buyers_and_advance_after_the_limit(): void
    {
        $form = route('admin.wholesale.quote.create', $this->enquiry);
        $this->actingAs($this->admin)->get($form)->assertOk()
            ->assertSee('আগের পাইকারি অর্ডার: 0টি')->assertSee('value="cod"', false);

        $this->pastOrders(2);
        WebsiteSetting::updateOrCreate(['key' => 'wholesale_default_advance'], ['value' => '40']);
        $this->get($form)->assertOk()->assertSee('অগ্রিম পেমেন্ট')->assertSee('value="40"', false);
    }

    public function test_admin_can_edit_the_policy(): void
    {
        $this->actingAs($this->admin)->post(route('admin.commission.policy.update'), [
            'wholesale_cod_order_limit' => 1, 'wholesale_default_advance' => 50,
            'wholesale_delivery_later_text' => 'চার্জ পরে।', 'wholesale_cod_policy_text' => 'প্রথম {n}টি COD।',
        ])->assertSessionHas('success');

        $this->assertSame('প্রথম 1টি COD।', \App\Support\WholesalePolicy::codPolicyText());
        $this->assertSame(1, \App\Support\WholesalePolicy::codOrderLimit());
    }
}
