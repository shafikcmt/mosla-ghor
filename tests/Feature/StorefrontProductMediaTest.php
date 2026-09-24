<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontProductMediaTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        return Product::create([
            'name_bn' => 'Edge artwork', 'slug' => 'edge-artwork', 'stock' => 10,
            'retail_price_1kg' => 500, 'is_active' => true,
            'show_in_retail' => true, 'show_in_wholesale' => true,
            'main_image' => 'products/images/portrait.jpg',
            'gallery_images' => ["https://example.com/brand's-wide.jpg", 'storage/products/images/square.jpg'],
        ]);
    }

    public function test_detail_gallery_resolves_sources_and_preserves_media_data(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create(['name'=>'Small', 'is_active'=>true, 'image'=>'products/variants/small.jpg']);
        $before = $product->fresh()->getAttributes();
        foreach (['products.show', 'customer.wholesale.products.show'] as $route) {
            $response = $this->get(route($route, $product->slug))->assertOk();
            $response->assertSee('data-product-image-stage', false)
                ->assertSee('data-product-thumbnail', false)
                ->assertSee(asset('storage/products/images/portrait.jpg'), false)
                ->assertSee(e("https://example.com/brand's-wide.jpg"), false)
                ->assertSee(asset('storage/products/variants/small.jpg'), false)
                ->assertSee('js/storefront-product-media.js', false);
            $response->assertDontSee('onclick="pdShowImage', false);
        }
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame('products/variants/small.jpg', $variant->fresh()->image);
    }

    public function test_listing_modes_use_shared_contained_media_and_resolved_urls(): void
    {
        $product = $this->product();
        $product->syncPrices();
        foreach (['/', '/?tab=wholesale', '/?view=list', '/?category=unknown&search=artwork'] as $uri) {
            $this->get($uri)->assertOk()
                ->assertSee('product-artwork', false)
                ->assertSee(asset('storage/products/images/portrait.jpg'), false)
                ->assertSee('css/storefront-product-media.css', false);
        }
    }

    public function test_missing_main_image_uses_local_placeholder_and_gallery_still_renders(): void
    {
        $product = $this->product();
        $product->update(['main_image'=>null]);
        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertSee(asset('images/product-placeholder.svg'), false)
            ->assertSee(asset('storage/products/images/square.jpg'), false);
        $this->assertFileExists(public_path('images/product-placeholder.svg'));
        $this->assertFileExists(public_path('css/storefront-product-media.css'));
        $this->assertFileExists(public_path('js/storefront-product-media.js'));
    }
}
