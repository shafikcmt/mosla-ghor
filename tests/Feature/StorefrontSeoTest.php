<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_canonical_open_graph_and_json_ld(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/').'/">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Store"', false);
    }

    public function test_sitemap_and_robots_are_served_with_absolute_urls(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/').'/</loc>', false);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
            ->assertSee('Disallow: /admin', false);
    }

    public function test_navbar_has_no_duplicate_category_link_and_bottom_nav_targets_categories(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('কম্বো/ব্যাগ', $html);
        $this->assertStringContainsString('href="/#categories"', $html);
    }
}
