<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BuildInfo;
use App\Support\StoragePersistence;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoragePersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_first_visit_creates_marker_and_waits_for_a_deploy(): void
    {
        $this->actingAs($this->admin())->get(route('admin.general-settings.index'))->assertOk()
            ->assertSee('data-persistence="waiting"', false)->assertSee('পরের deploy-এর পর দেখুন');

        Storage::disk('local')->assertExists(StoragePersistence::MARKER);
        $this->assertSame(BuildInfo::commit(), json_decode(Storage::disk('local')->get(StoragePersistence::MARKER), true)['commit']);
    }

    public function test_marker_from_an_older_commit_means_private_storage_survived(): void
    {
        if (BuildInfo::commit() === 'unknown') {
            $this->markTestSkipped('No readable .git in this environment.');
        }
        Storage::disk('local')->put(StoragePersistence::MARKER, json_encode(['created_at' => now()->subDay()->toIso8601String(), 'commit' => 'abc1234']));

        $this->assertTrue(StoragePersistence::status()['survived']);
        $this->actingAs($this->admin())->get(route('admin.general-settings.index'))->assertOk()
            ->assertSee('data-persistence="survived"', false)->assertSee('✅ টিকে আছে — abc1234 থেকে');
    }

    public function test_marker_is_never_overwritten(): void
    {
        Storage::disk('local')->put(StoragePersistence::MARKER, json_encode(['created_at' => '2026-01-01T00:00:00+06:00', 'commit' => 'old1234']));
        StoragePersistence::status();
        $this->assertSame('old1234', json_decode(Storage::disk('local')->get(StoragePersistence::MARKER), true)['commit']);
    }
}
