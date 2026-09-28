<?php

namespace Tests\Feature;

use App\Jobs\SendMetaConversion;
use App\Models\Combo;
use App\Models\MarketingSetting;
use App\Models\MetaConversionEvent;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMarketingSetting;
use App\Services\MetaConversionsApi;
use App\Services\RecordMetaPurchase;
use App\Support\MetaUserData;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaConversionsApiTest extends TestCase
{
    private const PIXEL = '111111111111111';
    private const TOKEN = 'test-only-not-a-real-meta-token';
    private array $input;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['meta.enabled' => true, 'meta.access_token' => self::TOKEN,
            'meta.graph_version' => 'v26.0', 'meta.queue_connection' => 'database']);
        Queue::fake();
        Notification::fake();
        Http::preventStrayRequests();
        MarketingSetting::current()->update(['pixel_enabled' => true, 'pixel_ids' => [self::PIXEL], 'platform_pixel_scope' => 'all']);
        $this->product = $this->product('cumin');
        $division = DB::table('bd_divisions')->insertGetId(['source_id' => 'test', 'name' => 'Dhaka', 'bn_name' => 'Dhaka']);
        $district = DB::table('bd_districts')->insertGetId(['source_id' => 'test', 'name' => 'Dhaka', 'bn_name' => 'Dhaka', 'division_id' => $division]);
        $upazila = DB::table('bd_upazilas')->insertGetId(['source_id' => 'test', 'name' => 'Dhaka', 'bn_name' => 'Dhaka', 'district_id' => $district]);
        $zone = \App\Models\DeliveryZone::create(['zone_name' => 'Dhaka', 'zone_type' => 'inside_dhaka', 'delivery_charge' => 60, 'is_active' => true]);
        $location = \App\Models\DeliveryLocation::create(['zone_id' => $zone->id, 'location_name' => 'Dhaka', 'is_active' => true]);
        $this->input = [
            'full_name' => 'Customer Example', 'mobile_number' => '01712345678',
            'customer_email' => 'customer@example.com', 'full_address' => 'Private delivery address',
            'order_note' => 'Private order note', 'payment_method' => 'cash_on_delivery',
            'bd_division_id' => $division, 'bd_district_id' => $district, 'bd_upazila_id' => $upazila,
            'delivery_zone_id' => $zone->id, 'delivery_location_id' => $location->id,
            'items' => [['price_id' => $this->pack($this->product)->id]],
        ];
    }

    private function product(string $slug, ?int $vendorId = null): Product
    {
        $product = Product::create(['name_bn' => $slug, 'slug' => $slug, 'stock' => 100,
            'retail_price_1kg' => 1000, 'is_active' => true, 'show_in_retail' => true,
            'vendor_id' => $vendorId, 'approval_status' => 'approved']);
        $product->syncPrices();
        return $product;
    }

    private function pack(Product $product)
    {
        return $product->prices()->where('sell_type', 'retail')->where('quantity_gram', 1000)->firstOrFail();
    }

    private function place(array $changes = []): Order
    {
        $this->postJson(route('order.store'), array_replace($this->input, $changes))->assertOk()->assertJsonPath('success', true);
        return Order::latest('id')->firstOrFail();
    }

    private function recordingRequest(string $routeName = 'order.store'): Request
    {
        $request = Request::create('/order', 'POST', $this->input);
        $route = (new Route('POST', '/order', fn () => null))->name($routeName);
        $request->setRouteResolver(fn () => $route);
        return $request;
    }

    private function deliver(MetaConversionEvent $event): void
    {
        (new SendMetaConversion($event->id))->handle(app(MetaConversionsApi::class));
    }

    public function test_real_order_records_purchase_and_browser_matches_without_refresh_duplicates(): void
    {
        $order = $this->place();
        $event = MetaConversionEvent::sole();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($order->order_number, $event->event_id);
        $this->assertSame($order->created_at->timestamp, $event->event_time);
        $this->assertSame('Purchase', $event->event_name);
        $this->assertEquals($order->grand_total, $event->payload['event']['custom_data']['value']);
        Queue::assertPushed(SendMetaConversion::class, fn ($job) => $job->eventRecordId === $event->id && $job->afterCommit === true);
        Http::assertNothingSent();
        $html = $this->get(route('order.success', $order->order_number))->assertOk()->getContent();
        preg_match('/window\.MoslaPixelConfig = (.*?);<\/script>/s', $html, $matches);
        $config = json_decode($matches[1], true);
        $purchase = collect($config['queue'])->firstWhere('name', 'Purchase');
        $this->assertSame($event->event_id, $purchase['eventID']);
        $this->assertEquals($event->payload['event']['custom_data'], $purchase['params']);
        $this->get(route('order.success', $order->order_number))->assertOk();
        $this->assertSame(1, MetaConversionEvent::count());
        Queue::assertPushed(SendMetaConversion::class, 1);
        $this->assertStringNotContainsString(self::TOKEN, $matches[1]);
        $this->assertStringNotContainsString('customer@example.com', $matches[1]);
        $this->assertStringNotContainsString('01712345678', $matches[1]);
    }

    public function test_manual_payment_pending_is_still_a_placed_purchase(): void
    {
        PaymentSetting::current()->update(['bkash_enabled' => true]);
        $order = $this->place(['payment_method' => 'bkash', 'sender_number' => '01798765432',
            'transaction_id' => 'private-transaction', 'paid_amount' => 1000]);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($order->order_number, MetaConversionEvent::sole()->event_id);
        $this->assertStringNotContainsString('private-transaction', json_encode(MetaConversionEvent::sole()->payload));
    }

    public function test_duplicate_recording_keeps_original_snapshot_and_database_unique_identity(): void
    {
        $order = $this->place();
        $event = MetaConversionEvent::sole();
        $snapshot = $event->payload;
        $order->update(['grand_total' => 9999]);
        app(RecordMetaPurchase::class)->record($order, $this->recordingRequest());
        $this->assertSame(1, MetaConversionEvent::count());
        $this->assertSame($snapshot, $event->fresh()->payload);
        Queue::assertPushed(SendMetaConversion::class, 1);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $event->replicate()->save();
    }

    public function test_rollback_discards_event_and_after_commit_dispatch(): void
    {
        DB::beginTransaction();
        try {
            $this->place();
            $this->assertSame(1, MetaConversionEvent::count());
            Queue::assertNothingPushed();
        } finally {
            DB::rollBack();
        }
        $this->assertSame(0, Order::count());
        $this->assertSame(0, MetaConversionEvent::count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_dispatch_waits_for_outer_commit(): void
    {
        DB::beginTransaction();
        $this->place();
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushed(SendMetaConversion::class, 1);
    }

    public function test_real_database_queue_contains_only_record_reference_after_commit(): void
    {
        Queue::fake()->except([SendMetaConversion::class]);
        DB::beginTransaction();
        $this->place();
        $this->assertSame(0, DB::table('jobs')->count());
        DB::commit();
        $queued = DB::table('jobs')->sole();
        $this->assertSame('meta-capi', $queued->queue);
        $command = unserialize(json_decode($queued->payload, true)['data']['command']);
        $this->assertSame(MetaConversionEvent::sole()->id, $command->eventRecordId);
        foreach ([self::TOKEN, 'customer@example.com', '01712345678', 'Private delivery address', 'user_data'] as $private) {
            $this->assertStringNotContainsString($private, $queued->payload);
        }
        Http::assertNothingSent();
    }

    public function test_multiple_platform_destinations_have_independent_delivery_records(): void
    {
        MarketingSetting::current()->update(['pixel_ids' => [self::PIXEL, '444444444444444']]);
        $order = $this->place();
        $events = MetaConversionEvent::orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertSame([$order->order_number, $order->order_number], $events->pluck('event_id')->all());
        Http::fake([
            'graph.facebook.com/*/'.self::PIXEL.'/events' => Http::response(['events_received' => 1]),
            'graph.facebook.com/*/444444444444444/events' => Http::response([], 403),
        ]);
        foreach ($events as $event) {
            $this->deliver($event);
        }
        $this->assertSame('sent', $events[0]->fresh()->status);
        $this->assertSame('failed', $events[1]->fresh()->status);
    }

    public function test_disabled_and_missing_token_do_not_break_order_or_enqueue(): void
    {
        config(['meta.enabled' => false]);
        $this->place();
        config(['meta.enabled' => true, 'meta.access_token' => '']);
        $this->place();
        $this->assertSame(2, Order::count());
        $this->assertSame(0, MetaConversionEvent::count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_optional_recording_failure_does_not_rollback_order_or_leak_error(): void
    {
        Log::spy();
        MetaConversionEvent::creating(fn () => throw new \RuntimeException('private-recording-error'));
        try {
            $this->place();
            $this->assertSame(0, MetaConversionEvent::count());
            Log::shouldHaveReceived('warning')->with('Meta Purchase recording unavailable.')->once();
        } finally {
            MetaConversionEvent::flushEventListeners();
        }
    }

    public function test_http_payload_success_and_test_code_use_original_encrypted_snapshot(): void
    {
        MarketingSetting::current()->update(['test_event_code' => 'TEST123']);
        $this->place();
        $event = MetaConversionEvent::sole();
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        $this->deliver($event);
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v26.0/'.self::PIXEL.'/events'
            && $request->hasHeader('Authorization', 'Bearer '.self::TOKEN)
            && $request['data'] === [$event->payload['event']]
            && $request['test_event_code'] === 'TEST123'
            && ! isset($request['data'][0]['test_event_code']));
        $this->assertSame('sent', $event->fresh()->status);
        $this->assertNotNull($event->fresh()->sent_at);
        $this->assertSame(1, $event->fresh()->attempts);
        $this->deliver($event);
        Http::assertSentCount(1);
        $stored = DB::table('meta_conversion_events')->value('payload');
        foreach (['customer@example.com', '01712345678', self::TOKEN, 'user_data', 'Private delivery address'] as $private) {
            $this->assertStringNotContainsString($private, $stored);
            $this->assertStringNotContainsString($private, serialize(new SendMetaConversion($event->id)));
        }
        $this->assertArrayNotHasKey('payload', $event->toArray());
    }

    public function test_normalization_hashes_identity_but_not_browser_context(): void
    {
        $order = $this->place();
        $request = Request::create('/order', 'POST', ['customer_email' => ' CUSTOMER@Example.com '], [
            '_fbp' => 'fb.1.1720000000000.12345', '_fbc' => 'fb.1.1720000000000.Click_abc-123',
        ], [], ['REMOTE_ADDR' => '203.0.113.10', 'HTTP_USER_AGENT' => 'Test browser']);
        $user = User::factory()->create(['role' => 'customer']);
        $request->setUserResolver(fn () => $user);
        $order->mobile_number = '+৮৮০১৭১২৩৪৫৬৭৮';
        $data = MetaUserData::forPurchase($order, $request);
        $this->assertSame([hash('sha256', 'customer@example.com')], $data['em']);
        $this->assertSame([hash('sha256', '8801712345678')], $data['ph']);
        $this->assertSame([hash('sha256', 'bd')], $data['country']);
        $this->assertSame([hash('sha256', 'moslamart:user:'.$user->id)], $data['external_id']);
        $this->assertSame('203.0.113.10', $data['client_ip_address']);
        $this->assertSame('Test browser', $data['client_user_agent']);
        $this->assertSame('fb.1.1720000000000.12345', $data['fbp']);
        $this->assertSame('fb.1.1720000000000.Click_abc-123', $data['fbc']);
        $this->assertArrayNotHasKey('fn', $data);
        $this->assertArrayNotHasKey('ln', $data);
        $request = Request::create('/order', 'POST', ['customer_email' => 'invalid'], ['_fbp' => 'bad', '_fbc' => 'bad']);
        $order->mobile_number = 'invalid';
        $data = MetaUserData::forPurchase($order, $request);
        foreach (['em', 'ph', 'fbp', 'fbc', 'external_id'] as $key) {
            $this->assertArrayNotHasKey($key, $data);
        }
    }

    public function test_transient_failure_retries_exact_identity_time_and_commerce(): void
    {
        $order = $this->place();
        $event = MetaConversionEvent::sole();
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['error' => ['message' => 'private-remote-error']], 503)->push(['events_received' => 1])]);
        $job = (new SendMetaConversion($event->id))->withFakeQueueInteractions();
        $job->handle(app(MetaConversionsApi::class));
        $job->assertReleased(60);
        $this->assertSame('retry', $event->fresh()->status);
        $this->assertSame('http_503', $event->fresh()->last_error);
        $this->deliver($event); // Too early.
        Http::assertSentCount(1);
        $order->update(['grand_total' => 9999]);
        $this->travel(61)->seconds();
        $this->deliver($event);
        $this->assertSame('sent', $event->fresh()->status);
        $requests = Http::recorded();
        $this->assertSame($requests[0][0]->data(), $requests[1][0]->data());
        $this->assertArrayNotHasKey('test_event_code', $requests[0][0]->data());
    }

    public function test_network_errors_are_sanitized_and_retryable(): void
    {
        $this->place();
        $event = MetaConversionEvent::sole();
        Log::spy();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('secret-token customer@example.com'));
        $this->deliver($event);
        $this->assertSame('retry', $event->fresh()->status);
        $this->assertSame('network', $event->fresh()->last_error);
        $this->assertSame(1, Order::count());
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    public function test_permanent_auth_failure_is_not_retried_by_recovery(): void
    {
        $this->place();
        $event = MetaConversionEvent::sole();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 190, 'message' => self::TOKEN, 'is_transient' => true]], 400)]);
        $this->deliver($event);
        $this->assertSame('failed', $event->fresh()->status);
        $this->assertSame('http_400', $event->fresh()->last_error);
        Queue::fake();
        $this->artisan('meta:recover')->assertSuccessful();
        Queue::assertNothingPushed();
        $this->deliver($event);
        Http::assertSentCount(1);
    }

    public function test_retry_budget_and_age_are_bounded(): void
    {
        $this->place();
        $event = MetaConversionEvent::sole();
        Http::fake(['graph.facebook.com/*' => Http::response([], 429)]);
        for ($i = 0; $i < 5; $i++) {
            $this->deliver($event);
            $this->travel(61)->minutes();
        }
        $this->assertSame('failed', $event->fresh()->status);
        $this->assertSame(5, $event->fresh()->attempts);
        $this->deliver($event);
        Http::assertSentCount(5);
        $this->travelBack();
        $this->place();
        $old = MetaConversionEvent::latest('id')->first();
        $this->travel(25)->hours();
        $this->deliver($old);
        $this->assertSame('delivery_limit', $old->fresh()->last_error);
        Http::assertSentCount(5);
    }

    public function test_recovery_and_atomic_claim_skip_live_lease_but_recover_expired_worker(): void
    {
        $this->place();
        $event = MetaConversionEvent::sole();
        Queue::fake();
        $this->artisan('meta:recover')->assertSuccessful();
        Queue::assertPushed(SendMetaConversion::class, 1);
        $event->update(['status' => 'sending', 'claim_token' => 'other-worker', 'locked_until' => now()->addMinute()]);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        $this->deliver($event);
        Http::assertNothingSent();
        $this->travel(61)->seconds();
        $this->artisan('meta:recover')->assertSuccessful();
        Queue::assertPushed(SendMetaConversion::class, 2);
        $this->deliver($event);
        $this->assertSame('sent', $event->fresh()->status);
    }

    public function test_queue_dispatch_failure_does_not_break_order_and_is_recoverable(): void
    {
        Queue::fake()->except([SendMetaConversion::class]);
        Log::spy();
        config(['meta.queue_connection' => 'missing-connection']);
        $this->place();
        $this->assertSame('pending', MetaConversionEvent::sole()->status);
        Log::shouldHaveReceived('warning')->with('Meta CAPI dispatch unavailable.', ['event_record_id' => MetaConversionEvent::sole()->id])->once();
        config(['meta.queue_connection' => 'database']);
        Queue::fake();
        $this->artisan('meta:recover')->assertSuccessful();
        Queue::assertPushed(SendMetaConversion::class, 1);
    }

    public function test_own_scope_filters_vendor_values_without_vendor_capi(): void
    {
        MarketingSetting::current()->update(['platform_pixel_scope' => 'own', 'vendor_pixels_enabled' => true, 'vendor_capi_allowed' => true]);
        $vendor = Vendor::create(['user_id' => User::factory()->create(['role' => 'vendor'])->id,
            'shop_name' => 'Vendor', 'slug' => 'vendor', 'owner_name' => 'Owner', 'phone' => '01700000000',
            'email' => 'vendor@example.com', 'status' => 'approved', 'is_active' => true]);
        VendorMarketingSetting::create(['vendor_id' => $vendor->id, 'pixel_enabled' => true, 'pixel_id' => '222222222222222']);
        $vendorProduct = $this->product('vendor-product', $vendor->id);
        $this->place(['items' => [['price_id' => $this->pack($this->product)->id], ['price_id' => $this->pack($vendorProduct)->id]]]);
        $event = MetaConversionEvent::sole();
        $params = $event->payload['event']['custom_data'];
        $this->assertSame(self::PIXEL, $event->pixel_id);
        $this->assertEquals($this->pack($this->product)->final_price, $params['value']);
        $this->assertSame([$this->product->id.'-'.$this->pack($this->product)->id], $params['content_ids']);
        $this->place(['items' => [['price_id' => $this->pack($vendorProduct)->id]]]);
        $this->assertSame(1, MetaConversionEvent::count());
    }

    public function test_own_scope_omits_ambiguous_fixed_combos_without_changing_order(): void
    {
        MarketingSetting::current()->update(['platform_pixel_scope' => 'own']);
        $combo = Combo::create(['name' => 'Combo', 'slug' => 'combo', 'sell_type' => 'retail', 'sell_price' => 800, 'is_active' => true]);
        $combo->items()->create(['product_id' => $this->product->id, 'quantity_gram' => 1000,
            'sell_type' => 'retail', 'unit_price' => 1000, 'line_total' => 1000]);
        $order = $this->place(['combo_id' => $combo->id]);
        $this->assertSame('fixed_combo', $order->order_type);
        $this->assertSame(0, MetaConversionEvent::count());
        MarketingSetting::current()->update(['platform_pixel_scope' => 'all']);
        $order = $this->place(['combo_id' => $combo->id]);
        $this->assertEquals($order->grand_total, MetaConversionEvent::sole()->payload['event']['custom_data']['value']);
        $combo->update(['sell_type' => 'wholesale']);
        $this->place(['combo_id' => $combo->id]);
        $this->assertSame(1, MetaConversionEvent::count());
    }

    public function test_admin_and_vendor_requests_never_record_even_with_admin_pixel_enabled(): void
    {
        MarketingSetting::current()->update(['track_admin_users' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_admin' => true]));
        $this->place();
        $this->actingAs(User::factory()->create(['role' => 'vendor', 'is_admin' => false]));
        $this->place();
        $this->assertSame(0, MetaConversionEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_excluded_sources_and_routes_cannot_record_purchase(): void
    {
        config(['meta.enabled' => false]);
        $order = $this->place();
        config(['meta.enabled' => true]);
        foreach (['vendor_created_order', 'admin_created_order'] as $source) {
            $order->order_source = $source;
            app(RecordMetaPurchase::class)->record($order, $this->recordingRequest());
        }
        $order->order_source = 'customer_order';
        $order->order_type = 'wholesale';
        app(RecordMetaPurchase::class)->record($order, $this->recordingRequest());
        $order->order_type = 'single_product';
        foreach (['invoice.reorder.store', 'vendor.pos.store', 'customer.wholesale.quotes.confirm', 'order.success'] as $route) {
            app(RecordMetaPurchase::class)->record($order, $this->recordingRequest($route));
        }
        $order->load('items');
        $order->items->first()->sell_type = 'wholesale';
        app(RecordMetaPurchase::class)->record($order, $this->recordingRequest());
        $this->assertSame(0, MetaConversionEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_disabling_delivery_pauses_and_scope_change_cancels_existing_snapshot(): void
    {
        $this->place();
        $event = MetaConversionEvent::sole();
        config(['meta.enabled' => false]);
        $this->deliver($event);
        $this->assertSame('pending', $event->fresh()->status);
        config(['meta.enabled' => true]);
        MarketingSetting::current()->update(['platform_pixel_scope' => 'own']);
        $this->deliver($event);
        $this->assertSame('cancelled', $event->fresh()->status);
        Http::assertNothingSent();
    }
}
