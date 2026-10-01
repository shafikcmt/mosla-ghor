<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WholesaleCoverImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
    }

    private function product(array $changes = []): Product
    {
        $p = Product::create(array_replace(['name_bn' => 'গোটা জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => true,
            'main_image' => 'storage/products/images/main.webp',
            'gallery_images' => ['storage/products/images/g1.webp']], $changes));
        $p->syncPrices();
        return $p;
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'গোটা জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 1], $changes);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_cover_falls_back_to_main_image_until_a_wholesale_cover_is_set(): void
    {
        $product = $this->product();
        $this->assertSame($product->main_image, $product->coverImage('wholesale'));

        $product->wholesale_main_image = 'storage/products/images/ws.webp';
        $this->assertSame('storage/products/images/ws.webp', $product->coverImage('wholesale'));
        $this->assertSame($product->main_image, $product->coverImage('retail'));
    }

    public function test_admin_uploads_and_removes_wholesale_cover_without_touching_main_or_gallery(): void
    {
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload([
            'wholesale_main_image_file' => UploadedFile::fake()->image('ws.jpg', 400, 400),
            'wholesale_main_image_alt' => 'পাইকারি বস্তা',
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertNotNull($product->wholesale_main_image);
        $this->assertSame('storage/products/images/main.webp', $product->main_image);
        $this->assertSame(['storage/products/images/g1.webp'], $product->gallery_images);
        $this->assertSame('পাইকারি বস্তা', $product->coverImageAlt('wholesale'));

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['remove_wholesale_main_image' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->wholesale_main_image);
    }

    public function test_wholesale_detail_page_shows_wholesale_cover_and_retail_page_shows_main(): void
    {
        $this->product(['wholesale_main_image' => 'storage/products/images/ws.webp']);

        $this->get(route('products.show', ['product' => 'cumin', 'mode' => 'wholesale']))->assertOk()->assertSee('products/images/ws.webp', false);
        $retail = $this->get(route('products.show', 'cumin'))->assertOk();
        $retail->assertSee('products/images/main.webp', false);
        $retail->assertDontSee('products/images/ws.webp', false);
    }

    public function test_home_cards_carry_both_covers_for_the_mode_toggle(): void
    {
        $this->product(['wholesale_main_image' => 'storage/products/images/ws.webp']);

        $this->get('/?mode=wholesale')->assertOk()
            ->assertSee('data-cover-wholesale', false)
            ->assertSee('products/images/ws.webp', false)
            ->assertSee('products/images/main.webp', false);
    }
}
