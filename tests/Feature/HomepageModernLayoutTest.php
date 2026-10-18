<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentSetting;
use App\Models\Product;
use Tests\TestCase;

class HomepageModernLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    public function test_home_shows_trust_strip_and_picture_categories_with_counts(): void
    {
        $spice = Category::create(['name_bn' => 'মসলা', 'slug' => 'spice', 'is_active' => true]);
        $whole = Category::create(['name_bn' => 'আস্ত মসলা', 'slug' => 'whole', 'parent_id' => $spice->id, 'is_active' => true]);
        foreach ([['jira', $whole->id, 'storage/products/images/jira.webp'], ['elach', $spice->id, null], ['misc', null, null]] as [$slug, $cat, $img]) {
            Product::create(['name_bn' => $slug, 'slug' => $slug, 'stock' => 5, 'retail_price_1kg' => 500, 'category_id' => $cat,
                'main_image' => $img, 'is_active' => true, 'show_in_retail' => true])->syncPrices();
        }
        PaymentSetting::current()->update(['cash_on_delivery_enabled' => true]);

        $res = $this->get('/')->assertOk();
        $res->assertSee('hm-trust', false)->assertSee('ক্যাশ অন ডেলিভারি')->assertSee('বাছাইকৃত খাঁটি মসলা');
        // Sub-category products count towards the parent tile, which uses a product photo.
        $res->assertSee('class="hm-cat"', false)->assertSee('products/images/jira.webp', false)
            ->assertSee('২টি পণ্য')->assertSee('৩টি পণ্য');
        $res->assertSee('hm-card-detail', false)->assertSee('storefront-home.css', false);
    }
}
