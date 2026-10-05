<?php

namespace Tests\Feature;

use App\Models\{BdDivision, BdDistrict, BdUpazila, BdUnion, CustomerAddress, DeliveryZone, Order, Product, User};
use App\Services\CheckoutService;
use Tests\TestCase;

class CheckoutAddressTest extends TestCase
{
    private array $address;
    private int $priceId;
    private BdUpazila $upazila;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $division = BdDivision::create(['source_id' => '1', 'name' => 'Dhaka', 'bn_name' => 'Division', 'is_active' => true]);
        $district = BdDistrict::create(['source_id' => '1', 'division_id' => $division->id, 'name' => 'Dhaka', 'bn_name' => 'District', 'is_active' => true]);
        $this->upazila = BdUpazila::create(['source_id' => '1', 'district_id' => $district->id, 'name' => 'Historic', 'bn_name' => 'Historic', 'is_active' => true]);
        $zone = DeliveryZone::create(['zone_name' => 'Dhaka City', 'zone_type' => 'inside_dhaka', 'delivery_charge' => 60, 'is_active' => true]);
        $location = $zone->locations()->create(['location_name' => 'City', 'is_active' => true]);
        $this->address = ['name' => 'Customer', 'phone' => '01712345678', 'full_address' => 'House 12',
            'bd_division_id' => $division->id, 'bd_district_id' => $district->id,
            'delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id];
        $product = Product::create(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 100, 'retail_price_1kg' => 500, 'is_active' => true, 'show_in_retail' => true]);
        $product->syncPrices();
        $this->priceId = (int) $product->prices()->where('sell_type', 'retail')->value('id');
        $this->post(route('checkout.start'), ['items' => [$this->priceId]])->assertRedirect(route('checkout.review'));
    }

    private function orderPayload(array $extra = []): array
    {
        return array_merge($this->address, ['full_name' => 'Customer', 'mobile_number' => '01712345678',
            'payment_method' => 'cash_on_delivery', 'items' => [['price_id' => $this->priceId]]], $extra);
    }

    public function test_guest_can_save_review_pay_and_order_without_upazila(): void
    {
        $this->get(route('checkout.review'))->assertOk()->assertDontSee('ca-upazila', false)->assertDontSee('BD_UPAZILAS', false);
        $this->post(route('checkout.address.store'), $this->address)->assertRedirect(route('checkout.review'))->assertSessionHasNoErrors();
        $saved = session('checkout.guest_address');
        $this->assertNull($saved['bd_upazila_id']);
        $this->assertSame($this->address['bd_division_id'], $saved['bd_division_id']);
        $this->assertSame($this->address['bd_district_id'], $saved['bd_district_id']);
        $this->assertSame('District, Division', (new CustomerAddress($saved))->regionLine());
        $this->get(route('checkout.review'))->assertOk()->assertSee('District, Division');
        $this->get(route('checkout.payment'))->assertOk();
        $this->postJson(route('order.store'), $this->orderPayload())->assertSuccessful();
        $order = Order::firstOrFail();
        $this->assertNull($order->bd_upazila_id);
        $this->assertNull($order->upazila_name);
        $this->assertEquals($this->address['bd_district_id'], $order->bd_district_id);
        $this->assertEquals(60, $order->delivery_charge);
        $this->get(route('order.success', $order->order_number))->assertOk()->assertSee('House 12');
    }

    public function test_saved_addresses_with_and_without_upazila_remain_usable(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->post(route('checkout.address.store'), $this->address + ['bd_upazila_id' => $this->upazila->id])->assertSessionHasNoErrors();
        $historic = CustomerAddress::firstOrFail();
        $this->assertSame('Historic, District, Division', $historic->regionLine());
        $this->post(route('checkout.address.store'), $this->address)->assertSessionHasNoErrors();
        $this->assertSame(2, CustomerAddress::count());
        $this->assertSame('Historic', $historic->fresh()->upazila_name);
        $new = CustomerAddress::latest('id')->firstOrFail();
        $this->put(route('customer.addresses.update', $new), $this->address)->assertSessionHasNoErrors();
        $this->get(route('customer.addresses.index'))->assertOk()->assertSee('Historic');
        $this->post(route('checkout.address.select', $historic))->assertRedirect(route('checkout.review'));
        $this->get(route('checkout.review'))->assertOk()->assertSee('Historic, District, Division');
        $this->postJson(route('order.store'), $this->orderPayload(['bd_upazila_id' => $this->upazila->id]))->assertSuccessful();
        $order = Order::firstOrFail();
        $this->assertSame('Historic', $order->upazila_name);
        $this->get(route('order.success', $order->order_number))->assertOk()->assertSee('Historic');
    }

    public function test_editing_unrelated_fields_preserves_an_unselectable_historical_upazila(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->post(route('checkout.address.store'), $this->address + ['bd_upazila_id' => $this->upazila->id])->assertSessionHasNoErrors();
        $historic = CustomerAddress::firstOrFail();
        $this->upazila->update(['is_active' => false]);
        $this->get(route('customer.addresses.edit', $historic))->assertOk();
        $this->put(route('customer.addresses.update', $historic), array_replace($this->address, [
            'name' => 'Updated name', 'bd_upazila_id' => '',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('Updated name', $historic->fresh()->name);
        $this->assertEquals($this->upazila->id, $historic->fresh()->bd_upazila_id);
        $this->assertSame('Historic', $historic->fresh()->upazila_name);
    }

    public function test_historical_edit_preserves_active_values_and_allows_explicit_changes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $payload = $this->address + ['bd_upazila_id' => $this->upazila->id];
        $this->post(route('checkout.address.store'), $payload)->assertSessionHasNoErrors();
        $historic = CustomerAddress::firstOrFail();
        $this->put(route('customer.addresses.update', $historic), array_replace($payload, ['name' => 'Edited']))->assertSessionHasNoErrors();
        $this->assertSame('Historic', $historic->fresh()->upazila_name);
        $this->put(route('customer.addresses.update', $historic), $this->address)->assertSessionHasNoErrors();
        $this->assertSame('Historic', $historic->fresh()->upazila_name);
        $this->put(route('customer.addresses.update', $historic), $this->address + ['bd_upazila_id' => ''])->assertSessionHasNoErrors();
        $this->assertNull($historic->fresh()->upazila_name);

        // Older saved addresses may have only region names, predating the ID columns.
        $historic->update(['bd_division_id' => null, 'bd_district_id' => null, 'upazila_name' => 'Legacy']);
        $this->put(route('customer.addresses.update', $historic), $this->address + ['bd_upazila_id' => ''])->assertSessionHasNoErrors();
        $this->assertSame('Legacy', $historic->fresh()->upazila_name);
        $other = BdDistrict::create(['source_id' => '2', 'division_id' => $this->address['bd_division_id'], 'name' => 'Other', 'bn_name' => 'Other', 'is_active' => true]);
        $this->put(route('customer.addresses.update', $historic), array_replace($this->address, ['bd_district_id' => $other->id]))->assertSessionHasNoErrors();
        $this->assertNull($historic->fresh()->upazila_name);
    }

    public function test_remaining_fields_are_required_on_checkout_and_saved_addresses(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        foreach (['checkout.address.store', 'customer.addresses.store'] as $route) {
            foreach (array_keys($this->address) as $field) {
                $payload = $this->address;
                unset($payload[$field]);
                $this->postJson(route($route), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
    }

    public function test_invalid_hierarchy_and_orphan_union_are_rejected(): void
    {
        $other = BdDivision::create(['source_id' => '2', 'name' => 'Other', 'bn_name' => 'Other', 'is_active' => true]);
        $union = BdUnion::create(['source_id' => '1', 'upazila_id' => $this->upazila->id, 'name' => 'Union', 'bn_name' => 'Union', 'is_active' => true]);
        foreach ([['bd_division_id' => $other->id], ['bd_union_id' => $union->id], ['bd_upazila_id' => 999999]] as $changes) {
            $this->post(route('checkout.address.store'), array_replace($this->address, $changes))->assertSessionHasErrors();
            $this->postJson(route('order.store'), $this->orderPayload($changes))->assertUnprocessable();
        }
        $this->upazila->update(['is_active' => false]);
        $this->post(route('checkout.address.store'), $this->address + ['bd_upazila_id' => $this->upazila->id])->assertSessionHasErrors('bd_upazila_id');
        $this->postJson(route('order.store'), $this->orderPayload(['bd_upazila_id' => $this->upazila->id]))->assertUnprocessable();
    }

    public function test_delivery_zone_rates_overrides_and_free_delivery_are_unchanged(): void
    {
        foreach (['inside_dhaka' => 60, 'outside_dhaka' => 120] as $type => $rate) {
            $zone = DeliveryZone::create(['zone_name' => $type, 'zone_type' => $type, 'delivery_charge' => $rate, 'is_active' => true]);
            $location = $zone->locations()->create(['location_name' => 'Test', 'is_active' => true]);
            $service = app(CheckoutService::class);
            $this->assertSame((float) $rate, $service->resolveCharge($zone->id, $location->id, 100)['delivery_charge']);
            $location->update(['delivery_charge' => 80]);
            $this->assertSame(80.0, $service->resolveCharge($zone->id, $location->id, 100)['delivery_charge']);
            $zone->update(['free_delivery_minimum_amount' => 500]);
            $this->assertSame(0.0, $service->resolveCharge($zone->id, $location->id, 500)['delivery_charge']);
        }
    }

    public function test_retail_cart_can_order_more_than_one_pack(): void
    {
        $kg = Product::where('slug', 'cumin')->firstOrFail()->prices()
            ->where('sell_type', 'retail')->where('quantity_gram', 1000)->firstOrFail();
        $this->priceId = $kg->id;
        $this->post(route('checkout.start'), ['items' => [$this->priceId], 'qty' => [3]])->assertRedirect(route('checkout.review'));
        $this->get(route('checkout.review'))->assertOk()->assertSee('× 3');
        $this->post(route('checkout.address.store'), $this->address)->assertSessionHasNoErrors();
        $this->get(route('checkout.payment'))->assertOk()->assertSee('name="items[0][qty]" value="3"', false);

        $this->postJson(route('order.store'), $this->orderPayload(['items' => [['price_id' => $this->priceId, 'qty' => 3]]]))->assertSuccessful();

        $item = Order::firstOrFail()->items()->firstOrFail();
        $this->assertSame(3000, (int) $item->quantity_gram);
        $this->assertEquals($kg->final_price, $item->unit_price);
        $this->assertEquals($kg->final_price * 3, $item->line_total);
        $this->assertEquals(97, Product::where('slug', 'cumin')->value('stock'));
    }

    public function test_pack_quantity_is_capped(): void
    {
        $this->post(route('checkout.start'), ['items' => [$this->priceId], 'qty' => [CheckoutService::MAX_PACKS_PER_LINE + 1]])
            ->assertSessionHasErrors('qty.0');
    }
}
