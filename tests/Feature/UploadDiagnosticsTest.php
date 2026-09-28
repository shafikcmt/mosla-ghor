<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use App\Support\BuildInfo;
use App\Support\ServerLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDiagnosticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_settings_card_shows_live_limits_and_running_commit(): void
    {
        $this->actingAs($this->admin())->get(route('admin.general-settings.index'))->assertOk()
            ->assertSee('upload_max_filesize')->assertSee(ini_get('upload_max_filesize'))
            ->assertSee('post_max_size')->assertSee('max_file_uploads')->assertSee('memory_limit')
            ->assertSee('আপলোড সীমা পরীক্ষা')->assertSee('এখনও পরীক্ষা হয়নি')
            ->assertSee(BuildInfo::commit());
    }

    public function test_probe_reports_received_size_and_stores_nothing(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->admin())->postJson(route('admin.diagnostics.upload-probe'), [
            'probe' => UploadedFile::fake()->create('probe.bin', 512),
        ])->assertOk()->assertJson(['ok' => true, 'bytes' => 512 * 1024]);

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_probe_reports_php_upload_limit_error(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'probe');
        file_put_contents($path, 'x');
        $tooBig = new UploadedFile($path, 'probe.bin', 'application/octet-stream', UPLOAD_ERR_INI_SIZE, true);

        $this->actingAs($this->admin())->postJson(route('admin.diagnostics.upload-probe'), ['probe' => $tooBig])
            ->assertOk()->assertJson(['ok' => false, 'layer' => 'php_upload_max_filesize', 'error' => 'UPLOAD_ERR_INI_SIZE']);
        @unlink($path);
    }

    public function test_result_is_saved_and_used_as_safe_limit(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.diagnostics.upload-result'), ['largest_ok_bytes' => 1048576])
            ->assertOk()->assertJson(['ok' => true, 'saved' => '1 MB']);

        $this->assertSame(1048576, ServerLimits::measured());
        $this->assertLessThanOrEqual(1048576, ServerLimits::safeRequestBytes());
        $this->assertNotSame('', WebsiteSetting::get(ServerLimits::MEASURED_AT_KEY));
    }

    public function test_non_admin_cannot_use_diagnostics(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer', 'is_admin' => false]))
            ->postJson(route('admin.diagnostics.upload-probe'), ['probe' => UploadedFile::fake()->create('p.bin', 1)])
            ->assertForbidden();
        $this->assertNull(ServerLimits::measured());
    }

    public function test_limit_helpers(): void
    {
        $this->assertSame(2 * 1024 * 1024, ServerLimits::toBytes('2M'));
        $this->assertSame(512 * 1024, ServerLimits::toBytes('512K'));
        $this->assertSame(-1, ServerLimits::toBytes('-1'));
        $this->assertSame('2 MB', ServerLimits::human(2 * 1024 * 1024));
        $this->assertSame('সীমাহীন', ServerLimits::human(-1));
    }
}
