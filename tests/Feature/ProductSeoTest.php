<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'short_description' => 'Fresh cumin seeds',
            'retail_price_1kg' => 500, 'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_empty_seo_fields_keep_auto_head_output(): void
    {
        $product = Product::create($this->payload());
        $html = $this->get(route('products.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Cumin — ', $html);
        $this->assertStringContainsString('<meta name="description" content="Fresh cumin seeds">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('products.show', 'cumin').'">', $html);
        $this->assertStringNotContainsString('name="robots"', $html);
    }

    public function test_admin_saves_seo_fields_and_storefront_uses_them(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->post(route('admin.products.store'), $this->payload([
            'meta_title' => 'Buy Cumin Online', 'meta_description' => 'Custom description', 'meta_keywords' => 'cumin, jeera',
            'canonical_url' => 'https://example.com/cumin', 'meta_robots' => 'noindex,follow',
            'og_image_file' => UploadedFile::fake()->image('share.jpg', 1200, 630),
        ]))->assertSessionHasNoErrors();

        $product = Product::where('slug', 'cumin')->firstOrFail();
        $this->assertSame('Buy Cumin Online', $product->meta_title);
        $this->assertSame('noindex,follow', $product->meta_robots);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $product->og_image));

        $html = $this->get(route('products.show', 'cumin'))->assertOk()->getContent();
        $this->assertStringContainsString('<title>Buy Cumin Online</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Custom description">', $html);
        $this->assertStringContainsString('<meta name="keywords" content="cumin, jeera">', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
        $this->assertStringContainsString('href="https://example.com/cumin"', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Buy Cumin Online">', $html);
        $this->assertStringContainsString($product->og_image, $html);

        // Replacing the share image removes the old file after commit.
        $old = str_replace('storage/', '', $product->og_image);
        $this->put(route('admin.products.update', $product), $this->payload(['og_image_file' => UploadedFile::fake()->image('next.jpg')]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $this->assertSame('Buy Cumin Online', $product->fresh()->meta_title, 'Omitted fields must not be cleared.');
    }

    public function test_validation_errors_are_bangla(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.products.store'), $this->payload([
            'meta_title' => str_repeat('x', 71), 'canonical_url' => 'not-a-url', 'meta_robots' => 'bad',
        ]))->assertSessionHasErrors([
            'meta_title' => 'Meta Title সর্বোচ্চ ৭০ অক্ষর হতে পারবে।',
            'canonical_url' => 'Canonical URL একটি সঠিক http/https লিংক হতে হবে।',
            'meta_robots' => 'Robots-এর একটি সঠিক অপশন বেছে নিন।',
        ]);
    }

    public function test_vendor_cannot_set_canonical_or_robots_and_form_hides_them(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $user->id, 'shop_name' => 'QA shop', 'slug' => 'qa-shop', 'owner_name' => 'Owner',
            'phone' => '01700000000', 'email' => 'qa@example.com', 'status' => 'approved', 'is_active' => true]);
        $product = Product::create($this->payload(['vendor_id' => $vendor->id, 'approval_status' => 'approved']));
        $this->actingAs($user);

        $this->get(route('vendor.products.edit', $product))->assertOk()
            ->assertSee('name="meta_title"', false)->assertDontSee('name="canonical_url"', false)->assertDontSee('name="meta_robots"', false);

        $this->put(route('vendor.products.update', $product), $this->payload([
            'meta_title' => 'Vendor title', 'canonical_url' => 'https://evil.example/x', 'meta_robots' => 'noindex,nofollow',
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Vendor title', $product->meta_title);
        $this->assertNull($product->canonical_url);
        $this->assertNull($product->meta_robots);
    }
}
