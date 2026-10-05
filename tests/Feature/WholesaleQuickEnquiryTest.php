<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\WebsiteSetting;
use App\Models\WholesaleEnquiry;
use Tests\TestCase;

class WholesaleQuickEnquiryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Product::create(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500, 'is_active' => true,
            'show_in_retail' => false, 'show_in_wholesale' => true, 'min_order_quantity' => 10, 'min_order_unit' => 'kg']);
        WebsiteSetting::updateOrCreate(['key' => 'whatsapp_number'], ['value' => '01768-987779']);
    }

    public function test_quick_enquiry_needs_only_qty_name_and_phone(): void
    {
        $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 25, 'quantity_unit' => 'kg',
        ])->assertCreated()->assertJson(['ok' => true]);

        $enquiry = WholesaleEnquiry::sole();
        $this->assertSame('Rahim', $enquiry->customer_name);
        $this->assertSame('', (string) $enquiry->delivery_location);
    }

    public function test_quick_enquiry_reports_moq_as_json(): void
    {
        $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity_kg');

        $this->assertSame(0, WholesaleEnquiry::count());
    }

    public function test_wholesale_card_shows_price_whatsapp_and_call_buttons(): void
    {
        $this->get('/?mode=wholesale')->assertOk()
            ->assertSee("msQuickEnquiry(this, 'form')", false)
            ->assertSee("msQuickEnquiry(this, 'whatsapp')", false)
            ->assertSee("msQuickEnquiry(this, 'call')", false)
            ->assertSee('data-moq="10"', false)
            ->assertSee('id="ms-qe"', false);
    }

    public function test_whatsapp_contact_is_unlocked_only_after_the_lead_is_saved(): void
    {
        $response = $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 20,
            'quantity_unit' => 'kg', 'business_type' => 'shop', 'contact_channel' => 'whatsapp',
        ])->assertCreated();

        $enquiry = WholesaleEnquiry::sole();
        $this->assertSame('whatsapp', $enquiry->contact_channel);
        $this->assertSame('shop', $enquiry->business_type);
        $this->assertStringStartsWith('https://wa.me/8801768987779?text=', $response->json('contact.whatsapp'));
        $this->assertStringContainsString(rawurlencode('#'.$enquiry->id), $response->json('contact.whatsapp'));
        $this->assertSame('tel:+8801768987779', $response->json('contact.tel'));

        // Below the MOQ never unlocks a contact link.
        $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Karim', 'customer_phone' => '01812345678', 'quantity_kg' => 2, 'contact_channel' => 'call',
        ])->assertStatus(422)->assertJsonMissingPath('contact');
    }

    public function test_plain_form_enquiry_defaults_to_form_channel(): void
    {
        $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => 'Rahim', 'customer_phone' => '01712345678', 'quantity_kg' => 10,
        ])->assertCreated();

        $this->assertSame('form', WholesaleEnquiry::sole()->contact_channel);
    }

    private function enquiry(string $name): WholesaleEnquiry
    {
        $this->postJson(route('products.enquiry.store', 'cumin'), [
            'customer_name' => $name, 'customer_phone' => '01712345678', 'quantity_kg' => 10,
        ])->assertCreated();

        return WholesaleEnquiry::latest('id')->firstOrFail();
    }

    public function test_admin_can_delete_test_enquiries_but_not_ones_that_became_orders(): void
    {
        $test    = $this->enquiry('Test One');
        $test2   = $this->enquiry('Test Two');
        $ordered = $this->enquiry('Real Buyer');
        $admin   = \App\Models\User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        \App\Models\WholesaleChatMessage::create(['enquiry_id' => $test->id, 'sender_type' => 'admin', 'sender_id' => $admin->id, 'message' => 'hi']);
        \App\Models\Order::create([
            'order_number' => 'ENQ-ORDER', 'customer_name' => 'Real Buyer', 'mobile_number' => '01712345678',
            'full_address' => 'Dhaka', 'district' => 'Dhaka', 'area' => 'Dhaka', 'subtotal' => 100, 'packaging_cost' => 0,
            'delivery_charge' => 0, 'grand_total' => 100, 'payment_method' => 'cash_on_delivery', 'order_status' => 'pending',
            'enquiry_id' => $ordered->id,
        ]);

        $this->actingAs($admin)->get(route('admin.wholesale.enquiry.index'))->assertOk()->assertSee('enq-bulk', false);

        $this->delete(route('admin.wholesale.enquiry.destroy', $test))
            ->assertRedirect(route('admin.wholesale.enquiry.index'))->assertSessionHas('success');
        $this->assertModelMissing($test);
        $this->assertSame(0, \App\Models\WholesaleChatMessage::where('enquiry_id', $test->id)->count());

        $this->delete(route('admin.wholesale.enquiry.destroy', $ordered))->assertSessionHas('error');
        $this->assertModelExists($ordered);

        $this->from(route('admin.wholesale.enquiry.index'))
            ->post(route('admin.wholesale.enquiry.bulk-destroy'), ['ids' => [$test2->id, $ordered->id]])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1টি') && str_contains($m, '#'.$ordered->id));
        $this->assertModelMissing($test2);
        $this->assertModelExists($ordered);
    }

    public function test_guests_and_customers_cannot_delete_enquiries(): void
    {
        $enquiry = $this->enquiry('Someone');
        $this->delete(route('admin.wholesale.enquiry.destroy', $enquiry));
        $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->delete(route('admin.wholesale.enquiry.destroy', $enquiry));
        $this->assertModelExists($enquiry);
    }
}
