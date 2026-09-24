<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteIdentityHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_name_is_escaped_and_shared_without_repeated_settings_queries(): void
    {
        WebsiteSetting::updateOrCreate(['key'=>'site_name'], ['value'=>'Nafi <Food>']);
        DB::enableQueryLog();
        $this->get('/')->assertOk()->assertSee('<title>Nafi &lt;Food&gt;', false)
            ->assertSee('title="Nafi &lt;Food&gt;"', false)->assertDontSee('Nafi <Food>', false);
        $queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "website_settings"'));
        $this->assertCount(1, $queries);
        DB::disableQueryLog();
        $this->get(route('customer.login'))->assertOk()->assertSee('Nafi &lt;Food&gt;', false);
    }

    public function test_missing_and_empty_names_use_app_name(): void
    {
        config(['app.name'=>'Fallback Store']);
        $this->get('/')->assertOk()->assertSee('<title>Fallback Store', false);
        WebsiteSetting::updateOrCreate(['key'=>'site_name'], ['value'=>'   ']);
        $this->get('/')->assertOk()->assertSee('<title>Fallback Store', false);
    }

    public function test_admin_can_save_identity_and_the_next_request_sees_it(): void
    {
        WebsiteSetting::updateOrCreate(['key'=>'site_name'], ['value'=>'Old store']);
        $this->get('/')->assertOk()->assertSee('Old store');
        $admin = User::factory()->create(['role'=>'admin', 'is_admin'=>true]);
        $this->actingAs($admin)->post(route('admin.website-settings.update'), [
            'site_name'=>'MoslaMart', 'site_tagline'=>'Retail and wholesale', 'hero_title'=>'Spices for your kitchen',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.website-settings.index'));
        $this->get('/')->assertOk()->assertSee('MoslaMart')->assertSee('Retail and wholesale')->assertDontSee('Old store');
        $this->assertDatabaseHas('website_settings', ['key'=>'site_name', 'value'=>'MoslaMart']);
    }

    public function test_invalid_names_are_rejected_without_changing_saved_name(): void
    {
        WebsiteSetting::updateOrCreate(['key'=>'site_name'], ['value'=>'Saved store']);
        $this->actingAs(User::factory()->create(['role'=>'admin', 'is_admin'=>true]));
        foreach (['', str_repeat('x', 101), ['invalid']] as $name) {
            $this->post(route('admin.website-settings.update'), ['site_name'=>$name, 'hero_title'=>'Spices'])
                ->assertSessionHasErrors('site_name');
            $this->assertDatabaseHas('website_settings', ['key'=>'site_name', 'value'=>'Saved store']);
        }
    }

    public function test_hero_placeholder_and_configured_banner_fallbacks(): void
    {
        $this->get('/')->assertOk()->assertSee('hm-hero-placeholder', false);
        WebsiteSetting::updateOrCreate(['key'=>'hero_image_url'], ['value'=>'https://example.com/banner.jpg']);
        $this->get('/')->assertOk()->assertSee('hm-hero-banner', false)->assertSee('https://example.com/banner.jpg', false);
    }

    public function test_hero_uses_at_most_three_public_product_images(): void
    {
        for ($i=1; $i<=4; $i++) {
            Product::create(['name_bn'=>'Spice '.$i, 'slug'=>'spice-'.$i, 'retail_price_1kg'=>500,
                'is_active'=>true, 'show_in_retail'=>true, 'main_image'=>'products/spice-'.$i.'.jpg']);
        }
        $html = $this->get('/')->assertOk()->assertSee('data-hero-count="3"', false)->getContent();
        $this->assertSame(3, substr_count($html, 'class="hm-hero-product"'));
        $this->assertStringContainsString(asset('storage/products/spice-4.jpg'), $html);
    }

    public function test_one_image_hero_has_one_heading_and_unique_ids(): void
    {
        Product::create(['name_bn'=>'Public spice', 'slug'=>'public-spice', 'retail_price_1kg'=>500,
            'is_active'=>true, 'show_in_retail'=>true, 'main_image'=>'images/public-spice.jpg']);
        WebsiteSetting::updateOrCreate(['key'=>'hero_badge_text'], ['value'=>'ঈদ স্পেশাল কালেকশন']);
        $html = $this->get('/')->assertOk()->assertSee('data-hero-count="1"', false)
            ->assertDontSee('ঈদ স্পেশাল কালেকশন')->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $this->assertSame(1, $dom->getElementsByTagName('h1')->length);
        $ids = [];
        foreach ((new \DOMXPath($dom))->query('//*[@id]') as $node) {
            $ids[] = $node->getAttribute('id');
        }
        $this->assertCount(count(array_unique($ids)), $ids);
    }
}
