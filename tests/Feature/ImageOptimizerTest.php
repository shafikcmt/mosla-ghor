<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
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
        ImageOptimizer::$forceWebp = true;
    }

    protected function tearDown(): void
    {
        ImageOptimizer::$forceWebp = null;
        ImageOptimizer::$forceLibrary = null;
        foreach ($this->tmp as $path) {
            is_dir($path) ? $this->rmTree($path) : @unlink($path);
        }
        parent::tearDown();
    }

    // ── image fixtures (generated with GD) ──────────────────────────────────

    private static array $jpegCache = [];

    /** Noisy photo-like JPEG (noise defeats compression, like a real photo). Cached per size. */
    private function jpegBytes(int $w, int $h, int $quality = 95): string
    {
        return self::$jpegCache["$w-$h-$quality"] ??= (function () use ($w, $h, $quality) {
            $im = imagecreatetruecolor($w, $h);
            mt_srand(42);
            $blocks = (int) ($w * $h / 900); // small blocks cover the image cheaply
            for ($i = 0; $i < $blocks; $i++) {
                $x = mt_rand(0, $w - 1);
                $y = mt_rand(0, $h - 1);
                $c = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                imagefilledrectangle($im, $x, $y, $x + mt_rand(4, 40), $y + mt_rand(4, 40), $c);
            }
            ob_start();
            imagejpeg($im, null, $quality);
            imagedestroy($im);
            return (string) ob_get_clean();
        })();
    }

    /** PNG with a fully transparent background and an opaque square. */
    private function transparentPngBytes(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagefilledrectangle($im, (int) ($w / 4), (int) ($h / 4), (int) ($w * 3 / 4), (int) ($h * 3 / 4), imagecolorallocatealpha($im, 200, 30, 30, 0));
        ob_start();
        imagepng($im);
        imagedestroy($im);
        return (string) ob_get_clean();
    }

    private function webpBytes(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 120, 60));
        ob_start();
        imagewebp($im, null, 80);
        imagedestroy($im);
        return (string) ob_get_clean();
    }

    /** Insert a real EXIF APP1 block with a GPS latitude tag right after the JPEG SOI marker. */
    private function withGpsExif(string $jpeg): string
    {
        $tiff = "MM\x00\x2A".pack('N', 8)
            .pack('n', 1).pack('nnNN', 0x8825, 4, 1, 26).pack('N', 0)          // IFD0: GPSInfo → offset 26
            .pack('n', 1).pack('nnN', 0x0001, 2, 2)."N\0\0\0".pack('N', 0);     // GPS IFD: GPSLatitudeRef = "N"
        $exif = "Exif\0\0".$tiff;
        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
    }

    private function upload(string $bytes, string $name, string $mime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $bytes);
        $this->tmp[] = $path;
        return new UploadedFile($path, $name, $mime, null, true);
    }

    private function stored(string $relative): string
    {
        return Storage::disk('public')->get($relative);
    }

    private function dims(string $bytes): array
    {
        $info = getimagesizefromstring($bytes);
        return [$info[0], $info[1], $info['mime']];
    }

    // ── tests ───────────────────────────────────────────────────────────────

    public function test_large_jpeg_is_resized_to_webp_and_much_smaller(): void
    {
        $original = $this->jpegBytes(3000, 2000);
        $path = ImageOptimizer::store($this->upload($original, 'photo.jpg', 'image/jpeg'), 'products/images', 'product');

        $this->assertStringEndsWith('.webp', $path);
        $bytes = $this->stored($path);
        [$w, $h, $mime] = $this->dims($bytes);
        $this->assertSame('image/webp', $mime);
        $this->assertSame([1600, 1067], [$w, $h], 'Long side scaled to the product max, ratio kept.');
        $this->assertLessThan(strlen($original) * 0.4, strlen($bytes), 'Much smaller than the original.');
    }

    public function test_png_transparency_is_kept(): void
    {
        $path = ImageOptimizer::store($this->upload($this->transparentPngBytes(2400, 1800), 'logo.png', 'image/png'), 'vendors/logos', 'logo');
        $this->assertStringEndsWith('.webp', $path);
        $im = imagecreatefromstring($this->stored($path));
        $this->assertSame(512, imagesx($im));
        $this->assertSame(127, (imagecolorat($im, 2, 2) >> 24) & 0x7F, 'Corner stays fully transparent.');
        $this->assertSame(0, (imagecolorat($im, 256, 192) >> 24) & 0x7F, 'Centre stays opaque.');

        // No WebP on the server → optimised PNG (not JPEG), alpha kept.
        ImageOptimizer::$forceWebp = false;
        $path = ImageOptimizer::store($this->upload($this->transparentPngBytes(2400, 1800), 'logo.png', 'image/png'), 'vendors/logos', 'logo');
        $this->assertStringEndsWith('.png', $path);
        $im = imagecreatefromstring($this->stored($path));
        $this->assertSame(127, (imagecolorat($im, 2, 2) >> 24) & 0x7F);
    }

    public function test_exif_and_gps_are_removed(): void
    {
        $withGps = $this->withGpsExif($this->jpegBytes(800, 600, 90));
        $this->assertTrue(ImageOptimizer::hasMetadata($this->upload($withGps, 'gps.jpg', 'image/jpeg')->getRealPath()), 'Fixture really carries EXIF.');

        // Fits the profile and may not get smaller, but carries GPS → must still be re-encoded.
        $path = ImageOptimizer::store($this->upload($withGps, 'gps.jpg', 'image/jpeg'), 'reviews', 'review');
        $bytes = $this->stored($path);
        $this->assertStringNotContainsString("Exif\0\0", $bytes);
        $this->assertStringNotContainsString('GPS', $bytes);
        $this->assertNotSame($withGps, $bytes);
    }

    public function test_small_image_is_not_upscaled(): void
    {
        $path = ImageOptimizer::store($this->upload($this->jpegBytes(400, 300), 'small.jpg', 'image/jpeg'), 'products/images', 'product');
        [$w, $h] = $this->dims($this->stored($path));
        $this->assertSame([400, 300], [$w, $h]);
    }

    public function test_kyc_is_stored_byte_for_byte(): void
    {
        $original = $this->withGpsExif($this->jpegBytes(3000, 2000));
        $this->assertNull(ImageOptimizer::profile('kyc'));
        $path = ImageOptimizer::store($this->upload($original, 'nid.jpg', 'image/jpeg'), 'vendors/kyc', 'kyc');
        $this->assertSame($original, $this->stored($path));

        // And the registration controller never routes KYC through the optimizer.
        $src = file_get_contents(app_path('Http/Controllers/Vendor/AuthController.php'));
        $this->assertStringContainsString("\$request->file('kyc_document')->store('vendors/kyc', 'public')", $src);
    }

    public function test_corrupt_image_falls_back_to_original_without_error(): void
    {
        Log::spy();
        // Valid PNG signature + header (so it looks like a 2000×2000 PNG) followed by garbage pixel data.
        $corrupt = substr($this->transparentPngBytes(2000, 2000), 0, 33).random_bytes(4000);
        $path = ImageOptimizer::store($this->upload($corrupt, 'broken.png', 'image/png'), 'products/images', 'product');
        $this->assertSame($corrupt, $this->stored($path));
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx) => $msg === 'Image optimization skipped; original stored'
            && ! str_contains(json_encode($ctx), 'broken.png'));

        // Not an image at all.
        $junk = 'definitely not an image';
        $path = ImageOptimizer::store($this->upload($junk, 'junk.jpg', 'image/jpeg'), 'reviews', 'review');
        $this->assertSame($junk, $this->stored($path));
    }

    public function test_og_image_is_1200x630_jpeg(): void
    {
        $path = ImageOptimizer::store($this->upload($this->jpegBytes(2000, 1500), 'share.jpg', 'image/jpeg'), 'products/images', 'og');
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame([1200, 630, 'image/jpeg'], $this->dims($this->stored($path)));
    }

    public function test_without_webp_support_large_jpeg_becomes_smaller_jpeg(): void
    {
        ImageOptimizer::$forceWebp = false;
        $original = $this->jpegBytes(3000, 2000);
        $path = ImageOptimizer::store($this->upload($original, 'photo.jpg', 'image/jpeg'), 'hero-slides', 'hero');
        $this->assertStringEndsWith('.jpg', $path);
        [$w, , $mime] = $this->dims($this->stored($path));
        $this->assertSame(['image/jpeg', 1920], [$mime, $w]);
        $this->assertLessThan(strlen($original), strlen($this->stored($path)));
    }

    public function test_webp_that_gd_cannot_decode_is_stored_unchanged_and_logs_nothing(): void
    {
        // What the browser sends on live: a resized WebP, within the profile, no EXIF.
        $webp = $this->webpBytes(1200, 900);
        ImageOptimizer::$forceWebp = false; // live GD build without WebP
        Log::spy();

        $path = ImageOptimizer::store($this->upload($webp, 'photo.webp', 'image/webp'), 'products/images', 'product');

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame($webp, $this->stored($path));
        Log::shouldNotHaveReceived('warning');
    }

    public function test_setting_off_stores_exactly_as_before(): void
    {
        WebsiteSetting::updateOrCreate(['key' => 'image_optimize_enabled'], ['value' => '0']);
        $original = $this->jpegBytes(3000, 2000);
        $path = ImageOptimizer::store($this->upload($original, 'photo.jpg', 'image/jpeg'), 'products/images', 'product');
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame($original, $this->stored($path));
        $this->assertSame('', (string) ImageOptimizer::inputAttributes('product'), 'Browser resize is off too.');
    }

    public function test_memory_guard_stores_original(): void
    {
        Log::spy();
        // Build the fixture first; only the optimizer runs under the tight limit.
        $original = $this->jpegBytes(3000, 2000);
        $file = $this->upload($original, 'photo.jpg', 'image/jpeg');
        $limit = ini_get('memory_limit');
        ini_set('memory_limit', (string) (memory_get_usage(true) + 16 * 1024 * 1024)); // far too little to decode 3000×2000
        try {
            $path = ImageOptimizer::store($file, 'products/images', 'product');
        } finally {
            ini_set('memory_limit', $limit);
        }
        $this->assertSame($original, $this->stored($path));
        Log::shouldHaveReceived('warning')->withArgs(fn ($m, $c) => $c['reason'] === 'image too large for available memory');
    }

    public function test_product_editor_upload_is_optimized_end_to_end(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_admin' => true]));
        $product = Product::create(['name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => false]);

        $this->put(route('admin.products.update', $product), [
            'name_bn' => 'Cumin', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500, 'is_active' => 1,
            'show_in_retail' => 1, 'show_in_wholesale' => 0,
            'main_image_file' => $this->upload($this->jpegBytes(3000, 2000), 'photo.jpg', 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $main = $product->fresh()->main_image;
        $this->assertStringStartsWith('storage/products/images/', $main);
        $this->assertStringEndsWith('.webp', $main);
        [$w] = $this->dims($this->stored(substr($main, strlen('storage/'))));
        $this->assertSame(1600, $w);
    }

    public function test_settings_card_shows_whether_image_library_is_installed(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.general-settings.index'))->assertOk()
            ->assertSee('data-image-library="yes"', false)->assertSee('Image library: ✅ ইনস্টল আছে');

        // Deploy without composer install: card says so, and uploads still store the original.
        ImageOptimizer::$forceLibrary = false;
        $this->actingAs($admin)->get(route('admin.general-settings.index'))->assertOk()
            ->assertSee('data-image-library="no"', false)->assertSee('Image library: ❌ নেই — ব্রাউজারে ছোট করা চালু আছে');

        Log::spy();
        $original = $this->jpegBytes(3000, 2000);
        $path = ImageOptimizer::store($this->upload($original, 'photo.jpg', 'image/jpeg'), 'products/images', 'product');
        $this->assertSame($original, $this->stored($path));
        Log::shouldHaveReceived('warning')->withArgs(fn ($m, $c) => $c['reason'] === 'image library not installed');
    }

    public function test_input_attributes_share_the_server_profile(): void
    {
        $attrs = (string) ImageOptimizer::inputAttributes('og');
        $this->assertStringContainsString('data-resize-format="jpeg"', $attrs);
        $this->assertStringContainsString('data-resize-crop="1200x630"', $attrs);
        $this->assertStringContainsString('data-resize-max="1600"', (string) ImageOptimizer::inputAttributes('product'));
        $this->assertSame('', (string) ImageOptimizer::inputAttributes('kyc'));
    }

    public function test_command_dry_run_changes_nothing_and_apply_backs_up_in_place(): void
    {
        $original = $this->jpegBytes(3000, 2000);
        Storage::disk('public')->put('products/images/old.jpg', $original);
        Storage::disk('public')->put('vendors/kyc/nid.jpg', $original);

        $this->artisan('images:optimize-existing')->expectsOutputToContain('Dry run')->assertExitCode(0);
        $this->assertSame($original, Storage::disk('public')->get('products/images/old.jpg'));

        $backupRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'img-backup-test-'.uniqid();
        $this->tmp[] = $backupRoot;
        $this->artisan('images:optimize-existing', ['--apply' => true, '--backup-root' => $backupRoot])->assertExitCode(0);

        $new = Storage::disk('public')->get('products/images/old.jpg');
        $this->assertLessThan(strlen($original), strlen($new));
        $this->assertSame('image/jpeg', $this->dims($new)[2], 'Same format, same path.');
        $this->assertSame(1600, $this->dims($new)[0]);
        $this->assertSame($original, Storage::disk('public')->get('vendors/kyc/nid.jpg'), 'KYC never touched.');

        $backups = glob($backupRoot.'/*/products/images/old.jpg');
        $this->assertCount(1, $backups);
        $this->assertSame($original, file_get_contents($backups[0]));
    }

    private function rmTree(string $dir): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}
