<?php

namespace Tests\Feature;

use App\Models\BotApiToken;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class PriceBoardAndUnitConversionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function product(array $changes = []): Product
    {
        $p = Product::create(array_replace(['name_bn' => 'গোটা জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 600,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => true, 'wholesale_price_1kg' => 500,
            'min_order_quantity' => 10, 'min_order_unit' => 'kg'], $changes));
        $p->syncPrices();
        return $p;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_unit_conversion_chains_to_kg_and_prices_each_unit(): void
    {
        $p = $this->product(['unit_conversions' => [
            ['unit' => 'carton', 'qty' => 10, 'base' => 'packet'],
            ['unit' => 'packet', 'qty' => 500, 'base' => 'gram'],
            ['unit' => 'bag', 'qty' => 25, 'base' => 'kg'],
        ]]);

        $this->assertSame(5.0, $p->unitInKg('carton'));
        $this->assertSame(0.5, $p->unitInKg('packet'));
        $prices = collect($p->wholesaleUnitPrices())->keyBy('unit');
        $this->assertSame(2500.0, $prices['carton']['price']);
        $this->assertSame(12500.0, $prices['bag']['price']);
        $this->assertSame(['1 কার্টন = 10 প্যাকেট', '1 প্যাকেট = 500 গ্রাম', '1 ব্যাগ = 25 কেজি'], $p->unitConversionLabels());
    }

    public function test_editor_saves_wholesale_price_and_conversions_and_rejects_bad_rows(): void
    {
        $p = $this->product();
        $base = ['name_bn' => 'গোটা জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 600,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 1];

        $this->actingAs($this->admin())->put(route('admin.products.update', $p), $base + [
            'wholesale_price_1kg' => 480,
            'unit_conversions' => [['unit' => 'carton', 'qty' => 20, 'base' => 'kg'], ['unit' => null, 'qty' => null, 'base' => 'kg']],
        ])->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame('480.00', (string) $p->wholesale_price_1kg);
        $this->assertSame([['unit' => 'carton', 'qty' => 20, 'base' => 'kg']], $p->unit_conversions);

        $this->put(route('admin.products.update', $p), $base + [
            'unit_conversions' => [['unit' => 'kg', 'qty' => 2, 'base' => 'kg']],
        ])->assertSessionHasErrors('unit_conversions.0');
    }

    public function test_price_board_lists_and_bulk_updates_prices(): void
    {
        $p = $this->product();
        $admin = $this->admin();
        $packBefore = (float) $p->prices()->where('quantity_gram', 1000)->value('final_price');

        $this->actingAs($admin)->get(route('admin.price-board.index'))->assertOk()->assertSee('গোটা জিরা')->assertSee('পাইকারি দাম');

        $this->post(route('admin.price-board.update'), ['rows' => [$p->id => ['retail_price_1kg' => 800, 'wholesale_price_1kg' => 650]]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $p->refresh();
        $this->assertSame('800.00', (string) $p->retail_price_1kg);
        $this->assertSame('650.00', (string) $p->wholesale_price_1kg);
        $this->assertGreaterThan($packBefore, (float) $p->prices()->where('quantity_gram', 1000)->value('final_price'), 'Retail packs re-sync.');

        $this->post(route('admin.price-board.update'), ['rows' => [$p->id => ['retail_price_1kg' => '']]])
            ->assertSessionHasErrors("rows.{$p->id}.retail_price_1kg");
        $this->post(route('admin.price-board.update'), ['rows' => [999999 => ['wholesale_price_1kg' => 1]]])
            ->assertSessionHasErrors('rows');
    }

    public function test_bot_prices_endpoint_returns_reply_per_customer_type(): void
    {
        $this->product(['unit_conversions' => [['unit' => 'bag', 'qty' => 25, 'base' => 'kg']]]);
        [, $token] = BotApiToken::issue('Bot', ['products:read']);
        $api = $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']);

        $wholesale = $api->getJson('/api/bot/v1/prices?q=জিরা&customer_type=wholesale')->assertOk()->json('data.0');
        $this->assertSame(500.0, (float) $wholesale['wholesale']['price_per_kg']);
        $this->assertStringContainsString('প্রতি ব্যাগ (25 কেজি): ৳12500', $wholesale['reply']);
        $this->assertStringContainsString('সর্বনিম্ন অর্ডার: 10 কেজি', $wholesale['reply']);

        $retail = $api->getJson('/api/bot/v1/prices?q=cumin')->assertOk()->json('data.0');
        $this->assertStringContainsString('খুচরা দাম', $retail['reply']);
        $this->assertStringNotContainsString('পাইকারি', $retail['reply']);

        $api->getJson('/api/bot/v1/prices?customer_type=vip')->assertStatus(422);
    }
}
