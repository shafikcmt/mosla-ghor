<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPrice;
use Tests\TestCase;

class CartPackDropdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    private function product(string $slug, bool $active = true): Product
    {
        $p = Product::create(['name_bn' => 'এলাচ '.$slug, 'slug' => $slug, 'stock' => 100, 'retail_price_1kg' => 6000, 'is_active' => $active, 'show_in_retail' => true]);
        $p->syncPrices();

        return $p;
    }

    public function test_cart_packs_returns_active_retail_pack_sizes_per_product(): void
    {
        $p = $this->product('elach');
        $grams = $p->prices()->where('sell_type', 'retail')->pluck('quantity_gram')->all();
        $this->assertNotEmpty($grams);

        $json = $this->getJson(route('cart.packs', ['ids' => $p->id]))->assertOk()->json();
        $packs = $json[$p->id]['base'];
        $this->assertSame(collect($grams)->sort()->values()->all(), collect($packs)->pluck('quantity_gram')->all()); // ordered by weight
        foreach ($packs as $pack) {
            $this->assertEqualsCanonicalizing(['id', 'label', 'quantity_gram', 'final_price'], array_keys($pack));
        }
    }

    public function test_inactive_prices_inactive_products_and_wholesale_packs_are_not_offered(): void
    {
        $p = $this->product('elach');
        $off = $p->prices()->where('sell_type', 'retail')->first();
        $off->update(['is_active' => false]);
        $hidden = $this->product('hidden-one', false);

        $json = $this->getJson(route('cart.packs', ['ids' => $p->id.','.$hidden->id]))->assertOk()->json();
        $this->assertNotContains($off->id, collect($json[$p->id]['base'])->pluck('id')->all());
        $this->assertArrayNotHasKey($hidden->id, $json);
        $this->assertSame(0, ProductPrice::where('sell_type', 'retail')->where('product_id', $p->id)->where('id', $off->id)->where('is_active', true)->count());
    }

    public function test_cart_packs_handles_empty_and_garbage_ids(): void
    {
        $this->getJson(route('cart.packs'))->assertOk()->assertExactJson([]);
        $this->getJson('/cart/packs?ids=abc,,-5,0')->assertOk()->assertExactJson([]);
    }

    public function test_drawer_and_home_builder_are_wired_for_pack_change(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('msCart.changePack', $html);
        $this->assertStringContainsString('changePack: function', $html);          // home builder bridge
        $this->assertStringContainsString(route('cart.packs'), $html);
    }
}
