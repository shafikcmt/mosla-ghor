<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ImageOptimizer;
use App\Support\TempUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TempUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
        ImageOptimizer::$forceWebp = true;
    }

    protected function tearDown(): void
    {
        ImageOptimizer::$forceWebp = null;
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function product(array $changes = []): Product
    {
        return Product::create(array_replace(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false], $changes));
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    private function upload(User $user, string $kind = 'main', string $route = 'admin.products.uploads'): array
    {
        return $this->actingAs($user)->postJson(route($route), [
            'kind' => $kind, 'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertOk()->assertJsonStructure(['token', 'url'])->json();
    }

    public function test_upload_endpoint_stores_optimized_temp_file_and_returns_token(): void
    {
        $admin = $this->admin();
        $json = $this->upload($admin);
        $path = TempUpload::resolve($json['token'], $admin->id, 'main');

        $this->assertNotNull($path);
        $this->assertStringStartsWith("products/tmp/{$admin->id}/", $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString($path, $json['url']);
    }

    public function test_saving_with_tokens_moves_files_into_place_and_removes_temp(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $main = $this->upload($admin, 'main');
        $og = $this->upload($admin, 'og');
        $g1 = $this->upload($admin, 'gallery');
        $g2 = $this->upload($admin, 'gallery');
        $tmpMain = TempUpload::resolve($main['token'], $admin->id, 'main');

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload([
            'main_image_token' => $main['token'], 'og_image_token' => $og['token'],
            'gallery_tokens' => [$g1['token'], $g2['token']],
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('storage/products/images/'.basename($tmpMain), $product->main_image);
        $this->assertStringStartsWith('storage/products/images/', $product->og_image);
        $this->assertCount(2, $product->gallery_images);
        foreach (array_merge([$product->main_image, $product->og_image], $product->gallery_images) as $stored) {
            Storage::disk('public')->assertExists(substr($stored, strlen('storage/')));
        }
        Storage::disk('public')->assertMissing($tmpMain);
        $this->assertSame([], Storage::disk('public')->allFiles("products/tmp/{$admin->id}"), 'Temp files removed after commit.');
    }

    public function test_variant_image_token(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $variant = ProductVariant::create(['product_id' => $product->id, 'name' => 'Large']);
        $t = $this->upload($admin, 'variant');

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload([
            'variants' => [$variant->id => ['name' => 'Large', 'image_token' => $t['token']]],
        ]))->assertSessionHasNoErrors();

        $this->assertStringStartsWith('storage/products/variants/', $variant->fresh()->image);
    }

    public function test_another_users_token_is_rejected_and_nothing_moves(): void
    {
        $owner = $this->admin();
        $other = $this->admin();
        $product = $this->product();
        $t = $this->upload($owner);
        $tmp = TempUpload::resolve($t['token'], $owner->id, 'main');

        $this->actingAs($other)->put(route('admin.products.update', $product), $this->payload(['main_image_token' => $t['token']]))
            ->assertSessionHasErrors(['main_image_token' => 'মূল ছবি-এর আপলোডের মেয়াদ শেষ বা অবৈধ — ছবিটি আবার বেছে নিন।']);
        $this->assertNull($product->fresh()->main_image);
        Storage::disk('public')->assertExists($tmp);
    }

    public function test_expired_tampered_and_wrong_kind_tokens_are_rejected(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $t = $this->upload($admin, 'main');

        // Wrong kind (a main-image token used as the share image).
        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['og_image_token' => $t['token']]))
            ->assertSessionHasErrors('og_image_token');

        // Tampered: a hand-made token pointing at someone else's file.
        $forged = Crypt::encryptString(json_encode(['p' => 'products/images/other.webp', 'u' => $admin->id, 'k' => 'main', 'e' => time() + 60]));
        $this->assertNull(TempUpload::resolve($forged, $admin->id, 'main'));
        $this->assertNull(TempUpload::resolve('not-a-token', $admin->id, 'main'));

        // Expired.
        $this->travel(13)->hours();
        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['main_image_token' => $t['token']]))
            ->assertSessionHasErrors('main_image_token');
        $this->assertNull($product->fresh()->main_image);
    }

    public function test_upload_endpoint_validates_and_uses_bangla_errors(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.products.uploads'), [
            'kind' => 'main', 'file' => UploadedFile::fake()->create('notes.txt', 2, 'text/plain'),
        ])->assertStatus(422)->assertJsonPath('errors.file.0', 'মূল ছবি একটি ছবি হতে হবে (JPG, PNG বা WebP)।');

        $this->actingAs($this->admin())->postJson(route('admin.products.uploads'), [
            'kind' => '../../etc', 'file' => UploadedFile::fake()->image('a.jpg'),
        ])->assertStatus(422)->assertJsonValidationErrors('kind');
    }

    public function test_vendor_upload_requires_approved_vendor(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $user->id, 'shop_name' => 'QA', 'slug' => 'qa', 'owner_name' => 'O',
            'phone' => '01700000001', 'email' => 'qa@example.com', 'status' => 'approved', 'is_active' => true]);
        $this->upload($user, 'gallery', 'vendor.products.uploads');

        $vendor->update(['status' => 'pending']);
        $this->actingAs($user->fresh())->postJson(route('vendor.products.uploads'), ['kind' => 'main', 'file' => UploadedFile::fake()->image('a.jpg')])
            ->assertForbidden();

        auth()->logout();
        $this->postJson(route('admin.products.uploads'), ['kind' => 'main', 'file' => UploadedFile::fake()->image('a.jpg')])
            ->assertUnauthorized();
    }

    public function test_cleanup_removes_only_stale_temp_files(): void
    {
        $admin = $this->admin();
        $fresh = TempUpload::resolve($this->upload($admin)['token'], $admin->id, 'main');
        Storage::disk('public')->put('products/tmp/99/old.webp', 'x');
        touch(Storage::disk('public')->path('products/tmp/99/old.webp'), time() - 2 * 86400);
        Storage::disk('public')->put('products/images/keep.webp', 'x');
        touch(Storage::disk('public')->path('products/images/keep.webp'), time() - 30 * 86400);

        $this->artisan('uploads:clean-temp')->expectsOutputToContain('Removed 1')->assertExitCode(0);

        Storage::disk('public')->assertMissing('products/tmp/99/old.webp');
        Storage::disk('public')->assertExists($fresh);
        Storage::disk('public')->assertExists('products/images/keep.webp');
    }

    public function test_tokens_survive_a_validation_error_on_the_form(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $t = $this->upload($admin, 'gallery');

        $this->actingAs($admin)->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $this->payload(['name_bn' => '', 'gallery_tokens' => [$t['token']]]))
            ->assertSessionHasErrors('name_bn');

        $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertSee('আগে আপলোড করা ছবি — সংরক্ষণ করলে যোগ হবে')
            ->assertSee('name="gallery_tokens[]" value="'.$t['token'].'"', false);
    }

    public function test_editor_exposes_upload_endpoint_and_direct_file_fallback_still_works(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('data-upload-url="'.route('admin.products.uploads').'"', false)
            ->assertSee('data-async-upload="gallery"', false);

        // No JavaScript: the plain file input still saves.
        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload([
            'main_image_file' => UploadedFile::fake()->image('direct.jpg', 400, 300),
        ]))->assertSessionHasNoErrors();
        $this->assertStringStartsWith('storage/products/images/', $product->fresh()->main_image);
    }
}
