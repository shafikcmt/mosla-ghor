<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProductQuickEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function product(array $changes = []): Product
    {
        $product = Product::create(array_replace(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false, 'sort_order' => 3], $changes));
        if ($product->show_in_retail) {
            $product->syncPrices();
        }
        return $product;
    }

    private function pack(Product $product, int $grams)
    {
        return $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->where('quantity_gram', $grams)->first();
    }

    public function test_list_renders_quick_edit_button_and_dialog(): void
    {
        $this->product();
        $this->actingAs($this->admin())->get(route('admin.products.index'))->assertOk()
            ->assertSee('দ্রুত সম্পাদনা')->assertSee('data-qe-dialog', false)->assertSee('admin-product-quick-edit.js', false);
    }

    public function test_price_change_updates_packs_but_keeps_manual_overrides(): void
    {
        Log::spy();
        $product = $this->product();
        $this->pack($product, 250)->update(['manual_price' => 99, 'final_price' => 99, 'is_manual_override' => true]);

        $response = $this->actingAs($this->admin())->patchJson(route('admin.products.quick-update', $product), ['retail_price_1kg' => 800])
            ->assertOk()
            ->assertJsonPath('product.retail_price_1kg', 800)
            ->assertJsonPath('stats.total', 1);

        $this->assertEquals(800, (float) $this->pack($product, 1000)->final_price);
        $this->assertEquals(80, (float) $this->pack($product, 100)->final_price);
        $manual = $this->pack($product, 250);
        $this->assertEquals(99, (float) $manual->final_price, 'Manual override pack must keep its price.');
        $this->assertEquals(200, (float) $manual->auto_price, 'Auto price still refreshes underneath the override.');

        $packs = collect($response->json('packs'));
        $this->assertEquals(800, $packs->firstWhere('label', '১ কেজি')['final_price']);
        $this->assertTrue($packs->firstWhere('label', '২৫০ গ্রাম')['is_manual_override']);

        Log::shouldHaveReceived('info')->withArgs(fn ($msg, $ctx) => $msg === 'Admin product quick edit'
            && $ctx['product_id'] === $product->id && (float) $ctx['changes']['retail_price_1kg']['new'] === 800.0);
    }

    public function test_non_price_fields_update_without_touching_other_data(): void
    {
        $product = $this->product(['short_description' => 'keep me', 'meta_title' => 'Keep SEO']);
        $before = $this->pack($product, 1000)->final_price;

        $this->actingAs($this->admin())->patchJson(route('admin.products.quick-update', $product), [
            'stock' => 0, 'low_stock_threshold' => 2.5, 'sort_order' => 7, 'is_active' => false, 'show_in_wholesale' => true,
        ])->assertOk()->assertJsonPath('stats.active', 0);

        $product->refresh();
        $this->assertSame(0, $product->stock);
        $this->assertEquals(2.5, (float) $product->low_stock_threshold);
        $this->assertSame(7, $product->sort_order);
        $this->assertFalse($product->is_active);
        $this->assertTrue($product->show_in_wholesale);
        $this->assertFalse($product->is_wholesale, 'Retail + wholesale is not wholesale-only.');
        $this->assertSame('keep me', $product->short_description);
        $this->assertSame('Keep SEO', $product->meta_title);
        $this->assertEquals((float) $before, (float) $this->pack($product, 1000)->final_price);
    }

    public function test_validation_errors_are_bangla(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->patchJson(route('admin.products.quick-update', $product), [
            'retail_price_1kg' => 'abc', 'stock' => -1, 'sort_order' => 1.5, 'low_stock_threshold' => -2,
        ])->assertStatus(422)->assertJsonValidationErrors([
            'retail_price_1kg' => 'খুচরা দাম একটি সংখ্যা হতে হবে।',
            'stock' => 'স্টক ০ বা তার বেশি হতে হবে।',
            'sort_order' => 'ক্রম পূর্ণ সংখ্যা হতে হবে।',
            'low_stock_threshold' => 'কম স্টকের সীমা ০ বা তার বেশি হতে হবে।',
        ]);
        $this->assertEquals(500, (float) $product->fresh()->retail_price_1kg);
    }

    public function test_both_channels_off_is_rejected(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->patchJson(route('admin.products.quick-update', $product), ['show_in_retail' => false, 'show_in_wholesale' => false])
            ->assertStatus(422)->assertJsonValidationErrors(['show_in_retail' => 'অন্তত একটি বিক্রয় মাধ্যম (খুচরা বা পাইকারি) চালু রাখুন।']);
        $this->assertTrue($product->fresh()->show_in_retail);
    }

    public function test_turning_retail_on_requires_a_price_and_then_creates_packs(): void
    {
        $product = $this->product(['show_in_retail' => false, 'show_in_wholesale' => true, 'retail_price_1kg' => 0, 'is_wholesale' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->patchJson(route('admin.products.quick-update', $product), ['show_in_retail' => true])
            ->assertStatus(422)->assertJsonValidationErrors(['retail_price_1kg' => 'খুচরা চালু করতে ১ কেজির দাম দিন।']);

        $this->actingAs($admin)->patchJson(route('admin.products.quick-update', $product), ['show_in_retail' => true, 'retail_price_1kg' => 400])
            ->assertOk()->assertJsonPath('stats.retail', 1);
        $product->refresh();
        $this->assertTrue($product->show_in_retail);
        $this->assertFalse($product->is_wholesale);
        $this->assertEquals(400, (float) $this->pack($product, 1000)->final_price);
    }

    public function test_variant_products_allow_only_stock_active_and_visibility(): void
    {
        $product = $this->product();
        ProductVariant::create(['product_id' => $product->id, 'name' => 'Large']);
        $admin = $this->admin();

        $this->actingAs($admin)->patchJson(route('admin.products.quick-update', $product), ['retail_price_1kg' => 900])
            ->assertStatus(422)->assertJsonValidationErrors(['retail_price_1kg' => 'ভ্যারিয়েন্টের দাম পূর্ণ সম্পাদনায় বদলান।']);

        $this->actingAs($admin)->patchJson(route('admin.products.quick-update', $product), ['stock' => 25, 'is_active' => false])
            ->assertOk()->assertJsonPath('product.has_variants', true)->assertJsonPath('product.stock', 25);
        $this->assertEquals(500, (float) $product->fresh()->retail_price_1kg);
    }

    public function test_non_admin_gets_403_and_guest_401(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create(['role' => 'customer', 'is_admin' => false]))
            ->patchJson(route('admin.products.quick-update', $product), ['stock' => 1])->assertForbidden();
        $this->assertAuthenticated();

        auth()->logout();
        $this->patchJson(route('admin.products.quick-update', $product), ['stock' => 1])->assertUnauthorized();
        $this->assertSame(10, $product->fresh()->stock);
    }
}
