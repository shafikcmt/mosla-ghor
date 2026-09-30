<?php

namespace Tests\Feature;

use App\Models\BotApiToken;
use App\Models\BotLead;
use App\Models\MarketingSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BotApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function token(array $abilities = null): string
    {
        [, $plain] = BotApiToken::issue('Test bot', $abilities ?? array_keys(BotApiToken::ABILITIES));
        return $plain;
    }

    private function api(string $token): static
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']);
    }

    private function product(string $slug, array $extra = []): Product
    {
        $p = Product::create(array_merge(['name_bn' => 'জিরা '.$slug, 'name_en' => 'Cumin '.$slug, 'slug' => $slug, 'stock' => 10,
            'retail_price_1kg' => 1000, 'is_active' => true, 'show_in_retail' => true, 'show_in_wholesale' => true,
            'approval_status' => 'approved'], $extra));
        $p->syncPrices();
        return $p;
    }

    private function order(string $number, string $phone, string $status = 'shipped'): int
    {
        $row = ['order_number' => $number, 'mobile_number' => $phone, 'customer_name' => 'Secret Name', 'full_address' => 'Secret Address',
            'order_status' => $status, 'grand_total' => 500, 'tracking_id' => 'TRK1', 'created_at' => now(), 'updated_at' => now()];
        foreach (Schema::getColumns('orders') as $col) {
            if (! $col['nullable'] && $col['default'] === null && ! $col['auto_increment'] && ! array_key_exists($col['name'], $row)) {
                $row[$col['name']] = str_contains(strtolower($col['type_name']), 'char') || str_contains(strtolower($col['type_name']), 'text') ? 'x' : 0;
            }
        }
        return DB::table('orders')->insertGetId($row);
    }

    public function test_requires_a_valid_unrevoked_token_with_the_ability(): void
    {
        $this->getJson('/api/bot/v1/ping')->assertUnauthorized();
        $this->api('mgb_wrong')->getJson('/api/bot/v1/ping')->assertUnauthorized();

        $limited = $this->token(['products:read']);
        $this->api($limited)->getJson('/api/bot/v1/ping')->assertOk()->assertJsonPath('abilities', ['products:read']);
        $this->api($limited)->getJson('/api/bot/v1/orders/status?phone=01712345678')->assertForbidden();

        BotApiToken::query()->update(['revoked_at' => now()]);
        $this->api($limited)->getJson('/api/bot/v1/products')->assertUnauthorized();
    }

    public function test_token_is_stored_hashed(): void
    {
        [$model, $plain] = BotApiToken::issue('Bot', ['products:read']);
        $this->assertStringStartsWith('mgb_', $plain);
        $this->assertSame(hash('sha256', $plain), DB::table('bot_api_tokens')->value('token_hash'));
        $this->assertArrayNotHasKey('token_hash', $model->toArray());
    }

    public function test_product_search_returns_public_prices_and_hides_inactive(): void
    {
        $this->product('iran');
        $this->product('hidden', ['is_active' => false]);
        $t = $this->token();

        $res = $this->api($t)->getJson('/api/bot/v1/products?q=Cumin')->assertOk();
        $this->assertSame(['iran'], array_map(fn ($u) => basename($u), array_column($res->json('data'), 'url')));
        $pack = $res->json('data.0.packs.0');
        $this->assertArrayHasKey('price', $pack);
        $this->assertArrayHasKey('label', $pack);
        $this->assertArrayNotHasKey('purchase_price', $res->json('data.0'));

        $id = $res->json('data.0.id');
        $this->api($t)->getJson("/api/bot/v1/products/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $hidden = Product::where('slug', 'hidden')->value('id');
        $this->api($t)->getJson("/api/bot/v1/products/{$hidden}")->assertNotFound();
    }

    public function test_highlights_lists_new_arrivals(): void
    {
        $this->product('iran');
        $this->api($this->token())->getJson('/api/bot/v1/catalog/highlights')->assertOk()
            ->assertJsonStructure(['best_sellers_30d', 'new_arrivals', 'combos'])
            ->assertJsonPath('new_arrivals.0.name', 'জিরা iran');
    }

    public function test_order_status_by_phone_without_personal_data(): void
    {
        $this->order('MM-1', '01712345678');
        $this->order('MM-2', '01712345678', 'pending');
        $this->order('MM-3', '01899999999');
        $t = $this->token();

        $res = $this->api($t)->getJson('/api/bot/v1/orders/status?phone=+8801712345678')->assertOk();
        $this->assertEqualsCanonicalizing(['MM-1', 'MM-2'], array_column($res->json('data'), 'order_number'));
        $this->assertStringNotContainsString('Secret', $res->getContent());

        $this->api($t)->getJson('/api/bot/v1/orders/status?phone=01712345678&order_number=MM-1')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.status_label', 'কুরিয়ারে');
        $this->api($t)->getJson('/api/bot/v1/orders/status?phone=123')->assertStatus(422);
    }

    public function test_lead_is_created_idempotently_and_sent_to_capi(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        MarketingSetting::current()->update(['pixel_enabled' => true, 'pixel_ids' => ['111111111111111'],
            'capi_enabled' => true, 'capi_access_token' => 'EAAGtest']);
        $product = $this->product('iran');
        $t = $this->token();
        $payload = ['source' => 'messenger', 'phone' => '+8801712345678', 'message' => '৫ কেজি জিরা চাই', 'customer_name' => 'Karim',
            'product_id' => $product->id, 'quantity' => '5 kg', 'external_ref' => 'psid-1:mid-1'];

        $first = $this->api($t)->postJson('/api/bot/v1/leads', $payload)->assertCreated()->assertJsonPath('duplicate', false);
        Http::assertSentCount(1);
        $event = json_decode(Http::recorded()[0][0]['data'], true)[0];

        // Retry with the same external_ref: same lead, no new Meta event (the test app re-runs
        // earlier after-response callbacks, so compare event ids rather than raw call counts).
        $this->api($t)->postJson('/api/bot/v1/leads', $payload)->assertOk()->assertJsonPath('duplicate', true)
            ->assertJsonPath('data.id', $first->json('data.id'));
        $ids = Http::recorded()->map(fn ($r) => json_decode($r[0]['data'], true)[0]['event_id'])->unique();
        $this->assertCount(1, $ids);

        $this->assertSame(1, BotLead::count());
        $lead = BotLead::first();
        $this->assertSame('01712345678', $lead->phone);
        $this->assertSame('new', $lead->status);
        $this->assertSame('Lead', $event['event_name']);
        $this->assertSame('chat', $event['action_source']);
        $this->assertSame('bot-lead-'.$lead->id, $event['event_id']);
        $this->assertArrayNotHasKey('client_ip_address', $event['user_data']);

        $this->api($t)->postJson('/api/bot/v1/leads', ['source' => 'sms', 'phone' => '123', 'message' => ''])
            ->assertStatus(422)->assertJsonValidationErrors(['source', 'phone', 'message']);
    }

    public function test_admin_creates_token_once_revokes_it_and_updates_leads(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $res = $this->actingAs($admin)->post(route('admin.bot-api.tokens.store'), ['name' => 'FB Bot', 'abilities' => ['products:read']]);
        $plain = session('bot_plain_token');
        $res->assertRedirect(route('admin.bot-api.index'));
        $this->assertNotNull(BotApiToken::findActive($plain));

        $this->actingAs($admin)->get(route('admin.bot-api.index'))->assertOk()->assertSee('FB Bot');

        $token = BotApiToken::first();
        $this->actingAs($admin)->post(route('admin.bot-api.tokens.revoke', $token))->assertRedirect();
        $this->assertNull(BotApiToken::findActive($plain));

        $lead = BotLead::create(['source' => 'messenger', 'phone' => '01712345678', 'message' => 'hi']);
        $this->actingAs($admin)->patch(route('admin.bot-api.leads.update', $lead), ['status' => 'converted', 'admin_note' => 'ordered'])
            ->assertRedirect();
        $this->assertSame('converted', $lead->fresh()->status);

        $this->actingAs(User::factory()->create(['is_admin' => false]))->get(route('admin.bot-api.index'))->assertRedirect();
    }
}
