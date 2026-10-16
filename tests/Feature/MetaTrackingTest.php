<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MarketingSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wishlist;
use App\Models\WholesaleEnquiry;
use App\Support\MetaCapi;
use App\Support\MetaPixel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Meta tracking hardening: quantities, one content-id scheme, shared event ids (browser ↔ CAPI),
 * new events (ViewContent CAPI, Search, AddToWishlist, AddToCart CAPI, InitiateCheckout CAPI, Contact),
 * deferred AddPaymentInfo, token safety, internal-traffic exclusion, test-event-code expiry.
 */
class MetaTrackingTest extends TestCase
{
    private const PLATFORM = '111111111111111';
    private const TOKEN = 'EAAGsecretTOKEN987';

    private Product $product;
    private ProductPrice $pack;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->product = $this->makeProduct('admin-cumin', null);
        $this->pack = $this->packOf($this->product);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
    }

    // ── fixtures ────────────────────────────────────────────────────────────

    private function makeProduct(string $slug, ?int $vendorId): Product
    {
        $p = Product::create(['name_bn' => $slug, 'slug' => $slug, 'stock' => 100, 'retail_price_1kg' => 1000, 'is_active' => true,
            'show_in_retail' => true, 'show_in_wholesale' => true, 'vendor_id' => $vendorId, 'approval_status' => 'approved']);
        $p->syncPrices();
        return $p;
    }

    private function packOf(Product $p, int $grams = 1000): ProductPrice
    {
        return $p->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->where('quantity_gram', $grams)->firstOrFail();
    }

    private function enable(array $extra = []): MarketingSetting
    {
        $s = MarketingSetting::current();
        $s->update(array_replace(['pixel_enabled' => true, 'pixel_ids' => [self::PLATFORM], 'platform_pixel_scope' => 'all',
            'capi_enabled' => true, 'capi_access_token' => self::TOKEN, 'track_admin_users' => false], $extra));
        return $s;
    }

    private function config(string $html): ?array
    {
        return preg_match('/window\.MoslaPixelConfig = (.*?);<\/script>/s', $html, $m) ? json_decode($m[1], true) : null;
    }

    private function browser(array $cfg, string $name): array
    {
        return array_values(array_filter($cfg['queue'], fn ($e) => $e['pixel'] === self::PLATFORM && $e['name'] === $name));
    }

    /** CAPI events posted to Meta in this test. */
    private function sent(?string $name = null): array
    {
        $events = [];
        foreach (Http::recorded() as [$request]) {
            $events = array_merge($events, json_decode($request['data'], true));
        }
        // Laravel replays earlier after-response callbacks on every later request inside ONE test; production
        // handles one request per process. Collapse replays so counts mean "distinct logical events".
        $unique = [];
        foreach ($events as $e) {
            $unique[$e['event_name'].'|'.$e['event_id']] ??= $e;
        }
        return array_values(array_filter($unique, fn ($e) => ! $name || $e['event_name'] === $name));
    }

    private function fillRequired(string $table, array $row): array
    {
        foreach (Schema::getColumns($table) as $col) {
            if ($col['nullable'] || $col['default'] !== null || $col['auto_increment'] || array_key_exists($col['name'], $row)) {
                continue;
            }
            $type = strtolower($col['type_name']);
            $row[$col['name']] = match (true) {
                str_contains($type, 'int'), str_contains($type, 'dec'), str_contains($type, 'num'), str_contains($type, 'real'),
                str_contains($type, 'float'), str_contains($type, 'double') => 0,
                str_contains($type, 'date'), str_contains($type, 'time') => now(),
                default => 'test',
            };
        }
        return $row;
    }

    /** An order exactly as OrderController stores it: unit_price = ONE pack, line_total = unit × packs. */
    private function order(Product $product, ProductPrice $pack, int $packs, ?float $grandTotal = null): Order
    {
        $unit = (float) $pack->final_price;
        $id = DB::table('orders')->insertGetId($this->fillRequired('orders', [
            'order_number' => 'MM-TRK-'.random_int(1000, 9999), 'mobile_number' => '01712345678', 'customer_name' => 'Rahim Uddin',
            'grand_total' => $grandTotal ?? $unit * $packs + 60, 'created_at' => now(), 'updated_at' => now(),
        ]));
        DB::table('order_items')->insert($this->fillRequired('order_items', [
            'order_id' => $id, 'product_id' => $product->id, 'price_id' => $pack->id, 'vendor_id' => $product->vendor_id,
            'product_name' => $product->name_bn, 'unit_price' => $unit, 'line_total' => $unit * $packs,
            'quantity_gram' => 1000 * $packs, 'created_at' => now(), 'updated_at' => now(),
        ]));
        return Order::findOrFail($id);
    }

    private function runJs(array $cfg, string $steps): array
    {
        $script = <<<'JS'
const fs = require('node:fs'), vm = require('node:vm');
const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const calls = [], fetches = [], timers = []; let items = []; const listeners = {};
const box = { value: '', _l: {}, addEventListener(t, f) { (this._l[t] = this._l[t] || []).push(f); } };
const fire = (t, ev) => (box._l[t] || []).forEach(f => f(ev || {}));
const document = { readyState: 'complete', querySelector: () => ({ getAttribute: () => 'tok' }),
    getElementById: id => id === 'hm-product-search' ? box : null, addEventListener(t, f) { listeners[t] = f; } };
const window = { MoslaPixelConfig: input.config, fbq: (...a) => calls.push(a),
    fetch: (u, o) => { fetches.push({ u, body: JSON.parse(o.body) }); return Promise.resolve({}); },
    msCart: { get: () => items, replace: n => { items = n; } } };
const ctx = { window, document, Date, Math, JSON, parseInt, parseFloat, String,
    setTimeout: f => { timers.push(f); return timers.length; }, clearTimeout: id => { if (id) timers[id - 1] = null; } };
vm.runInNewContext(fs.readFileSync('public/js/mosla-pixel.js', 'utf8'), ctx);
const click = href => listeners.click({ target: { closest: () => ({ getAttribute: () => href }) } });
const flush = () => { timers.splice(0).forEach(f => f && f()); };
const cart = window.msCart;
eval(input.steps);
process.stdout.write(JSON.stringify({ calls, fetches }));
JS;
        $process = new Process(['node', '-e', $script], base_path());
        $process->setInput(json_encode(['config' => $cfg, 'steps' => $steps]));
        $process->mustRun();
        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function jsConfig(): array
    {
        return $this->config($this->get('/')->assertOk()->getContent());
    }

    private function cartItem(int $qty, ?string $uid = 'u1'): string
    {
        return json_encode(['uid' => $uid, 'productId' => $this->product->id, 'priceId' => $this->pack->id,
            'price' => (float) $this->pack->final_price, 'qty' => $qty, 'nameBn' => 'cumin']);
    }

    // ── content ids / ViewContent ───────────────────────────────────────────

    public function test_view_content_params_and_shared_event_id_with_capi(): void
    {
        $this->enable();
        $cfg = $this->config($this->get('/products/admin-cumin')->assertOk()->getContent());
        $vc = $this->browser($cfg, 'ViewContent');
        $this->assertCount(1, $vc, 'exactly one browser ViewContent');

        $id = $this->product->id.'-'.$this->pack->id; // cheapest pack is the 25 g one — recompute below
        $cheapest = $this->product->prices()->where('is_active', true)->whereNull('product_variant_id')->where('sell_type', 'retail')->orderBy('final_price')->first();
        $id = $this->product->id.'-'.$cheapest->id;
        $p = $vc[0]['params'];
        $this->assertSame([$id], $p['content_ids']);
        $this->assertSame('product', $p['content_type']);
        $this->assertEquals([['id' => $id, 'quantity' => 1, 'item_price' => round((float) $cheapest->final_price, 2)]], $p['contents']);
        $this->assertEquals((float) $cheapest->final_price, $p['value']);
        $this->assertSame('BDT', $p['currency']);
        $this->assertNotEmpty($p['content_name']);

        $capi = $this->sent('ViewContent');
        $this->assertCount(1, $capi);
        $this->assertSame($vc[0]['eventID'], $capi[0]['event_id'], 'browser and CAPI ViewContent share the event_id');
        $this->assertEquals($p, $capi[0]['custom_data']);
        $this->assertSame('website', $capi[0]['action_source']);
    }

    public function test_view_content_is_not_sent_to_capi_for_bots_and_not_duplicated(): void
    {
        $this->enable();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/products/admin-cumin')->assertOk();
        $this->assertSame([], $this->sent('ViewContent'));

        // Calling the builder twice in one request still yields one event per pixel.
        $this->get('/'); // boots a request so state exists
        $request = request();
        MetaPixel::viewContent($this->product, 10.0, $this->pack->id);
        MetaPixel::viewContent($this->product, 10.0, $this->pack->id);
        $this->assertCount(1, array_filter(MetaPixel::events(), fn ($e) => $e['name'] === 'ViewContent'));
    }

    public function test_content_ids_match_across_the_whole_funnel(): void
    {
        $this->enable();
        $expected = $this->product->id.'-'.$this->pack->id;
        $packs = 2;

        // AddToCart (server copy)
        $this->postJson('/meta/add-to-cart', ['event_id' => 'atc-abcdef123456', 'price_id' => $this->pack->id, 'qty' => $packs])->assertNoContent();
        $this->assertSame([$expected], $this->sent('AddToCart')[0]['custom_data']['content_ids']);

        // InitiateCheckout
        $this->post('/checkout/start', ['items' => [$this->pack->id], 'qty' => [$packs]]);
        $cfg = $this->config($this->get('/checkout/review')->assertOk()->getContent());
        $this->assertSame([$expected], $this->browser($cfg, 'InitiateCheckout')[0]['params']['content_ids']);
        $this->assertSame([$expected], $this->sent('InitiateCheckout')[0]['custom_data']['content_ids']);

        // Purchase
        $order = $this->order($this->product, $this->pack, $packs);
        $this->assertSame([$expected], MetaPixel::platformPurchaseParams($order)['content_ids']);

        // JS AddToCart + ViewContent on the winning pack
        $js = $this->runJs($this->jsConfig(), "cart.replace([{$this->cartItem($packs)}]);");
        $atc = array_values(array_filter($js['calls'], fn ($c) => $c[2] === 'AddToCart'));
        $this->assertSame([$expected], $atc[0][3]['content_ids']);
        $this->assertSame('product', $atc[0][3]['content_type']);
    }

    // ── quantities ──────────────────────────────────────────────────────────

    public function test_initiate_checkout_quantities_value_and_shared_event_id_and_refresh_guard(): void
    {
        $this->enable();
        $unit = (float) $this->pack->final_price;
        $this->post('/checkout/start', ['items' => [$this->pack->id], 'qty' => [3]]);

        $cfg = $this->config($this->get('/checkout/review')->assertOk()->getContent());
        $ic = $this->browser($cfg, 'InitiateCheckout');
        $this->assertCount(1, $ic);
        $p = $ic[0]['params'];
        $id = $this->product->id.'-'.$this->pack->id;
        $this->assertEquals([['id' => $id, 'quantity' => 3, 'item_price' => round($unit, 2)]], $p['contents']);
        $this->assertSame(3, $p['num_items']);
        $this->assertEquals(round($unit * 3, 2), $p['value']);
        $this->assertSame('product', $p['content_type']);

        $capi = $this->sent('InitiateCheckout');
        $this->assertCount(1, $capi);
        $this->assertSame($ic[0]['eventID'], $capi[0]['event_id']);
        $this->assertEquals($p, $capi[0]['custom_data']);

        // Refresh with the same cart: no second conversion event, browser or server.
        $cfg = $this->config($this->get('/checkout/review')->assertOk()->getContent());
        $this->assertSame([], $this->browser($cfg, 'InitiateCheckout'));
        $this->assertCount(1, $this->sent('InitiateCheckout'));

        // A changed cart is a new checkout intent.
        $this->post('/checkout/start', ['items' => [$this->pack->id], 'qty' => [4]]);
        $cfg = $this->config($this->get('/checkout/review')->getContent());
        $this->assertSame(4, $this->browser($cfg, 'InitiateCheckout')[0]['params']['num_items']);
    }

    public function test_add_payment_info_is_deferred_and_never_sent_to_capi(): void
    {
        $this->enable();
        $this->get('/');
        $items = [['product_id' => $this->product->id, 'price_id' => $this->pack->id, 'vendor_id' => null,
            'unit_price' => (float) $this->pack->final_price, 'line_total' => (float) $this->pack->final_price * 2, 'qty' => 2]];
        MetaPixel::paymentInfo($items);

        $events = array_values(array_filter(MetaPixel::events(), fn ($e) => $e['name'] === 'AddPaymentInfo'));
        $this->assertCount(1, $events);
        $this->assertTrue($events[0]['deferred'], 'must not fire on page view');
        $this->assertSame(2, $events[0]['params']['num_items']);
        $this->assertSame([], $this->sent('AddPaymentInfo'));

        // Browser: nothing until fire(); then once.
        $cfg = ['platform' => [self::PLATFORM], 'queue' => [array_merge($events[0], ['pixel' => self::PLATFORM])], 'vendors' => new \stdClass(), 'productVendors' => new \stdClass()];
        $js = $this->runJs($cfg, "window.MoslaPixel.fire('AddPaymentInfo'); window.MoslaPixel.fire('AddPaymentInfo');");
        $this->assertCount(1, array_filter($js['calls'], fn ($c) => $c[2] === 'AddPaymentInfo'));
        $js = $this->runJs($cfg, '');
        $this->assertCount(0, array_filter($js['calls'], fn ($c) => ($c[2] ?? '') === 'AddPaymentInfo'));
    }

    public function test_purchase_quantities_item_price_value_order_id_and_shared_event_id(): void
    {
        $this->enable();
        $unit = (float) $this->pack->final_price;
        $order = $this->order($this->product, $this->pack, 3, 1234.0);

        $params = MetaPixel::platformPurchaseParams($order);
        $id = $this->product->id.'-'.$this->pack->id;
        $this->assertEquals([['id' => $id, 'quantity' => 3, 'item_price' => round($unit, 2)]], $params['contents']);
        $this->assertSame(3, $params['num_items']);
        $this->assertEquals(1234.0, $params['value'], 'Purchase value is the order grand total');
        $this->assertSame('BDT', $params['currency']);
        $this->assertSame('product', $params['content_type']);
        $this->assertSame($order->order_number, $params['order_id']);

        // Browser
        $cfg = $this->config($this->withSession([MetaPixel::PURCHASE_KEY => $order->order_number])
            ->get(route('order.success', $order->order_number))->getContent());
        $browser = $this->browser($cfg, 'Purchase');
        $this->assertCount(1, $browser);
        $this->assertEquals($params, $browser[0]['params']);

        // CAPI
        MetaCapi::purchase($order, 'Rahim@Example.com');
        $this->get('/'); // after-response jobs run when the next request terminates
        $capi = $this->sent('Purchase');
        $this->assertCount(1, $capi);
        $this->assertSame($browser[0]['eventID'], $capi[0]['event_id']);
        $this->assertEquals($params, $capi[0]['custom_data']);
    }

    // ── AddToCart ───────────────────────────────────────────────────────────

    public function test_add_to_cart_js_sends_quantity_value_and_shared_event_id(): void
    {
        $this->enable();
        $unit = (float) $this->pack->final_price;
        $js = $this->runJs($this->jsConfig(), "cart.replace([{$this->cartItem(3)}]);");

        $atc = array_values(array_filter($js['calls'], fn ($c) => $c[2] === 'AddToCart'));
        $this->assertCount(1, $atc);
        $id = $this->product->id.'-'.$this->pack->id;
        $this->assertEquals([['id' => $id, 'quantity' => 3, 'item_price' => $unit]], $atc[0][3]['contents']);
        $this->assertEquals(round($unit * 3, 2), $atc[0][3]['value']);
        $this->assertSame(3, $atc[0][3]['num_items']);
        $this->assertSame('BDT', $atc[0][3]['currency']);

        $this->assertCount(1, $js['fetches']);
        $this->assertStringEndsWith('/meta/add-to-cart', $js['fetches'][0]['u']);
        $this->assertSame($atc[0][4]['eventID'], $js['fetches'][0]['body']['event_id'], 'server copy uses the browser event id');
        $this->assertSame(['event_id', 'price_id', 'qty'], array_keys($js['fetches'][0]['body']));
        $this->assertSame(3, $js['fetches'][0]['body']['qty']);
    }

    public function test_add_to_cart_js_fires_for_quantity_increase_only_not_for_reloads_or_decreases(): void
    {
        $this->enable();
        $js = $this->runJs($this->jsConfig(), "
            cart.replace([{$this->cartItem(2)}]);   // new line: 2 packs
            cart.replace([{$this->cartItem(2)}]);   // re-render / reload: nothing
            cart.replace([{$this->cartItem(5)}]);   // +3 packs
            cart.replace([{$this->cartItem(4)}]);   // decrease: nothing
        ");
        $atc = array_values(array_filter($js['calls'], fn ($c) => $c[2] === 'AddToCart'));
        $this->assertSame([2, 3], array_map(fn ($c) => $c[3]['contents'][0]['quantity'], $atc));
        $this->assertCount(2, $js['fetches']);
        $this->assertNotSame($js['fetches'][0]['body']['event_id'], $js['fetches'][1]['body']['event_id']);
    }

    public function test_add_to_cart_server_copy_recomputes_values_from_the_database(): void
    {
        $this->enable();
        $unit = (float) $this->pack->final_price;
        $this->postJson('/meta/add-to-cart', ['event_id' => 'atc-abcdef123456', 'price_id' => $this->pack->id, 'qty' => 4,
            'value' => 1, 'item_price' => 1])->assertNoContent();

        $e = $this->sent('AddToCart')[0];
        $this->assertSame('atc-abcdef123456', $e['event_id']);
        $this->assertEquals(round($unit * 4, 2), $e['custom_data']['value'], 'client supplied value is ignored');
        $this->assertSame(4, $e['custom_data']['num_items']);
        $this->assertSame(4, $e['custom_data']['contents'][0]['quantity']);

        $this->postJson('/meta/add-to-cart', ['event_id' => 'bad id!', 'price_id' => $this->pack->id, 'qty' => 1])->assertStatus(422);
        $this->postJson('/meta/add-to-cart', ['event_id' => 'atc-abcdef123456', 'price_id' => 999999, 'qty' => 1])->assertNoContent();
        $this->assertCount(1, $this->sent('AddToCart'));
    }

    public function test_add_to_cart_server_copy_respects_scope_own_for_vendor_products(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $user->id, 'shop_name' => 'S', 'slug' => 's', 'owner_name' => 'O', 'phone' => '01700000001',
            'email' => 'v@example.com', 'status' => 'approved', 'is_active' => true]);
        $vp = $this->makeProduct('vendor-chili', $vendor->id);
        $this->enable(['platform_pixel_scope' => 'own']);

        $this->postJson('/meta/add-to-cart', ['event_id' => 'atc-abcdef123456', 'price_id' => $this->packOf($vp)->id, 'qty' => 1])->assertNoContent();
        $this->assertSame([], $this->sent('AddToCart'));
    }

    // ── Search / Contact ────────────────────────────────────────────────────

    public function test_search_fires_only_for_a_settled_non_empty_query(): void
    {
        $this->enable();
        $js = $this->runJs($this->jsConfig(), "
            box.value = 'm'; fire('input');
            box.value = 'mo'; fire('input'); box.value = 'mos'; fire('input');
            flush();                                  // typing settled → one event
            flush();                                  // nothing pending
            box.value = 'mos'; fire('input'); flush(); // same text again → no duplicate
            box.value = '   '; fire('input'); flush(); // empty → none
            box.value = 'holud'; fire('keydown', {key: 'Enter'}); // Enter sends immediately
            box.value = 'x'; fire('keydown', {key: 'Enter'});     // too short
        ");
        $search = array_values(array_filter($js['calls'], fn ($c) => $c[2] === 'Search'));
        $this->assertSame([['search_string' => 'mos'], ['search_string' => 'holud']], array_map(fn ($c) => $c[3], $search));
        $this->assertSame(self::PLATFORM, $search[0][1]);
    }

    public function test_contact_fires_for_whatsapp_and_phone_clicks_only_with_shared_event_id(): void
    {
        $this->enable();
        $js = $this->runJs($this->jsConfig(), "
            click('https://wa.me/8801700000000?text=hi');
            click('https://wa.me/8801700000000?text=hi');   // immediate double click → one
            click('tel:+8801700000000');
            click('https://example.com/about');             // ordinary link → none
        ");
        $contact = array_values(array_filter($js['calls'], fn ($c) => $c[2] === 'Contact'));
        $this->assertSame(['whatsapp', 'phone'], array_map(fn ($c) => $c[3]['contact_method'], $contact));
        $this->assertCount(2, $js['fetches']);
        $this->assertSame($contact[0][4]['eventID'], $js['fetches'][0]['body']['event_id']);
        $this->assertSame('phone', $js['fetches'][1]['body']['method']);

        // A page view alone fires nothing.
        $cfg = $this->jsConfig();
        $this->assertSame([], $this->browser($cfg, 'Contact'));
    }

    public function test_contact_server_copy_uses_the_browser_event_id(): void
    {
        $this->enable();
        $this->postJson('/meta/contact', ['event_id' => 'ct-abcdef123456', 'method' => 'whatsapp'])->assertNoContent();
        $e = $this->sent('Contact')[0];
        $this->assertSame('ct-abcdef123456', $e['event_id']);
        $this->assertSame(['contact_method' => 'whatsapp'], $e['custom_data']);

        $this->postJson('/meta/contact', ['event_id' => 'ct-abcdef123456', 'method' => 'email'])->assertStatus(422);
        $this->assertCount(1, $this->sent('Contact'));
    }

    // ── Wishlist ────────────────────────────────────────────────────────────

    private function customer(): User
    {
        $user = User::factory()->create(['role' => 'customer', 'is_admin' => false, 'phone' => '01755555555', 'email' => 'buyer@example.com']);
        Customer::create(['name' => 'Buyer One', 'mobile_number' => '01755555555', 'email' => 'buyer@example.com', 'is_active' => true]);
        return $user;
    }

    public function test_add_to_wishlist_fires_once_on_success_with_shared_event_id(): void
    {
        $this->enable();
        $user = $this->customer();

        $response = $this->actingAs($user)->from('/products/admin-cumin')->post(route('customer.wishlist.store', $this->product->id));
        $response->assertRedirect();
        $this->assertSame(1, Wishlist::count());

        $cfg = $this->config($this->actingAs($user)->get('/products/admin-cumin')->getContent());
        $wl = $this->browser($cfg, 'AddToWishlist');
        $this->assertCount(1, $wl);
        $capi = $this->sent('AddToWishlist');
        $this->assertCount(1, $capi);
        $this->assertSame($wl[0]['eventID'], $capi[0]['event_id']);
        $this->assertEquals($wl[0]['params'], $capi[0]['custom_data']);
        $this->assertSame('product', $wl[0]['params']['content_type']);
        $this->assertSame($this->product->id.'-', substr($wl[0]['params']['content_ids'][0], 0, strlen($this->product->id) + 1));
        // hashed identifiers of the signed-in customer, never raw
        $this->assertSame([hash('sha256', '8801755555555')], $capi[0]['user_data']['ph']);
        $this->assertSame([hash('sha256', 'buyer@example.com')], $capi[0]['user_data']['em']);

        // Adding the same product again is not a new wishlist addition.
        $this->actingAs($user)->from('/products/admin-cumin')->post(route('customer.wishlist.store', $this->product->id));
        $this->assertSame(1, Wishlist::count());
        $this->assertCount(1, $this->sent('AddToWishlist'));
    }

    // ── Lead / CompleteRegistration / failures ──────────────────────────────

    public function test_lead_browser_and_capi_share_event_id(): void
    {
        $this->enable();
        $this->from('/products/admin-cumin')->post(route('products.enquiry.store', 'admin-cumin'), [
            'customer_name' => 'Karim', 'customer_phone' => '01812345678', 'delivery_location' => 'Dhaka', 'quantity_kg' => 50,
        ])->assertRedirect();
        $enquiry = WholesaleEnquiry::latest('id')->firstOrFail();

        $cfg = $this->config($this->get('/')->getContent());
        $lead = $this->browser($cfg, 'Lead');
        $this->assertSame('lead-'.$enquiry->id, $lead[0]['eventID']);
        $this->assertSame('lead-'.$enquiry->id, $this->sent('Lead')[0]['event_id']);
        $this->assertSame([(string) $this->product->id], $lead[0]['params']['content_ids']);
    }

    public function test_complete_registration_shares_event_id_and_failed_registration_fires_nothing(): void
    {
        $this->enable();
        $this->post(route('customer.register.post'), ['name' => 'X', 'mobile_number' => '01788888888',
            'password' => 'secret123', 'password_confirmation' => 'nope'])->assertSessionHasErrors();
        $this->assertSame([], $this->sent('CompleteRegistration'));
        $this->assertNull(session(MetaPixel::NEXT_PAGE_KEY));

        $this->post(route('customer.register.post'), ['name' => 'New', 'mobile_number' => '01788888888',
            'password' => 'secret123', 'password_confirmation' => 'secret123'])->assertRedirect();
        $user = User::where('phone', '01788888888')->firstOrFail();
        $cfg = $this->config($this->get('/')->getContent());
        $this->assertSame('reg-'.$user->id, $this->browser($cfg, 'CompleteRegistration')[0]['eventID']);
        $this->assertSame('reg-'.$user->id, $this->sent('CompleteRegistration')[0]['event_id']);
    }

    public function test_failed_order_fires_no_purchase(): void
    {
        $this->enable();
        $this->postJson(route('order.store'), [])->assertStatus(422);
        $this->assertNull(session(MetaPixel::PURCHASE_KEY));
        $this->assertSame([], $this->sent('Purchase'));
        // A stranger opening a success URL without the flag gets no Purchase either.
        $order = $this->order($this->product, $this->pack, 1);
        $cfg = $this->config($this->get(route('order.success', $order->order_number))->getContent());
        $this->assertSame([], $this->browser($cfg, 'Purchase'));
    }

    // ── customer data ───────────────────────────────────────────────────────

    public function test_user_data_hashing_and_unhashed_browser_fields(): void
    {
        $ud = MetaCapi::userData(['phone' => '01712345678', 'email' => '  Rahim@Example.COM ', 'name' => 'Rahim Uddin', 'external_id' => 42], false);
        $this->assertSame([hash('sha256', '8801712345678')], $ud['ph']);
        $this->assertSame([hash('sha256', 'rahim@example.com')], $ud['em']);
        $this->assertSame([hash('sha256', 'rahim')], $ud['fn']);
        $this->assertSame([hash('sha256', 'bd')], $ud['country']);
        $this->assertSame([hash('sha256', '42')], $ud['external_id']);
        foreach (['ln', 'ct', 'st', 'zp', 'db', 'ge'] as $never) {
            $this->assertArrayNotHasKey($never, $ud);
        }
        $this->assertArrayNotHasKey('client_ip_address', $ud, 'no request context when withRequest=false');
    }

    public function test_fbp_fbc_ip_and_user_agent_are_sent_unhashed_and_fbclid_is_the_fallback(): void
    {
        $this->enable();
        $this->withUnencryptedCookies(['_fbp' => 'fb.1.1700000000000.123456789', '_fbc' => 'fb.1.1700000000000.AbCdEf'])
            ->withHeader('User-Agent', 'Mozilla/5.0 TestPhone')
            ->post('/meta/contact', ['event_id' => 'ct-abcdef123456', 'method' => 'phone'], ['Accept' => 'application/json'])->assertNoContent();
        $ud = $this->sent('Contact')[0]['user_data'];
        $this->assertSame('fb.1.1700000000000.123456789', $ud['fbp']);
        $this->assertSame('fb.1.1700000000000.AbCdEf', $ud['fbc']);
        $this->assertSame('127.0.0.1', $ud['client_ip_address']);
        $this->assertSame('Mozilla/5.0 TestPhone', $ud['client_user_agent']);

    }

    public function test_fbclid_is_the_fbc_fallback_when_no_cookie_exists(): void
    {
        $this->enable();
        $this->post('/meta/contact?fbclid=IwAR_test-123', ['event_id' => 'ct-fedcba654321', 'method' => 'phone'], ['Accept' => 'application/json'])->assertNoContent();
        $ud = $this->sent('Contact')[0]['user_data'];
        $this->assertArrayNotHasKey('fbp', $ud, 'unavailable values are not fabricated');
        $this->assertMatchesRegularExpression('/^fb\.1\.\d+\.IwAR_test-123$/', $ud['fbc']);
    }

    // ── internal traffic ────────────────────────────────────────────────────

    public function test_admin_and_excluded_ips_are_not_tracked_anywhere(): void
    {
        $this->enable();
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $html = $this->actingAs($admin)->get('/products/admin-cumin')->getContent();
        $this->assertStringNotContainsString('fbevents.js', $html);
        $this->actingAs($admin)->postJson('/meta/add-to-cart', ['event_id' => 'atc-abcdef123456', 'price_id' => $this->pack->id, 'qty' => 1]);
        $this->actingAs($admin)->postJson('/meta/contact', ['event_id' => 'ct-abcdef123456', 'method' => 'phone']);
        $this->assertSame([], $this->sent());

        $this->app['auth']->forgetGuards();
        config(['services.meta.exclude_ips' => ['127.0.0.1']]);
        $this->assertStringNotContainsString('fbevents.js', $this->get('/products/admin-cumin')->getContent());
        $this->postJson('/meta/contact', ['event_id' => 'ct-abcdef123456', 'method' => 'phone']);
        $this->assertSame([], $this->sent());
    }

    // ── token safety ────────────────────────────────────────────────────────

    public function test_capi_token_is_never_flashed_rendered_or_logged(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->from('/admin/marketing-settings')->post(route('admin.marketing-settings.update'), [
            'pixel_enabled' => '0', 'pixel_ids' => '', 'platform_pixel_scope' => 'all',
            'capi_enabled' => '1', 'capi_access_token' => self::TOKEN,
        ]);
        $response->assertSessionHasErrors('capi_enabled');
        $this->assertNull(session()->getOldInput('capi_access_token'), 'token must not be flashed into the session');
        $this->assertStringNotContainsString(self::TOKEN, json_encode(session()->all()));

        // Saved properly: encrypted at rest, absent from every page and from the admin form.
        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), [
            'pixel_enabled' => '1', 'pixel_ids' => self::PLATFORM, 'platform_pixel_scope' => 'all',
            'capi_enabled' => '1', 'capi_access_token' => self::TOKEN,
        ])->assertSessionHasNoErrors();
        $this->assertNotSame(self::TOKEN, DB::table('marketing_settings')->value('capi_access_token'));
        $this->assertStringNotContainsString(self::TOKEN, $this->actingAs($admin)->get(route('admin.marketing-settings.index'))->getContent());
        $this->app['auth']->forgetGuards();
        $this->assertStringNotContainsString(self::TOKEN, $this->get('/')->getContent());
        $this->assertStringNotContainsString(self::TOKEN, $this->get('/products/admin-cumin')->getContent());
        $this->assertStringNotContainsString(self::TOKEN, (string) json_encode(MarketingSetting::first()->toArray()));
    }

    // ── test_event_code ─────────────────────────────────────────────────────

    public function test_test_event_code_expires_and_is_only_set_for_two_hours(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), [
            'pixel_enabled' => '1', 'pixel_ids' => self::PLATFORM, 'platform_pixel_scope' => 'all',
            'capi_enabled' => '1', 'capi_access_token' => self::TOKEN, 'test_event_code' => 'TEST123',
        ])->assertSessionHasNoErrors();
        $s = MarketingSetting::first();
        $this->assertTrue($s->test_event_code_expires_at->between(now()->addMinutes(119), now()->addMinutes(121)));
        $this->assertSame('TEST123', MetaCapi::activeTestCode($s));

        MetaCapi::send([MetaCapi::event('Contact', 'ct-aaaaaaaaaa', [], [], url('/'))]);
        Http::assertSent(fn ($r) => ($r['test_event_code'] ?? null) === 'TEST123');

        // Expired: the code stays on file but production events stop going to Test Events.
        $s->update(['test_event_code_expires_at' => now()->subMinute()]);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        MetaCapi::send([MetaCapi::event('Contact', 'ct-bbbbbbbbbb', [], [], url('/'))]);
        Http::assertSent(fn ($r) => ! isset($r['test_event_code']) && str_contains($r['data'], 'ct-bbbbbbbbbb'));

        // Clearing the field clears the expiry too; default install has no code.
        $this->actingAs($admin)->post(route('admin.marketing-settings.update'), [
            'pixel_enabled' => '1', 'pixel_ids' => self::PLATFORM, 'platform_pixel_scope' => 'all',
            'capi_enabled' => '1', 'test_event_code' => '',
        ])->assertSessionHasNoErrors();
        $this->assertNull(MarketingSetting::first()->test_event_code_expires_at);
        $this->assertNull(MetaCapi::activeTestCode(MarketingSetting::first()));
    }

    // ── duplicate protection / admin copy ───────────────────────────────────

    public function test_one_base_pixel_snippet_and_one_page_view_per_page(): void
    {
        $this->enable();
        $html = $this->get('/products/admin-cumin')->getContent();
        $this->assertSame(1, substr_count($html, "fbq('init'"));
        $this->assertSame(1, substr_count($html, "'PageView'"));
        $this->assertSame(1, substr_count($html, 'window.MoslaPixelConfig ='));
        $this->assertSame(1, substr_count($html, 'js/mosla-pixel.js'));
    }

    public function test_admin_screen_lists_exactly_the_supported_events(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $html = $this->actingAs($admin)->get(route('admin.marketing-settings.index'))->assertOk()->getContent();
        foreach (['PageView', 'ViewContent', 'Search', 'AddToWishlist', 'AddToCart', 'InitiateCheckout', 'AddPaymentInfo', 'Purchase', 'Lead', 'CompleteRegistration', 'Contact'] as $event) {
            $this->assertStringContainsString($event, $html);
        }
        $this->assertStringContainsString('event ID', $html);
        $this->assertStringContainsString('২ ঘণ্টা', $html);
    }
}
