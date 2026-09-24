<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\User;
use App\Support\AnnouncementUrl;
use Database\Seeders\HeroSlideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSlideSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_samples_are_idempotent_and_preserve_existing_records_and_edits(): void
    {
        $real = HeroSlide::create(['title'=>'Real campaign', 'image_path'=>'images/product-placeholder.svg',
            'is_active'=>true, 'sort_order'=>50]);
        $before = $real->fresh()->getAttributes();
        $this->seed(HeroSlideSeeder::class);
        $this->assertDatabaseCount('hero_slides', 6);
        $demo = HeroSlide::where('sort_order', 1)->firstOrFail();
        $demo->update(['subtitle'=>'Admin copy', 'sort_order'=>20, 'is_active'=>false]);
        $edited = $demo->fresh()->getAttributes();
        $this->seed(HeroSlideSeeder::class);
        $this->assertDatabaseCount('hero_slides', 6);
        $this->assertSame($before, $real->fresh()->getAttributes());
        $this->assertSame($edited, $demo->fresh()->getAttributes());
    }

    public function test_samples_render_in_order_with_real_images_and_safe_ctas(): void
    {
        $this->seed(HeroSlideSeeder::class);
        $slides = HeroSlide::orderBy('sort_order')->get();
        $this->assertCount(5, $slides);
        $this->assertSame([1, 2, 3, 4, 5], $slides->pluck('sort_order')->all());
        foreach ($slides as $slide) {
            $this->assertTrue($slide->is_active);
            $this->assertFileExists(public_path($slide->image_path));
            foreach ([$slide->primary_url, $slide->secondary_url] as $url) {
                $this->assertTrue(AnnouncementUrl::isValid($url));
                $this->get($url)->assertOk();
            }
        }
        $this->get('/')->assertOk()->assertSeeInOrder($slides->pluck('title')->all())
            ->assertSee('data-dot="4"', false)->assertSee('data-next', false);
    }

    public function test_product_links_use_only_public_products_otherwise_catalogue(): void
    {
        Product::create(['name_bn'=>'Cardamom', 'slug'=>'elach', 'retail_price_1kg'=>500,
            'is_active'=>false, 'show_in_retail'=>true]);
        Product::create(['name_bn'=>'Cumin', 'slug'=>'jira', 'retail_price_1kg'=>500,
            'is_active'=>true, 'show_in_retail'=>true]);
        $this->seed(HeroSlideSeeder::class);
        $this->assertSame('/#products', HeroSlide::where('sort_order', 3)->value('primary_url'));
        $this->assertSame('/products/jira', HeroSlide::where('sort_order', 4)->value('primary_url'));
    }

    public function test_admin_can_edit_disable_reorder_and_replace_demo_artwork_without_deleting_static_asset(): void
    {
        Storage::fake('public');
        $this->seed(HeroSlideSeeder::class);
        $this->actingAs(User::factory()->create(['role'=>'admin', 'is_admin'=>true]));
        $slide = HeroSlide::firstOrFail();
        $this->get(route('admin.hero-slides.index'))->assertOk()->assertSee($slide->title);
        $this->get(route('admin.hero-slides.edit', $slide))->assertOk()->assertSee('product-placeholder.svg');
        $this->put(route('admin.hero-slides.update', $slide), ['title'=>$slide->title,
            'subtitle'=>'Edited sample', 'sort_order'=>9, 'is_active'=>0,
            'image'=>UploadedFile::fake()->image('replacement.png')])->assertSessionHasNoErrors();
        $this->assertFalse($slide->fresh()->is_active);
        $this->assertSame(9, $slide->fresh()->sort_order);
        $this->assertStringStartsWith('storage/hero-slides/', $slide->fresh()->image_path);
        $this->assertFileExists(public_path('images/product-placeholder.svg'));
    }
}
