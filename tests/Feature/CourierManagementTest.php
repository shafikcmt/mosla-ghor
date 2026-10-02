<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\CourierService;
use App\Services\SteadfastService;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CourierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => 'admin']));
    }

    protected function beforeRefreshingDatabase(): void
    {
        // These checks must run BEFORE RefreshDatabase can run migrations.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));
    }

    private function courier(array $attributes = []): Courier
    {
        return Courier::create(array_merge([
            'name' => 'Steadfast', 'slug' => 'steadfast', 'status' => 'active',
            'api_enabled' => false, 'vendor_allowed' => true,
        ], $attributes));
    }

    private function saveApi(Courier $courier, array $attributes = [])
    {
        return $this->put(route('admin.courier-api-settings.update', $courier), array_merge([
            'courier_id' => $courier->id, 'base_url_select' => Courier::DEFAULT_STEADFAST_BASE_URL,
        ], $attributes));
    }

    public function test_admin_can_view_manage_sections_without_secrets(): void
    {
        $courier = $this->courier(['api_key' => 'fixture-key-1234', 'api_secret' => 'fixture-secret-9876']);
        $this->get(route('admin.couriers.index'))->assertOk()->assertSee('Manage');
        $this->get(route('admin.courier-api-settings.index', ['courier' => $courier->id]))
            ->assertOk()->assertSee('API Configuration')->assertSee('Test Connection')
            ->assertDontSee('fixture-key-1234')->assertDontSee('fixture-secret-9876');
        $this->assertArrayNotHasKey('api_key', $courier->toArray());
        $this->assertArrayNotHasKey('api_secret', $courier->toArray());
        $this->assertStringNotContainsString('fixture-key-1234', $courier->toJson());
        $this->assertStringNotContainsString('fixture-secret-9876', $courier->toJson());
        $this->assertSame('••••••••', $courier->maskedKey());
    }

    public function test_non_admin_cannot_change_or_test_api_configuration(): void
    {
        $courier = $this->courier();
        foreach (['vendor', 'customer'] as $role) {
            $this->actingAs(User::factory()->create(['is_admin' => false, 'role' => $role]));
            $this->putJson(route('admin.courier-api-settings.update', $courier), ['api_enabled' => true])->assertForbidden();
            $this->postJson(route('admin.courier-api-settings.test', $courier))->assertForbidden();
            $this->getJson(route('admin.courier-api-settings.index'))->assertForbidden();
        }
        $this->assertFalse($courier->fresh()->api_enabled);
        Http::assertNothingSent();
    }

    public function test_basic_edit_keeps_api_credentials_and_books_nothing(): void
    {
        $courier = $this->courier(['api_key' => 'original-key', 'api_secret' => 'original-secret']);
        $this->put(route('admin.couriers.update', $courier), [
            'name' => 'Updated', 'slug' => 'steadfast', 'status' => 'active',
            'vendor_allowed' => '1', 'is_default' => '1', 'notes' => 'Updated notes',
            'api_key' => 'ignored', 'api_secret' => 'ignored',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Updated', $courier->fresh()->name);
        $this->assertSame('original-key', $courier->fresh()->api_key);
        $this->assertSame('original-secret', $courier->fresh()->api_secret);
        $this->assertTrue($courier->fresh()->is_default);
        Http::assertNothingSent();
    }

    public function test_disabled_api_does_not_require_credentials(): void
    {
        $courier = $this->courier();
        $this->saveApi($courier)->assertSessionHasNoErrors();
        $this->assertFalse($courier->fresh()->api_enabled);
        $this->assertNull($courier->fresh()->api_key);
        Http::assertNothingSent();
    }

    public function test_enabled_api_requires_both_credentials_and_does_not_save_invalid_state(): void
    {
        $courier = $this->courier();
        $this->saveApi($courier, ['api_enabled' => '1'])->assertSessionHasErrors(['api_key', 'api_secret']);
        $this->assertFalse($courier->fresh()->api_enabled);
        $this->saveApi($courier, [
            'api_enabled' => '1', 'replace_api_credentials' => '1', 'api_key' => 'only-key',
        ])->assertSessionHasErrors('api_secret');
        $this->assertNull($courier->fresh()->api_key);
    }

    public function test_secrets_are_encrypted_and_replacements_are_explicit(): void
    {
        $courier = $this->courier();
        $this->saveApi($courier, [
            'api_enabled' => '1', 'replace_api_credentials' => '1',
            'api_key' => 'fixture-key', 'api_secret' => 'fixture-secret',
        ])->assertSessionHasNoErrors();
        $row = DB::table('couriers')->find($courier->id);
        $this->assertNotSame('fixture-key', $row->api_key);
        $this->assertNotSame('fixture-secret', $row->api_secret);
        $this->assertSame('fixture-key', Crypt::decryptString($row->api_key));
        $this->assertSame('fixture-secret', $courier->fresh()->api_secret);
        $this->saveApi($courier, ['api_enabled' => '1', 'api_key' => 'autofill-value'])->assertSessionHasNoErrors();
        $this->assertSame('fixture-key', $courier->fresh()->api_key);
        Http::assertNothingSent();
    }

    public function test_blank_secret_inputs_preserve_existing_values(): void
    {
        $courier = $this->courier(['api_key' => 'old-key', 'api_secret' => 'old-secret']);
        $this->saveApi($courier, [
            'api_enabled' => '1', 'replace_api_credentials' => '1', 'api_key' => '', 'api_secret' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame('old-key', $courier->fresh()->api_key);
        $this->assertSame('old-secret', $courier->fresh()->api_secret);
    }

    public function test_new_secret_replaces_old_and_invalidates_previous_test(): void
    {
        $courier = $this->courier([
            'api_key' => 'old-key', 'api_secret' => 'old-secret',
            'courier_api_last_checked_at' => now(), 'courier_api_last_status' => 'success',
        ]);
        $this->saveApi($courier, ['replace_api_credentials' => '1', 'api_secret' => 'new-secret'])->assertSessionHasNoErrors();
        $this->assertSame('new-secret', $courier->fresh()->api_secret);
        $this->assertSame('old-key', $courier->fresh()->api_key);
        $this->assertNull($courier->fresh()->courier_api_last_checked_at);
    }

    public function test_validation_never_flashes_credentials(): void
    {
        $courier = $this->courier();
        $this->saveApi($courier, [
            'api_enabled' => '1', 'replace_api_credentials' => '1', 'api_key' => 'sensitive-fixture',
        ])->assertSessionHasErrors('api_secret');
        $this->assertArrayNotHasKey('api_key', session('_old_input', []));
        $this->assertArrayNotHasKey('api_secret', session('_old_input', []));
    }

    public function test_manual_provider_has_no_credential_fields_or_api_calls(): void
    {
        $courier = $this->courier(['name' => 'Pathao', 'slug' => 'pathao']);
        $this->get(route('admin.courier-api-settings.index', ['courier' => $courier->id]))
            ->assertOk()->assertSee('API integration is not implemented')
            ->assertDontSee('name="api_key"', false)->assertDontSee('>Test Connection</button>', false);
        $this->saveApi($courier, ['api_enabled' => '1'])->assertSessionHasErrors('api_enabled');
        $this->post(route('admin.courier-api-settings.test', $courier))->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_test_connection_only_reads_balance_and_preserves_orders(): void
    {
        $courier = $this->courier(['api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        $order = $this->order($courier);
        $before = $order->fresh()->getAttributes();
        Http::fake(['*/get_balance' => Http::response(['status' => 200, 'current_balance' => 100])]);
        $this->post(route('admin.courier-api-settings.test', $courier))->assertSessionHas('success');
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_ends_with($request->url(), '/get_balance')
            && $request->hasHeader('Api-Key', 'fixture-key')
            && $request->hasHeader('Secret-Key', 'fixture-secret'));
        Http::assertSentCount(1);
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame('success', $courier->fresh()->courier_api_last_status);
        $this->assertNotNull($courier->fresh()->courier_api_last_checked_at);
    }

    public function test_provider_errors_do_not_leak_secrets_to_flash_database_or_logs(): void
    {
        $courier = $this->courier(['api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        Log::spy();
        Http::fake(['*' => Http::response(['message' => 'fixture-key fixture-secret'], 500)]);
        $this->post(route('admin.courier-api-settings.test', $courier))->assertSessionHas('error');
        $this->assertStringNotContainsString('fixture-secret', session('error'));
        $this->assertStringNotContainsString('fixture-key', $courier->fresh()->courier_api_last_error);
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => ! isset($context['body']))->once();
    }

    public function test_authentication_failure_is_friendly(): void
    {
        $courier = $this->courier(['api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        Http::fake(['*' => Http::response(['message' => 'fixture-secret'], 401)]);
        $this->post(route('admin.courier-api-settings.test', $courier))->assertSessionHas('warning');
        $this->assertStringNotContainsString('fixture-secret', session('warning'));
        $this->assertSame('failed', $courier->fresh()->courier_api_last_status);
    }

    public function test_untrusted_endpoint_is_rejected_before_sending_credentials(): void
    {
        $courier = $this->courier(['api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        $this->saveApi($courier, ['base_url_select' => 'https://example.com/api/v1'])->assertSessionHasErrors('base_url_select');
        $courier->update(['base_url' => 'https://example.com/api/v1']);
        $this->post(route('admin.courier-api-settings.test', $courier))->assertSessionHas('warning');
        Http::assertNothingSent();
    }

    public function test_existing_manual_order_booking_remains_manual(): void
    {
        $courier = $this->courier(['name' => 'Pathao', 'slug' => 'pathao']);
        $order = $this->order($courier);
        $result = app(CourierService::class)->send($order, 'manual-tracking');
        $this->assertTrue($result['manual']);
        $this->assertSame('manual-tracking', $order->fresh()->tracking_id);
        $this->assertSame('shipped', $order->fresh()->order_status);
        Http::assertNothingSent();
    }

    public function test_existing_api_booking_still_sends_cod_and_saves_tracking(): void
    {
        $courier = $this->courier(['api_enabled' => true, 'api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        $order = $this->order($courier);
        Http::fake(['*/create_order' => Http::response([
            'status' => 200, 'consignment' => ['tracking_code' => 'tracking-123', 'consignment_id' => 42],
        ])]);
        $result = app(CourierService::class)->send($order);
        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['cod_amount'] === 150.0);
        $this->assertSame('tracking-123', $order->fresh()->tracking_id);
        $this->assertSame('42', $order->fresh()->consignment_id);
        $this->assertSame('shipped', $order->fresh()->order_status);
        $this->assertFalse(app(CourierService::class)->send($order->fresh())['success']);
        Http::assertSentCount(1);
    }

    public function test_migration_preserves_existing_credentials_records_and_nulls(): void
    {
        $id = DB::table('couriers')->insertGetId([
            'name' => 'Legacy', 'slug' => 'legacy', 'api_key' => 'legacy-key', 'api_secret' => null,
        ]);
        $migration = require database_path('migrations/2026_10_02_000001_encrypt_courier_credentials.php');
        $migration->up();
        $this->assertSame('legacy-key', Courier::findOrFail($id)->api_key);
        $this->assertNull(Courier::findOrFail($id)->api_secret);
        $this->assertSame('Legacy', Courier::findOrFail($id)->name);
        $migration->down();
        $this->assertSame('legacy-key', DB::table('couriers')->find($id)->api_key);
    }

    public function test_vendor_parcel_keeps_multi_vendor_cod_policy_and_payouts(): void
    {
        $courier = $this->courier(['api_enabled' => true, 'api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        $order = $this->order($courier);
        $vendorOrders = [];
        foreach ([1, 2] as $number) {
            $user = User::factory()->create(['role' => 'vendor']);
            $vendor = Vendor::create([
                'user_id' => $user->id, 'shop_name' => 'Fixture Shop', 'slug' => 'fixture-shop-'.$number,
                'owner_name' => 'Fixture Vendor', 'phone' => '01700000000', 'email' => $user->email,
            ]);
            $vendorOrders[] = VendorOrder::create(['order_id' => $order->id, 'vendor_id' => $vendor->id]);
        }
        Http::fake(['*/create_order' => Http::response([
            'status' => 200, 'consignment' => ['tracking_code' => 'vendor-track', 'consignment_id' => 51],
        ])]);
        $result = app(CourierService::class)->createVendorParcel($vendorOrders[0], $courier, null, null, 'admin', auth()->id());
        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request['cod_amount'] === 0.0);
        $this->assertSame('vendor-track', $vendorOrders[0]->fresh()->tracking_number);
        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame('0.00', $vendorOrders[0]->fresh()->payable_amount);
        $this->assertFalse(app(CourierService::class)->createVendorParcel($vendorOrders[0]->fresh(), $courier, null, null, 'admin', auth()->id())['success']);
        Http::assertSentCount(1);
    }

    public function test_credentials_with_maximum_length_fit_encrypted_columns(): void
    {
        $courier = $this->courier();
        $value = str_repeat("\u{1F336}", 255);
        $this->saveApi($courier, ['replace_api_credentials' => '1', 'api_key' => $value, 'api_secret' => $value])->assertSessionHasNoErrors();
        $this->assertSame($value, $courier->fresh()->api_secret);
        $this->assertGreaterThan(255, strlen(DB::table('couriers')->find($courier->id)->api_secret));
        $this->assertLessThanOrEqual(65535, strlen(DB::table('couriers')->find($courier->id)->api_secret));
    }

    public function test_migration_retry_preserves_mixed_plaintext_and_ciphertext(): void
    {
        $ciphertext = Crypt::encryptString('already-encrypted-secret');
        $id = DB::table('couriers')->insertGetId([
            'name' => 'Mixed', 'slug' => 'mixed', 'api_key' => 'legacy-key', 'api_secret' => $ciphertext,
            'courier_api_last_message' => 'legacy-key already-encrypted-secret',
        ]);
        $migration = require database_path('migrations/2026_10_02_000001_encrypt_courier_credentials.php');
        $migration->up();
        $after = DB::table('couriers')->find($id);
        $this->assertSame($ciphertext, $after->api_secret);
        $this->assertSame('legacy-key', Courier::findOrFail($id)->api_key);
        $this->assertSame('already-encrypted-secret', Courier::findOrFail($id)->api_secret);
        $this->assertStringNotContainsString('legacy-key', $after->courier_api_last_message);
        $this->assertStringNotContainsString('already-encrypted-secret', $after->courier_api_last_message);
        $migration->up();
        $this->assertSame($after->api_key, DB::table('couriers')->find($id)->api_key);
        $this->assertSame($ciphertext, DB::table('couriers')->find($id)->api_secret);
    }

    public function test_migration_normalizes_raw_empty_credentials_and_model_handles_empty_values(): void
    {
        $id = DB::table('couriers')->insertGetId([
            'name' => 'Empty', 'slug' => 'empty', 'api_key' => '', 'api_secret' => null,
        ]);
        $migration = require database_path('migrations/2026_10_02_000001_encrypt_courier_credentials.php');
        $migration->up();
        $courier = Courier::findOrFail($id);
        $this->assertNull($courier->api_key);
        $this->assertNull($courier->api_secret);
        $this->assertFalse($courier->isConfigured());
        $courier->update(['api_key' => '', 'api_secret' => '']);
        $this->assertSame('', $courier->fresh()->api_key);
        $this->assertSame('', $courier->fresh()->api_secret);
        $this->assertSame('', $courier->fresh()->maskedKey());
        $courier->update(['api_key' => null, 'api_secret' => null]);
        $this->assertNull($courier->fresh()->api_key);
        $this->assertNull($courier->fresh()->api_secret);
    }

    public function test_migration_wrong_key_fails_closed_and_rolls_back_credential_changes(): void
    {
        $id = DB::table('couriers')->insertGetId([
            'name' => 'Plain', 'slug' => 'plain', 'api_key' => 'legacy-key', 'api_secret' => null,
        ]);
        $foreign = (new Encrypter(random_bytes(32), 'AES-256-CBC'))->encryptString('foreign-fixture');
        $foreignId = DB::table('couriers')->insertGetId([
            'name' => 'Foreign', 'slug' => 'foreign', 'api_key' => $foreign, 'api_secret' => null,
        ]);
        $migration = require database_path('migrations/2026_10_02_000001_encrypt_courier_credentials.php');
        try {
            $migration->up();
            $this->fail('Migration accepted ciphertext encrypted with an unavailable key.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('APP_KEY', $error->getMessage());
            $this->assertStringNotContainsString($foreign, $error->getMessage());
        }
        $this->assertSame('legacy-key', DB::table('couriers')->find($id)->api_key);
        $this->assertSame($foreign, DB::table('couriers')->find($foreignId)->api_key);
    }

    public function test_migration_rejects_truncated_ciphertext_without_overwriting_it(): void
    {
        $truncated = substr(Crypt::encryptString('fixture-secret'), 0, 100);
        $id = DB::table('couriers')->insertGetId([
            'name' => 'Truncated', 'slug' => 'truncated', 'api_key' => $truncated,
        ]);
        $migration = require database_path('migrations/2026_10_02_000001_encrypt_courier_credentials.php');
        try {
            $migration->up();
            $this->fail('Migration accepted truncated ciphertext.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('could not be decrypted', $error->getMessage());
        }
        $this->assertSame($truncated, DB::table('couriers')->find($id)->api_key);
    }

    public function test_migration_rollback_retry_preserves_plaintext_and_decrypts_remaining_values(): void
    {
        $id = DB::table('couriers')->insertGetId([
            'name' => 'Rollback', 'slug' => 'rollback', 'api_key' => 'already-plain',
            'api_secret' => Crypt::encryptString('still-encrypted'),
        ]);
        $migration = require database_path('migrations/2026_10_02_000001_encrypt_courier_credentials.php');
        $migration->down();
        $migration->down();
        $this->assertSame('already-plain', DB::table('couriers')->find($id)->api_key);
        $this->assertSame('still-encrypted', DB::table('couriers')->find($id)->api_secret);
        $migration->up();
        $this->assertSame('already-plain', Courier::findOrFail($id)->api_key);
        $this->assertSame('still-encrypted', Courier::findOrFail($id)->api_secret);
    }

    public function test_guest_cannot_view_save_test_or_diagnose_credentials(): void
    {
        $courier = $this->courier();
        auth()->logout();
        $this->getJson(route('admin.courier-api-settings.index'))->assertUnauthorized();
        $this->putJson(route('admin.courier-api-settings.update', $courier), [])->assertUnauthorized();
        $this->postJson(route('admin.courier-api-settings.test', $courier))->assertUnauthorized();
        $this->postJson(route('admin.courier-api-settings.diagnose', $courier))->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_redirect_response_is_not_followed_and_only_one_read_request_is_sent(): void
    {
        $courier = $this->courier(['api_key' => 'fixture-key', 'api_secret' => 'fixture-secret']);
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);
        $this->post(route('admin.courier-api-settings.test', $courier))->assertSessionHas('error');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === Courier::DEFAULT_STEADFAST_BASE_URL.'/get_balance');
        // HTTP fakes do not execute Guzzle redirects; inspect the actual client options too.
        $method = new \ReflectionMethod(SteadfastService::class, 'client');
        $options = $method->invoke(app(SteadfastService::class), $courier)->getOptions();
        $this->assertFalse($options['allow_redirects']);
        $this->assertSame(10, $options['connect_timeout']);
        $this->assertSame(20, $options['timeout']);
    }

    public function test_credential_cleanup_dry_run_masks_candidates_and_never_changes_them(): void
    {
        $courier = $this->courier(['api_enabled' => true, 'api_key' => 'fixture@example.com', 'api_secret' => 'valid-fixture-secret']);
        $before = DB::table('couriers')->find($courier->id);
        $this->artisan('courier:clean-credentials')->assertSuccessful();
        $this->assertSame($before->api_key, DB::table('couriers')->find($courier->id)->api_key);
        $this->assertSame($before->api_secret, DB::table('couriers')->find($courier->id)->api_secret);
        $this->assertTrue($courier->fresh()->api_enabled);
        $this->assertStringNotContainsString('fixture@example.com', Artisan::output());
        $this->assertStringNotContainsString('valid-fixture-secret', Artisan::output());
    }

    private function order(Courier $courier): Order
    {
        return Order::create([
            'order_number' => 'COURIER-TEST', 'customer_name' => 'Fixture Customer',
            'mobile_number' => '01700000000', 'full_address' => 'Fixture address', 'district' => 'Dhaka', 'area' => 'Dhaka',
            'subtotal' => 100, 'packaging_cost' => 0, 'delivery_charge' => 50, 'grand_total' => 150,
            'payment_method' => 'cash_on_delivery', 'selected_courier_id' => $courier->id,
            'order_status' => 'pending',
        ]);
    }
}
