<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Support\BuildInfo;
use App\Support\ServerLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadErrorsTest extends TestCase
{
    private array $tmp = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $p) { @unlink($p); }
        parent::tearDown();
    }

    /** An upload PHP rejected (e.g. over upload_max_filesize) — exactly what happens on live. */
    private function failedUpload(int $error = UPLOAD_ERR_INI_SIZE, string $name = 'IMG_2024.jpg'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, '');
        $this->tmp[] = $path;
        return new UploadedFile($path, $name, 'image/jpeg', $error, true);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function product(): Product
    {
        return Product::create(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false]);
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    public function test_too_big_upload_gets_one_clear_bangla_message_not_must_be_an_image(): void
    {
        Log::spy();
        $product = $this->product();
        $response = $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            'main_image_file' => $this->failedUpload(),
            'og_image_file' => $this->failedUpload(),
            'gallery_images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), $this->failedUpload()],
        ]));

        $errors = session('errors')->getBag('default');
        $limit = ServerLimits::human(ServerLimits::uploadMax());
        $this->assertSame(["মূল ছবি অনেক বড় (সার্ভারের সীমা {$limit})। ছোট ছবি দিন অথবা আবার বেছে নিন — আমরা নিজে থেকে ছোট করে দিই।"], $errors->get('main_image_file'));
        $this->assertCount(1, $errors->get('og_image_file'));
        $this->assertStringStartsWith('শেয়ার ছবি অনেক বড়', $errors->first('og_image_file'));
        $this->assertStringStartsWith('গ্যালারির ৩ নম্বর ছবি অনেক বড়', $errors->first('gallery_images.2'));
        $this->assertStringNotContainsString('must be', implode(' ', $errors->all()));
        $this->assertNull($product->fresh()->main_image, 'Nothing saved.');

        Log::shouldHaveReceived('warning')->withArgs(fn ($m, $c) => $m === 'Upload failed before validation'
            && $c['field'] === 'main_image_file' && $c['error'] === 'UPLOAD_ERR_INI_SIZE'
            && isset($c['upload_max_filesize'], $c['post_max_size'])
            && ! str_contains(json_encode($c), 'IMG_2024'));
    }

    public function test_partial_and_server_side_failures_have_their_own_messages(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            'main_image_file' => $this->failedUpload(UPLOAD_ERR_PARTIAL),
            'og_image_file' => $this->failedUpload(UPLOAD_ERR_CANT_WRITE),
        ]))->assertSessionHasErrors([
            'main_image_file' => 'মূল ছবি পুরোপুরি আপলোড হয়নি (সংযোগ বিচ্ছিন্ন হয়েছিল)। আবার বেছে নিয়ে সংরক্ষণ করুন।',
            'og_image_file' => 'শেয়ার ছবি সার্ভারে সংরক্ষণ করা যায়নি (সার্ভারের সাময়িক সমস্যা)। কিছুক্ষণ পর আবার চেষ্টা করুন।',
        ]);
    }

    public function test_form_from_an_older_deploy_is_told_to_refresh(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            '_build' => 'deadbee', 'main_image_file' => $this->failedUpload(),
        ]));
        $message = session('errors')->first('main_image_file');
        if (BuildInfo::commit() !== 'unknown') {
            $this->assertStringContainsString('পেজটি পুরোনো — রিফ্রেশ করে আবার চেষ্টা করুন', $message);
        }

        // Same build → no refresh hint.
        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            '_build' => BuildInfo::commit(), 'main_image_file' => $this->failedUpload(),
        ]));
        $this->assertStringNotContainsString('পেজটি পুরোনো', session('errors')->first('main_image_file'));
    }

    public function test_wrong_file_type_gives_one_bangla_message(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            'main_image_file' => UploadedFile::fake()->create('notes.txt', 3, 'text/plain'),
        ]));
        $this->assertSame(['মূল ছবি একটি ছবি হতে হবে (JPG, PNG বা WebP)।'], session('errors')->get('main_image_file'));
    }

    public function test_editor_shows_errors_in_one_place_only(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $errors = (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['name_bn' => ['নাম দিন।']]));

        $html = $this->actingAs($admin)->withSession(['errors' => $errors])->get(route('admin.products.edit', $product))->assertOk()->getContent();
        $this->assertStringContainsString('সংরক্ষণ হয়নি। নিচের তথ্যগুলো ঠিক করুন।', $html);
        $this->assertStringNotContainsString('অনুগ্রহ করে নিচের ত্রুটিগুলো ঠিক করুন', $html, 'Layout box hidden on the editor.');
        $this->assertSame(1, substr_count($html, 'নাম দিন।'));

        // Other admin pages keep the layout's error box.
        $this->actingAs($admin)->withSession(['errors' => $errors])->get(route('admin.general-settings.index'))
            ->assertSee('অনুগ্রহ করে নিচের ত্রুটিগুলো ঠিক করুন');
    }

    public function test_other_image_forms_use_the_same_bangla_upload_errors(): void
    {
        $this->actingAs($this->admin())->post(route('admin.hero-slides.store'), ['title' => 'Slide', 'sort_order' => 0, 'image' => $this->failedUpload()])
            ->assertSessionHasErrors('image');
        $this->assertStringStartsWith('হিরো ছবি অনেক বড়', session('errors')->first('image'));

        // JSON order form (payment screenshot) → 422 with the Bangla message.
        auth()->logout();
        $this->postJson(route('order.store'), ['payment_screenshot' => $this->failedUpload()])
            ->assertStatus(422)->assertJsonPath('errors.payment_screenshot.0', fn ($m) => str_starts_with($m, 'পেমেন্ট স্ক্রিনশট অনেক বড়'));
    }

    public function test_request_over_post_max_size_gets_friendly_413(): void
    {
        $tooBig = (string) (ServerLimits::postMax() + 1024);
        $this->call('POST', route('admin.diagnostics.upload-probe'), [], [], [], ['CONTENT_LENGTH' => $tooBig, 'HTTP_ACCEPT' => 'application/json'])
            ->assertStatus(413)->assertJson(['layer' => 'php_post_max_size'])
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'মোট অনেক বড়'));

        $this->call('POST', route('admin.diagnostics.upload-probe'), [], [], [], ['CONTENT_LENGTH' => $tooBig])
            ->assertStatus(413)->assertSee('ফাইল অনেক বড়');
    }

    public function test_image_resize_script_carries_server_limits(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('data-max-request="'.ServerLimits::safeRequestBytes().'"', false)
            ->assertSee('data-max-file="'.ServerLimits::safeFileBytes().'"', false)
            ->assertSee('name="_build" value="'.BuildInfo::commit().'"', false);
    }
}
