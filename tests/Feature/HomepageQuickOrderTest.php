<?php

namespace Tests\Feature;

use App\Models\{BdDivision, BdDistrict, Combo, DeliveryZone, Order, PriceSetting, Product, ProductPrice};
use Tests\TestCase;

class HomepageQuickOrderTest extends TestCase
{
    private array $address;
    private ProductPrice $price;
    private Combo $combo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $division = BdDivision::create(['source_id' => '1', 'name' => 'Division', 'bn_name' => 'Division', 'is_active' => true]);
        $district = BdDistrict::create(['source_id' => '1', 'division_id' => $division->id, 'name' => 'District', 'bn_name' => 'District', 'is_active' => true]);
        $zone = DeliveryZone::create(['zone_name' => 'Dhaka City', 'zone_type' => 'inside_dhaka', 'delivery_charge' => 60, 'is_active' => true]);
        $location = $zone->locations()->create(['location_name' => 'City', 'is_active' => true]);
        $this->address = ['full_name' => 'Quick buyer', 'mobile_number' => '01712345678', 'full_address' => 'House 12',
            'bd_division_id' => $division->id, 'bd_district_id' => $district->id,
            'delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id, 'payment_method' => 'cash_on_delivery'];
        PriceSetting::current()->fill(['default_packaging_cost' => 10, 'minimum_order_amount' => 0])->save();
        $product = Product::create(['name_bn' => 'Cumin', 'slug' => 'quick-cumin', 'stock' => 100, 'retail_price_1kg' => 500, 'is_active' => true, 'show_in_retail' => true]);
        $product->syncPrices();
        $this->price = $product->prices()->where('sell_type', 'retail')->firstOrFail();
        $this->combo = Combo::create(['name' => 'Quick combo', 'slug' => 'quick-combo', 'sell_type' => 'retail', 'sell_price' => 425, 'is_active' => true]);
        $this->combo->items()->create(['product_id' => $product->id, 'product_price_id' => $this->price->id,
            'sell_type' => 'retail', 'quantity_gram' => 1000, 'unit_price' => 500, 'line_total' => 500]);
    }

    private function payload(bool $combo): array
    {
        return $this->address + ($combo ? ['combo_id' => $this->combo->id] : ['items' => [['price_id' => $this->price->id]]]);
    }

    public function test_homepage_modal_only_exposes_division_and_district(): void
    {
        $this->get('/')->assertOk()->assertSee('id="order-form"', false)
            ->assertSee('action="'.route('order.store').'"', false)
            ->assertSee('id="f-bd_division_id"', false)->assertSee('id="f-bd_district_id"', false)
            ->assertDontSee('upazila', false)->assertDontSee('union-wrap', false);
    }

    public function test_retail_and_fixed_combo_accept_missing_and_null_upazila_with_unchanged_totals(): void
    {
        foreach ([false, true] as $combo) {
            foreach ([[], ['bd_upazila_id' => null], ['bd_upazila_id' => '']] as $extra) {
                $stockBefore = (float) $this->price->product->fresh()->stock;
                $this->postJson(route('order.store'), $this->payload($combo) + $extra + ['subtotal' => 1, 'grand_total' => 1, 'delivery_charge' => 1])
                    ->assertOk()->assertJson(['success' => true]);
                $order = Order::latest('id')->firstOrFail();
                $subtotal = $combo ? 425 : (float) $this->price->final_price;
                $this->assertNull($order->bd_upazila_id);
                $this->assertNull($order->upazila_name);
                $this->assertEquals($this->address['bd_division_id'], $order->bd_division_id);
                $this->assertEquals($this->address['bd_district_id'], $order->bd_district_id);
                $this->assertEquals($subtotal, $order->subtotal);
                $this->assertEquals(60, $order->delivery_charge);
                $this->assertEquals(10, $order->packaging_cost);
                $this->assertEquals(0, $order->payment_discount);
                $this->assertEquals($subtotal + 70, $order->grand_total);
                $this->assertSame('cash_on_delivery', $order->payment_method);
                $this->assertSame($combo ? 'fixed_combo' : 'single_product', $order->order_type);
                $this->assertEquals($stockBefore - ceil(($combo ? 1000 : $this->price->quantity_gram) / 1000), $this->price->product->fresh()->stock);
                $this->get(route('order.success', $order->order_number))->assertOk()->assertSee('House 12');
                $html = view('invoice.pdf', ['order' => $order, 'siteName' => 'MoslaMart', 'vendor' => null])->render();
                $this->assertStringContainsString('Division › District</div>', $html);
            }
        }
    }

    public function test_both_payloads_keep_required_fields_and_hierarchy_checks(): void
    {
        $other = BdDivision::create(['source_id' => '2', 'name' => 'Other', 'bn_name' => 'Other', 'is_active' => true]);
        foreach ([false, true] as $combo) {
            foreach (['full_name', 'mobile_number', 'bd_division_id', 'bd_district_id', 'full_address', 'delivery_zone_id', 'delivery_location_id'] as $field) {
                $payload = $this->payload($combo);
                unset($payload[$field]);
                $this->postJson(route('order.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
            }
            $this->postJson(route('order.store'), array_replace($this->payload($combo), ['bd_division_id' => $other->id]))
                ->assertUnprocessable()->assertJsonValidationErrors('bd_district_id');
            $this->postJson(route('order.store'), $this->payload($combo) + ['bd_upazila_id' => 99999])
                ->assertUnprocessable()->assertJsonValidationErrors('bd_upazila_id');
        }
        $this->assertSame(0, Order::count());
    }

    public function test_outside_dhaka_location_override_is_used_for_both_order_types(): void
    {
        $zone = DeliveryZone::create(['zone_name' => 'Outside Dhaka', 'zone_type' => 'outside_dhaka', 'delivery_charge' => 120, 'is_active' => true]);
        $location = $zone->locations()->create(['location_name' => 'Outside', 'delivery_charge' => 130, 'is_active' => true]);
        foreach ([false, true] as $combo) {
            $this->postJson(route('order.store'), array_replace($this->payload($combo), ['delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id]))->assertOk();
            $order = Order::latest('id')->firstOrFail();
            $this->assertEquals(130, $order->delivery_charge);
            $this->assertEquals(($combo ? 425 : (float) $this->price->final_price) + 140, $order->grand_total);
        }
    }
}
