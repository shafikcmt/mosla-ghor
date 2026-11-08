<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;

class HomeCategorySectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    private function product(string $slug, ?int $categoryId, array $extra = []): Product
    {
        $p = Product::create($extra + ['name_bn' => 'পণ্য '.$slug, 'slug' => $slug, 'stock' => 50, 'retail_price_1kg' => 500, 'is_active' => true,
            'show_in_retail' => true, 'category_id' => $categoryId]);
        $p->syncPrices();

        return $p;
    }

    private function seedCatalog(): array
    {
        $dry    = Category::create(['name_bn' => 'ড্রাই ফ্রুটস', 'name_en' => 'Dry fruits', 'slug' => 'dry-fruits', 'sort_order' => 2, 'is_active' => true]);
        $masala = Category::create(['name_bn' => 'মশলা', 'name_en' => 'Masala', 'slug' => 'masala', 'sort_order' => 1, 'is_active' => true]);
        $sub    = Category::create(['name_bn' => 'গুঁড়া মশলা', 'name_en' => 'Powder', 'slug' => 'powder', 'parent_id' => $masala->id, 'sort_order' => 1, 'is_active' => true]);
        $this->product('cumin', $masala->id);
        $this->product('turmeric', $sub->id);       // sub-category joins its parent
        $this->product('cashew', $dry->id);
        $this->product('misc', null);                // uncategorised

        return [$masala, $dry];
    }

    public function test_retail_home_groups_products_by_category_in_category_order(): void
    {
        $this->seedCatalog();
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'class="ms-cat-head"') / 2); // card view + list view, 3 groups
        $this->assertStringContainsString('ms-cat-head">মশলা</h3>', $html);
        $this->assertStringContainsString('ms-cat-head">ড্রাই ফ্রুটস</h3>', $html);
        $this->assertStringContainsString('ms-cat-head">অন্যান্য</h3>', $html);
        // sort_order: মশলা (1) before ড্রাই ফ্রুটস (2) before অন্যান্য
        $this->assertTrue(strpos($html, 'ms-cat-head">মশলা') < strpos($html, 'ms-cat-head">ড্রাই ফ্রুটস'));
        $this->assertTrue(strpos($html, 'ms-cat-head">ড্রাই ফ্রুটস') < strpos($html, 'ms-cat-head">অন্যান্য'));
        $this->assertStringContainsString('class="ms-cat-grid', $html);
    }

    public function test_selected_category_is_not_grouped(): void
    {
        $this->seedCatalog();
        $html = $this->get('/?category=masala')->assertOk()->getContent();
        $this->assertStringNotContainsString('class="ms-cat-head"', $html);
        $this->assertStringContainsString('data-card-product', $html);
    }

    public function test_single_group_shows_no_headings(): void
    {
        $this->product('only', null);
        $this->get('/')->assertOk();
        $html = $this->get('/')->getContent();
        $this->assertStringNotContainsString('class="ms-cat-head"', $html);
    }

    public function test_wholesale_only_category_section_is_hidden_in_retail_and_shown_in_wholesale(): void
    {
        [$masala, $dry] = $this->seedCatalog();
        // make every dry-fruit product wholesale-only
        Product::where('category_id', $dry->id)->update(['show_in_retail' => false, 'show_in_wholesale' => true]);

        $retail = $this->get('/')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<section class="ms-cat-section" style="display:none;">\s*<h3 class="ms-cat-head">ড্রাই ফ্রুটস/u', $retail);
        $this->assertStringContainsString('<section id="products" data-mode="retail"', $retail);

        $wholesale = $this->get('/?mode=wholesale')->assertOk()->getContent();
        $this->assertStringContainsString('<section id="products" data-mode="wholesale"', $wholesale);
        $this->assertDoesNotMatchRegularExpression('/<section class="ms-cat-section" style="display:none;">\s*<h3 class="ms-cat-head">ড্রাই ফ্রুটস/u', $wholesale);
    }

    public function test_cards_mark_the_image_block_so_wholesale_mode_can_hide_it(): void
    {
        $this->seedCatalog();
        $html = $this->get('/?mode=wholesale')->assertOk()->getContent();
        $this->assertStringContainsString('ms-card-media', $html);
        $this->assertStringContainsString('ms-list-thumb', $html);
        $this->assertStringContainsString('ms-ws-cat', $html);
        $css = file_get_contents(public_path('css/storefront-home.css'));
        $this->assertStringContainsString('#products[data-mode=wholesale] .ms-card-media', $css);
    }
}
