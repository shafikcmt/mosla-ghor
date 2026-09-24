<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_catalogue_keeps_navigation_and_enquiry_available(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="categories"', false)
            ->assertSee('id="wholesale-products"', false)
            ->assertSee(route('wholesale.enquiry-bag'), false)
            ->assertSee('css/storefront-home.css', false)
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('id="selected-products"', false);
    }

    public function test_discovery_is_bounded_and_does_not_duplicate_cart_controls(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Product::create([
                'name_bn' => 'Commerce '.$i, 'slug' => 'commerce-'.$i,
                'retail_price_1kg' => 500, 'is_active' => true, 'show_in_retail' => true,
                'show_in_wholesale' => true, 'stock' => 10,
            ]);
        }
        foreach (['/', '/?mode=wholesale'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(16, substr_count($html, 'data-discovery-product='));
            $this->assertSame(10, substr_count($html, '<article data-card-product='));
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $ids = [];
            foreach ((new \DOMXPath($dom))->query('//*[@id]') as $element) {
                $ids[] = $element->getAttribute('id');
            }
            $this->assertSame(count($ids), count(array_unique($ids)), 'Duplicate IDs: '.implode(', ', array_keys(array_filter(array_count_values($ids), fn ($count) => $count > 1))));
            $this->assertSame(1, substr_count($html, '<h1 '));
        }
    }

    public function test_hidden_products_are_absent_from_discovery(): void
    {
        Product::create(['retail_price_1kg'=>500, 'name_bn'=>'Private product', 'slug'=>'private-product', 'is_active'=>false, 'show_in_retail'=>true]);
        Product::create(['retail_price_1kg'=>500, 'name_bn'=>'No channel', 'slug'=>'no-channel', 'is_active'=>true, 'show_in_retail'=>false, 'show_in_wholesale'=>false]);
        $this->get('/')->assertOk()->assertDontSee('Private product')->assertDontSee('No channel');
    }

    public function test_vendor_discovery_respects_approval_without_lazy_loading(): void
    {
        $user = User::factory()->create(['role'=>'vendor']);
        $vendor = Vendor::create(['user_id'=>$user->id, 'shop_name'=>'QA shop', 'slug'=>'qa-shop', 'owner_name'=>'Owner', 'phone'=>'01700000000', 'email'=>'qa@example.com', 'status'=>'approved', 'is_active'=>true]);
        foreach (['approved', 'pending', 'rejected'] as $status) {
            $product = $vendor->products()->create([
                'name_bn'=>'Vendor '.$status, 'slug'=>'vendor-'.$status,
                'retail_price_1kg'=>500, 'stock'=>10, 'is_active'=>true,
                'show_in_retail'=>true, 'show_in_wholesale'=>true, 'approval_status'=>$status,
                'main_image'=>'products/vendor-'.$status.'.jpg',
            ]);
            $product->syncPrices();
        }
        Model::preventLazyLoading();
        try {
            $this->get('/')->assertOk()->assertSee('Vendor approved')
                ->assertSee('data-hero-count="1"', false)
                ->assertDontSee('Vendor pending')->assertDontSee('Vendor rejected')
                ->assertDontSee('vendor-pending.jpg')->assertDontSee('vendor-rejected.jpg');
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
