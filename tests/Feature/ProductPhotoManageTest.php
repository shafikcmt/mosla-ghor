<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Support\TempUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductPhotoManageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
        foreach (['main', 'g1', 'g2'] as $f) {
            Storage::disk('public')->put("products/images/$f.webp", 'x');
        }
    }

    private function product(array $changes = []): Product
    {
        $p = Product::create(array_replace(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false,
            'main_image' => 'storage/products/images/main.webp', 'main_image_alt' => 'মূল',
            'gallery_images' => ['storage/products/images/g1.webp', 'storage/products/images/g2.webp'],
            'gallery_alts' => [hash('sha256', 'storage/products/images/g2.webp') => 'দ্বিতীয়']], $changes));
        $p->syncPrices();
        return $p;
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_editor_shows_every_photo_with_replace_delete_and_make_main(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin())->get(route('admin.products.edit', $p))->assertOk()
            ->assertSee('products/images/g1.webp', false)->assertSee('name="make_main"', false)
            ->assertSee('gallery_replace['.hash('sha256', 'storage/products/images/g2.webp').']', false)
            ->assertSee('ছবি বদলান');
        $empty = $this->product(['slug' => 'empty', 'main_image' => null, 'gallery_images' => null, 'gallery_alts' => null]);
        $this->get(route('admin.products.edit', $empty))->assertOk()->assertSee('এখনো মূল ছবি নেই')->assertSee('গ্যালারিতে এখনো কোনো ছবি নেই');
    }

    public function test_replace_one_gallery_photo_in_place_keeping_its_alt(): void
    {
        $p = $this->product();
        $admin = $this->admin();
        $up = TempUpload::store(UploadedFile::fake()->image('new.jpg', 300, 300), 'gallery', $admin->id);
        $g2 = hash('sha256', 'storage/products/images/g2.webp');

        $this->actingAs($admin)->put(route('admin.products.update', $p), $this->payload(['gallery_replace' => [$g2 => $up['token']]]))
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertCount(2, $p->gallery_images);
        $this->assertSame('storage/products/images/g1.webp', $p->gallery_images[0]);
        $this->assertNotSame('storage/products/images/g2.webp', $p->gallery_images[1], 'Second photo replaced in place.');
        $this->assertSame('দ্বিতীয়', $p->gallery_alts[hash('sha256', $p->gallery_images[1])]);
        $this->assertSame('storage/products/images/main.webp', $p->main_image);
    }

    public function test_make_a_gallery_photo_the_main_photo_swaps_places(): void
    {
        $p = $this->product();
        $g2 = hash('sha256', 'storage/products/images/g2.webp');

        $this->actingAs($this->admin())->put(route('admin.products.update', $p), $this->payload(['make_main' => $g2, 'main_image_alt' => 'মূল']))
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame('storage/products/images/g2.webp', $p->main_image);
        $this->assertSame('দ্বিতীয়', $p->main_image_alt);
        $this->assertSame(['storage/products/images/g1.webp', 'storage/products/images/main.webp'], $p->gallery_images);
        $this->assertSame('মূল', $p->gallery_alts[hash('sha256', 'storage/products/images/main.webp')]);
        Storage::disk('public')->assertExists('products/images/main.webp');
    }

    public function test_remove_main_and_promote_a_gallery_photo(): void
    {
        $p = $this->product();
        $g1 = hash('sha256', 'storage/products/images/g1.webp');

        $this->actingAs($this->admin())->put(route('admin.products.update', $p), $this->payload(['remove_main_image' => 1, 'make_main' => $g1]))
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame('storage/products/images/g1.webp', $p->main_image);
        $this->assertSame(['storage/products/images/g2.webp'], $p->gallery_images);
    }
}
