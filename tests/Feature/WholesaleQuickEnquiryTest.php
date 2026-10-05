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
            ->assertSee('msQuickEnquiry(this)', false)
            ->assertSee('https://wa.me/8801768987779?text=', false)
            ->assertSee('tel:+8801768987779', false)
            ->assertSee('id="ms-qe"', false);
    }
}
