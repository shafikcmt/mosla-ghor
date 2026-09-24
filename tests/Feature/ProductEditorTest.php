<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductEditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Each application uses a fresh in-memory database; no rollback of deployed migrations.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_admin' => true]));
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10,
            'retail_price_1kg' => 500, 'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    private function product(array $changes = []): Product
    {
        return Product::create($this->payload($changes));
    }

    public function test_admin_create_and_edit_render_with_real_assets(): void
    {
        $product = $this->product();
        foreach (['/admin/products/create', '/admin/products/'.$product->id.'/edit'] as $url) {
            $this->get($url)->assertOk()->assertSee('css/product-editor.css')->assertSee('js/product-editor.js');
        }
        $this->assertFileExists(public_path('css/product-editor.css'));
        $this->assertFileExists(public_path('js/product-editor.js'));
    }

    public function test_channel_sections_and_variant_attributes_render_for_each_mode(): void
    {
        $product = $this->product();
        $product->variants()->create(['name'=>'Large', 'attributes'=>[['name'=>'Size', 'value'=>'Large']]]);
        foreach ([[1,0], [0,1], [1,1]] as [$retail, $wholesale]) {
            $product->update(['show_in_retail'=>$retail, 'show_in_wholesale'=>$wholesale]);
            $response = $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Size')->assertSee('Large');
            foreach (['retail'=>$retail, 'wholesale'=>$wholesale] as $channel=>$enabled) {
                $pattern = '/<fieldset[^>]*data-channel-section="'.$channel.'"([^>]*)>/';
                preg_match_all($pattern, $response->getContent(), $matches);
                $this->assertNotEmpty($matches[1]);
                foreach ($matches[1] as $attributes) {
                    $this->assertSame(!$enabled, str_contains($attributes, 'hidden'));
                    $this->assertSame(!$enabled, str_contains($attributes, 'disabled'));
                }
            }
        }
    }

    public function test_name_edit_preserves_all_media_and_variant_identity(): void
    {
        Storage::fake('public');
        $product = $this->product(['main_image'=>'storage/products/images/old.jpg', 'gallery_images'=>['storage/products/images/gallery.jpg'], 'video_path'=>'storage/products/videos/old.mp4']);
        $variant = $product->variants()->create(['name'=>'Large', 'image'=>'storage/products/variants/old.jpg', 'stock'=>7]);
        $this->put(route('admin.products.update', $product), $this->payload(['name_bn'=>'New name', 'main_image'=>'', 'variants'=>[$variant->id=>['name'=>'Larger']]]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('storage/products/images/old.jpg', $product->fresh()->main_image);
        $this->assertSame(['storage/products/images/gallery.jpg'], $product->fresh()->gallery_images);
        $this->assertSame('storage/products/videos/old.mp4', $product->fresh()->video_path);
        $this->assertSame('storage/products/variants/old.jpg', $variant->fresh()->image);
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_replacement_commits_before_removing_old_file_and_preserves_shared_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/images/old.jpg', 'old');
        $product = $this->product(['main_image'=>'storage/products/images/old.jpg']);
        $this->put(route('admin.products.update', $product), $this->payload(['main_image_file'=>UploadedFile::fake()->image('new.jpg')]))->assertSessionHasNoErrors();
        $new = $product->fresh()->main_image;
        $this->assertNotSame('storage/products/images/old.jpg', $new);
        Storage::disk('public')->assertExists(substr($new, 8));
        Storage::disk('public')->assertMissing('products/images/old.jpg');
        $this->product(['slug'=>'shared', 'main_image'=>$new]);
        $this->put(route('admin.products.update', $product), $this->payload(['main_image_file'=>UploadedFile::fake()->image('next.jpg')]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists(substr($new, 8));
    }

    public function test_invalid_upload_and_foreign_gallery_removal_leave_media_unchanged(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/images/old.jpg', 'old');
        $product = $this->product(['main_image'=>'storage/products/images/old.jpg']);
        $this->put(route('admin.products.update', $product), $this->payload(['main_image_file'=>UploadedFile::fake()->create('bad.txt')]))->assertSessionHasErrors('main_image_file');
        $this->put(route('admin.products.update', $product), $this->payload(['main_image_file'=>UploadedFile::fake()->image('new.jpg'), 'remove_gallery'=>[hash('sha256', 'storage/products/images/foreign.jpg')]]))->assertSessionHasErrors('remove_gallery');
        $this->assertSame('storage/products/images/old.jpg', $product->fresh()->main_image);
        $this->assertSame(['products/images/old.jpg'], Storage::disk('public')->allFiles());
    }

    public function test_database_failure_rolls_back_upload_and_product_change(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/images/old.jpg', 'old');
        $product = $this->product(['main_image'=>'storage/products/images/old.jpg']);
        DB::unprepared("CREATE TRIGGER fail_product_update BEFORE UPDATE ON products BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->withoutExceptionHandling();
        try {
            $this->put(route('admin.products.update', $product), $this->payload(['main_image_file'=>UploadedFile::fake()->image('new.jpg')]));
            $this->fail('Expected database failure');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('test failure', $exception->getMessage());
        }
        $this->assertSame('storage/products/images/old.jpg', $product->fresh()->main_image);
        $this->assertSame(['products/images/old.jpg'], Storage::disk('public')->allFiles());
    }

    public function test_storage_failure_preserves_existing_image(): void
    {
        $product = $this->product(['main_image'=>'storage/products/images/old.jpg']);
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->put(route('admin.products.update', $product), $this->payload(['main_image_file'=>UploadedFile::fake()->image('new.jpg')]))->assertSessionHasErrors('main_image_file');
        $this->assertSame('storage/products/images/old.jpg', $product->fresh()->main_image);
    }

    public function test_gallery_video_and_variant_replacements_retire_only_owned_files(): void
    {
        Storage::fake('public');
        foreach (['images/gallery.jpg', 'images/keep.jpg', 'videos/old.mp4', 'variants/old.jpg'] as $path) {
            Storage::disk('public')->put('products/'.$path, 'old');
        }
        $product = $this->product(['gallery_images'=>['storage/products/images/gallery.jpg', 'storage/products/images/keep.jpg'], 'video_path'=>'storage/products/videos/old.mp4']);
        $variant = $product->variants()->create(['name'=>'Large', 'image'=>'storage/products/variants/old.jpg']);
        $this->put(route('admin.products.update', $product), $this->payload([
            'remove_gallery'=>[hash('sha256', 'storage/products/images/gallery.jpg')],
            'gallery_images'=>[UploadedFile::fake()->image('new.jpg')],
            'video_file'=>UploadedFile::fake()->create('new.mp4', 2, 'video/mp4'),
            'variants'=>[$variant->id=>['name'=>'Large', 'image_file'=>UploadedFile::fake()->image('variant.jpg')]],
        ]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing(['products/images/gallery.jpg', 'products/videos/old.mp4', 'products/variants/old.jpg']);
        Storage::disk('public')->assertExists('products/images/keep.jpg');
        $this->assertCount(2, $product->fresh()->gallery_images);
        Storage::disk('public')->assertExists(substr($product->fresh()->video_path, 8));
        Storage::disk('public')->assertExists(substr($variant->fresh()->image, 8));
    }

    public function test_external_image_explicit_removal_and_variant_sale_validation(): void
    {
        $product = $this->product(['main_image'=>'https://example.com/image.jpg']);
        $this->put(route('admin.products.update', $product), $this->payload(['main_image'=>$product->main_image, 'remove_main_image'=>1]))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->main_image);
        $variant = $product->variants()->create(['name'=>'Large', 'retail_price'=>120, 'sale_price'=>100]);
        $this->put(route('admin.products.update', $product), $this->payload(['variants'=>[$variant->id=>['name'=>'Large','retail_price'=>90]]]))->assertSessionHasErrors('variants.'.$variant->id.'.sale_price');
        $this->assertSame('120.00', $variant->fresh()->retail_price);
    }

    public function test_channels_validate_conditionally_and_retail_packs_survive_toggle(): void
    {
        $product = $this->product();
        $product->syncPrices();
        $pack = $product->prices()->first();
        $pack->update(['is_manual_override'=>true, 'manual_price'=>99, 'final_price'=>99, 'is_active'=>false]);
        $before = $product->prices()->get()->toArray();
        $this->put(route('admin.products.update', $product), $this->payload(['show_in_retail'=>0, 'show_in_wholesale'=>1, 'retail_price_1kg'=>'bad', 'prices'=>[$pack->id=>['manual_price'=>'bad']]]))->assertSessionHasNoErrors();
        $this->assertSame($before, $product->prices()->get()->toArray());
        $this->assertSame('500.00', $product->fresh()->retail_price_1kg);
        $this->put(route('admin.products.update', $product), $this->payload(['show_in_retail'=>1, 'show_in_wholesale'=>1]))->assertSessionHasNoErrors();
        $this->assertSame('99.00', $pack->fresh()->final_price);
        $this->assertFalse($pack->fresh()->is_active);
        $this->put(route('admin.products.update', $product), $this->payload(['retail_price_1kg'=>null]))->assertSessionHasErrors('retail_price_1kg');
        $this->put(route('admin.products.update', $product), $this->payload(['show_in_retail'=>0, 'show_in_wholesale'=>0]))->assertSessionHasErrors('show_in_retail');
        $this->post(route('admin.products.store'), $this->payload(['slug'=>'wholesale', 'show_in_retail'=>0, 'show_in_wholesale'=>1, 'retail_price_1kg'=>null]))->assertSessionHasNoErrors();
        $this->assertSame(0, Product::where('slug', 'wholesale')->firstOrFail()->prices()->count());
    }

    public function test_tags_normalize_reload_render_and_clear(): void
    {
        $product = $this->product();
        $this->put(route('admin.products.update', $product), $this->payload(['tags'=>'Cumin, cumin, Whole Spice, জিরা']))->assertSessionHasNoErrors();
        $this->assertSame(3, $product->tags()->count());
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Whole Spice');
        $this->get(route('products.show', $product->slug))->assertOk()->assertSee('name="keywords"', false)->assertSee('Whole Spice');
        $this->put(route('admin.products.update', $product), $this->payload(['tags'=>'<script>bad</script>']))->assertSessionHasErrors('tags.0');
        $this->put(route('admin.products.update', $product), $this->payload(['tags'=>'']))->assertSessionHasNoErrors();
        $this->assertSame(0, $product->tags()->count());
    }

    public function test_saving_legacy_retail_product_recovers_missing_packs(): void
    {
        $product = $this->product();
        $this->assertSame(0, $product->prices()->count());
        $this->put(route('admin.products.update', $product), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(6, $product->prices()->whereNull('product_variant_id')->count());
    }

    public function test_variant_attributes_images_deactivation_and_ownership(): void
    {
        Storage::fake('public');
        $product = $this->product();
        $this->put(route('admin.products.update', $product), $this->payload(['new_variants'=>[['name'=>'Large', 'attributes'=>[['name'=>'Weight','value'=>'1 kg']], 'stock'=>8, 'retail_price'=>120, 'sale_price'=>100, 'image_file'=>UploadedFile::fake()->image('variant.jpg')]], 'default_variant'=>'new:0']))->assertSessionHasNoErrors();
        $variant = $product->variants()->firstOrFail();
        $image = $variant->image;
        $this->assertSame([['name'=>'Weight','value'=>'1 kg']], $variant->attributes);
        $this->put(route('admin.products.update', $product), $this->payload(['variants'=>[$variant->id=>['name'=>'Large updated', 'is_active'=>0]]]))->assertSessionHasNoErrors();
        $this->assertFalse($variant->fresh()->is_active);
        $this->assertSame($image, $variant->fresh()->image);
        $this->assertSame(8, $variant->fresh()->stock);
        $this->put(route('admin.products.update', $product), $this->payload(['variants'=>[$variant->id=>['_delete'=>1]]]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_variants', ['id'=>$variant->id, 'stock'=>8]);
        Storage::disk('public')->assertExists(substr($image, 8));
        $other = $this->product(['slug'=>'other'])->variants()->create(['name'=>'Other']);
        $this->put(route('admin.products.update', $product), $this->payload(['variants'=>[$other->id=>['name'=>'Stolen']]]))->assertSessionHasErrors('variants');
        $this->put(route('admin.products.update', $product), $this->payload(['variants'=>[$variant->id=>['name'=>'Large','is_active'=>1]], 'default_variant'=>'existing:'.$other->id]))->assertSessionHasErrors('default_variant');
    }

    public function test_vendor_forms_approval_visibility_and_ownership(): void
    {
        $admin = auth()->user();
        $user = User::factory()->create(['role'=>'vendor']);
        $vendor = Vendor::create(['user_id'=>$user->id, 'shop_name'=>'Test shop', 'slug'=>'test-shop', 'owner_name'=>'Owner', 'phone'=>'01700000000', 'email'=>'vendor@example.com', 'status'=>'approved', 'is_active'=>true]);
        $this->actingAs($user)->get(route('vendor.products.create'))->assertOk();
        $this->post(route('vendor.products.store'), $this->payload(['approval_status'=>'approved', 'low_stock_threshold'=>null]))->assertSessionHasNoErrors();
        $product = $vendor->products()->firstOrFail();
        $this->assertSame('pending', $product->approval_status);
        $this->get(route('vendor.products.edit', $product))->assertOk();
        $this->get(route('products.show', $product->slug))->assertNotFound();
        $this->assertFalse(Product::active()->whereKey($product->id)->exists());
        $this->get('/')->assertOk()->assertViewHas('products', fn ($products) => !$products->contains('id', $product->id));
        $this->put(route('vendor.products.update', $product), $this->payload(['approval_status'=>'approved']))->assertSessionHasNoErrors();
        $this->assertSame('pending', $product->fresh()->approval_status);
        $other = $this->product(['slug'=>'admin-owned']);
        $this->put(route('vendor.products.update', $other), $this->payload(['slug'=>'admin-owned']))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['approval_status'=>'approved']))->assertSessionHasNoErrors();
        $this->assertTrue(Product::active()->whereKey($product->id)->exists());
        $this->get('/')->assertOk()->assertViewHas('products', fn ($products) => $products->contains('id', $product->id));
        $this->get(route('products.show', $product->slug))->assertOk();
        $this->get(route('products.show', ['product'=>$product->slug, 'mode'=>'wholesale']))->assertNotFound();
        $product->update(['show_in_retail'=>false, 'show_in_wholesale'=>true]);
        $this->get(route('products.show', $product->slug))->assertOk();
        $product->update(['is_active'=>false]);
        $this->get(route('products.show', $product->slug))->assertNotFound();
        $product->update(['is_active'=>true, 'show_in_wholesale'=>false]);
        $this->get(route('products.show', $product->slug))->assertNotFound();
    }
}
