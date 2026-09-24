<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_seo_and_next_request_uses_it_without_cache_clear(): void
    {
        $this->get('/')->assertOk();
        $this->actingAs(User::factory()->create(['role'=>'admin', 'is_admin'=>true]));
        $this->post(route('admin.website-settings.update'), [
            'site_name'=>'MoslaMart', 'site_tagline'=>'Spices', 'hero_title'=>'Welcome',
            'meta_title'=>'Shop <Spices>', 'meta_description'=>'Fresh & selected spices',
        ])->assertSessionHasNoErrors();
        $html = $this->get('/')->assertSee('<title>Shop &lt;Spices&gt;</title>', false)
            ->assertSee('content="Fresh &amp; selected spices"', false)->getContent();
        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertSame(1, substr_count($html, 'name="description"'));
        $this->assertSame('Shop <Spices>', WebsiteSetting::get('meta_title'));
    }

    public function test_page_specific_metadata_wins_and_missing_settings_fall_back(): void
    {
        WebsiteSetting::updateOrCreate(['key'=>'meta_title'], ['value'=>'Default title']);
        WebsiteSetting::updateOrCreate(['key'=>'meta_description'], ['value'=>'Default description']);
        $html = view('partials.storefront.seo', ['pageTitle'=>'Product', 'pageDescription'=>'Product description'])->render();
        $this->assertStringContainsString('Product — '.e(WebsiteSetting::siteName()), $html);
        $this->assertStringContainsString('content="Product description"', $html);
        WebsiteSetting::updateOrCreate(['key'=>'meta_title'], ['value'=>'']);
        WebsiteSetting::updateOrCreate(['key'=>'meta_description'], ['value'=>'']);
        $this->get('/')->assertOk()->assertSee('<title>'.e(WebsiteSetting::siteName()), false);
    }

    public function test_seo_lengths_are_validated(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin', 'is_admin'=>true]));
        $this->post(route('admin.website-settings.update'), ['site_name'=>'Shop', 'hero_title'=>'Welcome',
            'meta_title'=>str_repeat('a', 71), 'meta_description'=>str_repeat('a', 201)])
            ->assertSessionHasErrors(['meta_title', 'meta_description']);
    }

    public function test_product_and_faq_keep_one_page_specific_description(): void
    {
        $product = \App\Models\Product::create(['name_bn'=>'Spice', 'slug'=>'seo-spice', 'retail_price_1kg'=>500,
            'is_active'=>true, 'show_in_retail'=>true, 'short_description'=>'Page-specific spice description']);
        foreach (['/faq', route('products.show', $product->slug)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'name="description"'));
            $this->assertSame(1, substr_count($html, '<title>'));
        }
    }
}
