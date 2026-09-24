<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSlideTest extends TestCase
{
    use RefreshDatabase;

    private function slide(array $values = []): HeroSlide
    {
        return HeroSlide::create(array_merge(['title'=>'Public slide', 'image_path'=>'storage/hero-slides/old.jpg',
            'is_active'=>true, 'sort_order'=>1], $values));
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin', 'is_admin'=>true]));
    }

    public function test_active_slides_are_ordered_with_one_heading_and_unique_ids(): void
    {
        $this->slide(['title'=>'Second slide', 'sort_order'=>2]);
        $this->slide(['title'=>'First slide', 'sort_order'=>0]);
        $this->slide(['title'=>'Hidden slide', 'is_active'=>false]);
        $html = $this->get('/')->assertOk()->assertSeeInOrder(['First slide', 'Second slide'])
            ->assertDontSee('Hidden slide')->assertSee('data-next', false)->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $this->assertSame(1, $dom->getElementsByTagName('h1')->length);
        $ids = [];
        foreach ((new \DOMXPath($dom))->query('//*[@id]') as $node) { $ids[] = $node->getAttribute('id'); }
        $this->assertCount(count(array_unique($ids)), $ids);
    }

    public function test_single_slide_has_no_controls_and_zero_slides_use_existing_fallback(): void
    {
        $this->get('/')->assertOk()->assertSee('hm-hero-placeholder', false);
        $slide = $this->slide();
        $this->get('/')->assertOk()->assertSee('Public slide')->assertDontSee('data-next', false)->assertDontSee('data-dot', false);
        $slide->update(['is_active'=>false]);
        $this->get('/')->assertSee('hm-hero-placeholder', false)->assertDontSee('Public slide');
    }

    public function test_admin_can_create_edit_reorder_disable_replace_and_delete(): void
    {
        Storage::fake('public');
        $this->admin();
        $this->get(route('admin.hero-slides.index'))->assertOk();
        $this->get(route('admin.hero-slides.create'))->assertOk();
        $payload = ['title'=>'New slide', 'sort_order'=>3, 'is_active'=>1,
            'primary_label'=>'Products', 'primary_url'=>'/#products'];
        $this->post(route('admin.hero-slides.store'), $payload + ['image'=>UploadedFile::fake()->image('hero.png')])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.hero-slides.index'));
        $slide = HeroSlide::firstOrFail();
        $old = $slide->image_path;
        Storage::disk('public')->assertExists(substr($old, 8));
        $this->get(route('admin.hero-slides.edit', $slide))->assertOk();
        $this->put(route('admin.hero-slides.update', $slide), array_merge($payload, ['sort_order'=>0, 'is_active'=>0]))->assertSessionHasNoErrors();
        $this->assertSame($old, $slide->fresh()->image_path);
        $this->assertFalse($slide->fresh()->is_active);
        $this->assertSame(0, $slide->fresh()->sort_order);
        $this->put(route('admin.hero-slides.update', $slide), $payload + ['image'=>UploadedFile::fake()->image('replacement.jpg')])->assertSessionHasNoErrors();
        $new = $slide->fresh()->image_path;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing(substr($old, 8));
        Storage::disk('public')->assertExists(substr($new, 8));
        $this->delete(route('admin.hero-slides.destroy', $slide))->assertRedirect();
        Storage::disk('public')->assertMissing(substr($new, 8));
        $this->assertDatabaseCount('hero_slides', 0);
    }

    public function test_invalid_uploads_and_unsafe_ctas_are_rejected(): void
    {
        Storage::fake('public');
        $this->admin();
        foreach ([UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml'), UploadedFile::fake()->image('huge.jpg')->size(5121)] as $image) {
            $this->post(route('admin.hero-slides.store'), ['title'=>'Bad', 'sort_order'=>0, 'image'=>$image])->assertSessionHasErrors('image');
        }
        $slide = $this->slide();
        foreach (['javascript:alert(1)', 'data:text/html,test', '//example.com'] as $url) {
            $this->put(route('admin.hero-slides.update', $slide), ['title'=>'Bad', 'sort_order'=>0, 'primary_label'=>'Go', 'primary_url'=>$url])->assertSessionHasErrors('primary_url');
        }
        $this->assertSame('Public slide', $slide->fresh()->title);
    }

    public function test_failed_database_save_preserves_old_image_and_removes_new_upload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('hero-slides/old.jpg', 'original');
        $this->admin();
        $slide = $this->slide();
        HeroSlide::updating(function () { throw new \RuntimeException('Simulated DB failure'); });
        try {
            $this->put(route('admin.hero-slides.update', $slide), ['title'=>'Replacement', 'sort_order'=>0,
                'image'=>UploadedFile::fake()->image('new.jpg')])->assertStatus(500);
            $this->assertSame(['hero-slides/old.jpg'], Storage::disk('public')->allFiles('hero-slides'));
            $this->assertSame('storage/hero-slides/old.jpg', $slide->fresh()->image_path);
        } finally { HeroSlide::flushEventListeners(); }
    }

    public function test_delete_preserves_shared_and_unowned_media_and_requires_admin(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('hero-slides/old.jpg', 'original');
        $slide = $this->slide();
        $this->slide();
        $this->delete(route('admin.hero-slides.destroy', $slide))->assertRedirect();
        $this->assertDatabaseCount('hero_slides', 2);
        $this->admin();
        $this->delete(route('admin.hero-slides.destroy', $slide));
        Storage::disk('public')->assertExists('hero-slides/old.jpg');
        Storage::disk('public')->put('products/keep.jpg', 'original');
        $unowned = $this->slide(['image_path'=>'storage/products/keep.jpg']);
        $this->delete(route('admin.hero-slides.destroy', $unowned));
        Storage::disk('public')->assertExists('products/keep.jpg');
    }
}
