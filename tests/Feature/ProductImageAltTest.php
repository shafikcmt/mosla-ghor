<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageAltTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
    }

    private function product(array $changes = []): Product
    {
        $p = Product::create(array_replace(['name_bn' => 'গোটা জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false,
            'main_image' => 'storage/products/images/main.webp',
            'gallery_images' => ['storage/products/images/g1.webp', 'storage/products/images/g2.webp']], $changes));
        $p->syncPrices();
        return $p;
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'গোটা জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_admin_saves_alt_texts_and_only_for_existing_gallery_images(): void
    {
        $product = $this->product();
        $variant = ProductVariant::create(['product_id' => $product->id, 'name' => '৫০০ গ্রাম']);
        $h1 = hash('sha256', 'storage/products/images/g1.webp');

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            'main_image_alt' => 'গোটা জিরা ২৫০ গ্রাম প্যাক',
            'og_image_alt' => 'মসলামার্টের গোটা জিরা',
            'gallery_alts' => [$h1 => 'জিরার ক্লোজআপ', str_repeat('a', 64) => 'unknown image'],
            'variants' => [$variant->id => ['name' => '৫০০ গ্রাম', 'image_alt' => 'জিরা ৫০০ গ্রাম']],
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('গোটা জিরা ২৫০ গ্রাম প্যাক', $product->main_image_alt);
        $this->assertSame('মসলামার্টের গোটা জিরা', $product->og_image_alt);
        $this->assertSame([$h1 => 'জিরার ক্লোজআপ'], $product->gallery_alts, 'Only keys of current gallery images are kept.');
        $this->assertSame('জিরা ৫০০ গ্রাম', $variant->fresh()->image_alt);
    }

    public function test_removing_a_gallery_image_drops_its_alt(): void
    {
        $h1 = hash('sha256', 'storage/products/images/g1.webp');
        $h2 = hash('sha256', 'storage/products/images/g2.webp');
        $product = $this->product(['gallery_alts' => [$h1 => 'এক', $h2 => 'দুই']]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload(['remove_gallery' => [$h1]]))
            ->assertSessionHasNoErrors();

        $this->assertSame([$h2 => 'দুই'], $product->fresh()->gallery_alts);
    }

    public function test_angle_brackets_are_rejected_in_bangla(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            'main_image_alt' => '<script>x</script>',
        ]))->assertSessionHasErrors(['main_image_alt' => 'মূল ছবির বিবরণ (alt)-এ < বা > চিহ্ন দেওয়া যাবে না।']);
    }

    public function test_product_page_uses_custom_alts_and_fallbacks_and_og_image_alt(): void
    {
        $h2 = hash('sha256', 'storage/products/images/g2.webp');
        $product = $this->product(['main_image_alt' => 'গোটা জিরা প্যাক', 'gallery_alts' => [$h2 => 'জিরার দানা']]);

        $html = $this->get('/products/cumin')->assertOk()->getContent();
        $this->assertStringContainsString('id="pd-main-image" src="', $html);
        $this->assertStringContainsString('alt="গোটা জিরা প্যাক"', $html);
        $this->assertStringContainsString('alt="গোটা জিরা — ছবি ২"', $html, 'Gallery fallback: name — ছবি N.');
        $this->assertStringContainsString('alt="জিরার দানা"', $html);
        $this->assertStringContainsString('<meta property="og:image:alt" content="গোটা জিরা প্যাক">', $html, 'og alt falls back to main alt.');

        $product->update(['og_image_alt' => 'শেয়ার ছবি']);
        $this->assertStringContainsString('<meta property="og:image:alt" content="শেয়ার ছবি">', $this->get('/products/cumin')->getContent());
    }

    public function test_empty_alts_fall_back_to_product_name(): void
    {
        $product = $this->product();
        $this->assertSame('গোটা জিরা', $product->mainImageAlt());
        $this->assertSame('গোটা জিরা', $product->ogImageAlt());
        $this->assertSame('গোটা জিরা — ছবি ৩', $product->imageAlt('storage/products/images/g2.webp', 3));
        $variant = ProductVariant::create(['product_id' => $product->id, 'name' => 'বড়']);
        $this->assertSame('গোটা জিরা — বড়', $variant->imageAlt('গোটা জিরা'));
    }

    public function test_editor_shows_alt_fields(): void
    {
        $product = $this->product(['main_image_alt' => 'গোটা জিরা প্যাক']);
        $this->actingAs($this->admin())->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('name="main_image_alt"', false)->assertSee('value="গোটা জিরা প্যাক"', false)
            ->assertSee('name="og_image_alt"', false)
            ->assertSee('name="gallery_alts['.hash('sha256', 'storage/products/images/g1.webp').']"', false);
    }
}
