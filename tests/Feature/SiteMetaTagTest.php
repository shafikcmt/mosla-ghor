<?php

namespace Tests\Feature;

use App\Models\SiteMetaTag;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use App\Support\MetaTagRules;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteMetaTagTest extends TestCase
{
    private const FB = '<meta name="facebook-domain-verification" content="66ubp1k34fbb0nykwwf1rhhftbxij9">';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Cache::forget(SiteMetaTag::CACHE_KEY);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function fbTag(array $overrides = []): SiteMetaTag
    {
        return SiteMetaTag::create(array_replace([
            'label' => 'Facebook', 'attribute' => 'name', 'name' => 'facebook-domain-verification',
            'content' => '66ubp1k34fbb0nykwwf1rhhftbxij9', 'is_active' => true,
        ], $overrides));
    }

    private function valid(array $overrides = []): array
    {
        return array_replace(['label' => 'Facebook', 'attribute' => 'name', 'name' => 'facebook-domain-verification',
            'content' => '66ubp1k34fbb0nykwwf1rhhftbxij9', 'is_active' => 1, 'sort_order' => 0], $overrides);
    }

    public function test_tag_renders_on_home_and_maintenance_notice_with_escaped_values(): void
    {
        $this->fbTag();
        $this->fbTag(['name' => 'custom-check', 'content' => 'a"b&c\'d']);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString(self::FB, $home);
        $this->assertStringContainsString('<meta name="custom-check" content="a&quot;b&amp;c&#039;d">', $home);

        foreach (['maintenance_enabled' => '1', 'maintenance_mode' => 'full'] as $k => $v) {
            WebsiteSetting::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        $this->get('/')->assertStatus(503)->assertSee(self::FB, false);
    }

    public function test_tag_renders_in_other_storefront_shells(): void
    {
        $this->fbTag();
        $this->get('/faq')->assertOk()->assertSee(self::FB, false);                // storefront.layout
        $this->get('/track-order')->assertOk()->assertSee(self::FB, false);        // storefront.layout
    }

    public function test_inactive_tag_not_rendered(): void
    {
        $this->fbTag(['is_active' => false]);
        $this->get('/')->assertOk()->assertDontSee('facebook-domain-verification', false);
    }

    public function test_not_rendered_on_admin_or_vendor_pages(): void
    {
        $this->fbTag();
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->assertDontSee('66ubp1k34fbb0nykwwf1rhhftbxij9', false);
        auth()->logout();

        $user = User::factory()->create(['role' => 'vendor']);
        Vendor::create(['user_id' => $user->id, 'shop_name' => 'QA', 'slug' => 'qa', 'owner_name' => 'O', 'phone' => '01700000009',
            'email' => 'qa@example.com', 'status' => 'approved', 'is_active' => true]);
        $this->actingAs($user)->get('/vendor/dashboard')->assertOk()->assertDontSee('66ubp1k34fbb0nykwwf1rhhftbxij9', false);
    }

    public function test_admin_can_add_edit_toggle_and_delete(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.site-meta-tags.index'))->assertOk()->assertSee('সাইট ভেরিফিকেশন ও মেটা ট্যাগ');

        $this->actingAs($admin)->post(route('admin.site-meta-tags.store'), $this->valid())->assertSessionHasNoErrors();
        $tag = SiteMetaTag::firstOrFail();
        $this->actingAs($admin)->get(route('admin.site-meta-tags.index'))->assertSee(e(self::FB), false);

        $this->actingAs($admin)->put(route('admin.site-meta-tags.update', $tag), $this->valid(['content' => 'newvalue123']))->assertSessionHasNoErrors();
        $this->assertSame('newvalue123', $tag->fresh()->content);

        $this->actingAs($admin)->post(route('admin.site-meta-tags.toggle', $tag))->assertRedirect();
        $this->assertFalse($tag->fresh()->is_active);

        $this->actingAs($admin)->delete(route('admin.site-meta-tags.destroy', $tag))->assertRedirect();
        $this->assertSame(0, SiteMetaTag::count());
    }

    public function test_script_or_angle_brackets_in_content_rejected(): void
    {
        $admin = $this->admin();
        foreach (['<script>alert(1)</script>', '"><script>x</script>', 'abc>def', 'a<b'] as $bad) {
            $this->actingAs($admin)->post(route('admin.site-meta-tags.store'), $this->valid(['content' => $bad]))
                ->assertSessionHasErrors(['content' => 'Content-এ < বা > চিহ্ন দেওয়া যাবে না — শুধু মানটি দিন, পুরো HTML নয়।']);
        }
        $this->actingAs($admin)->post(route('admin.site-meta-tags.store'), $this->valid(['content' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('content');
        $this->assertSame(0, SiteMetaTag::count());
    }

    public function test_bad_names_attributes_and_reserved_names_rejected(): void
    {
        $admin = $this->admin();
        foreach (['description', 'Keywords', 'ROBOTS', 'viewport', 'og:title', 'OG:Image', 'twitter:card', 'csrf-token'] as $reserved) {
            $this->actingAs($admin)->post(route('admin.site-meta-tags.store'), $this->valid(['name' => $reserved]))
                ->assertSessionHasErrors('name');
        }
        foreach (['bad name', 'x"onload=1', 'a<b', str_repeat('a', 101)] as $bad) {
            $this->actingAs($admin)->post(route('admin.site-meta-tags.store'), $this->valid(['name' => $bad]))
                ->assertSessionHasErrors('name');
        }
        $this->actingAs($admin)->post(route('admin.site-meta-tags.store'), $this->valid(['attribute' => 'http-equiv']))
            ->assertSessionHasErrors(['attribute' => 'ট্যাগের ধরন শুধু name বা property হতে পারে।']);
        $this->assertSame(0, SiteMetaTag::count());
    }

    public function test_smart_paste_parses_facebook_and_google_tags_without_saving(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.site-meta-tags.parse'), [
            'paste' => '<meta name="facebook-domain-verification" content="66ubp1k34fbb0nykwwf1rhhftbxij9" />',
        ])->assertSessionHasNoErrors()->assertSessionHasInput([
            'attribute' => 'name', 'name' => 'facebook-domain-verification',
            'content' => '66ubp1k34fbb0nykwwf1rhhftbxij9', 'preset' => 'facebook',
        ]);

        $this->actingAs($admin)->post(route('admin.site-meta-tags.parse'), [
            'paste' => "  <META content='AbC-123_xyz' name='google-site-verification'>  ",
        ])->assertSessionHasNoErrors()->assertSessionHasInput([
            'name' => 'google-site-verification', 'content' => 'AbC-123_xyz', 'preset' => 'google',
        ]);

        $this->assertSame(0, SiteMetaTag::count(), 'Parsing never saves anything.');
        $this->assertFalse(session()->hasOldInput('paste'), 'The raw paste is never kept.');
    }

    public function test_smart_paste_rejects_scripts_and_anything_but_one_meta_tag(): void
    {
        $admin = $this->admin();
        foreach ([
            '<script>alert(1)</script>',
            '<meta name="x" content="y"><script>alert(1)</script>',
            '<meta name="a" content="1"><meta name="b" content="2">',
            '<meta http-equiv="refresh" content="0;url=https://evil.example">',
            '<meta charset="utf-8">',
            '<meta name="x" content="y" onload="alert(1)">',
            '<meta name=x content=y>',
            '<meta name="description" content="hijack">',
            '<meta property="og:image" content="https://evil.example/x.png">',
            '<meta name="x" content="&lt;script&gt;">',
            'facebook-domain-verification 66ubp1k34fbb0nykwwf1rhhftbxij9',
        ] as $bad) {
            $this->actingAs($admin)->post(route('admin.site-meta-tags.parse'), ['paste' => $bad])
                ->assertSessionHasErrors('paste');
        }
        $this->assertSame(0, SiteMetaTag::count());
    }

    public function test_output_regate_skips_rows_edited_directly_in_database(): void
    {
        $tag = $this->fbTag();
        \DB::table('site_meta_tags')->where('id', $tag->id)->update(['content' => '"><script>alert(1)</script>']);
        Cache::forget(SiteMetaTag::CACHE_KEY);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('facebook-domain-verification', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_cache_is_cleared_after_save_toggle_and_delete(): void
    {
        $admin = $this->admin();
        $tag = $this->fbTag();
        $this->get('/')->assertSee('66ubp1k34fbb0nykwwf1rhhftbxij9', false);
        $this->assertTrue(Cache::has(SiteMetaTag::CACHE_KEY), 'Active tags are cached.');

        $this->actingAs($admin)->put(route('admin.site-meta-tags.update', $tag), $this->valid(['content' => 'changedValue99']));
        auth()->logout();
        $this->get('/')->assertSee('changedValue99', false)->assertDontSee('66ubp1k34fbb0nykwwf1rhhftbxij9', false);

        $this->actingAs($admin)->post(route('admin.site-meta-tags.toggle', $tag));
        auth()->logout();
        $this->get('/')->assertDontSee('changedValue99', false);

        $this->actingAs($admin)->post(route('admin.site-meta-tags.toggle', $tag));
        $this->actingAs($admin)->delete(route('admin.site-meta-tags.destroy', $tag));
        auth()->logout();
        $this->get('/')->assertDontSee('changedValue99', false);
    }

    public function test_render_helper_matches_output_format(): void
    {
        $this->assertSame(self::FB, MetaTagRules::render('name', 'facebook-domain-verification', '66ubp1k34fbb0nykwwf1rhhftbxij9'));
        $this->assertSame('<meta property="x:y" content="&quot;">', MetaTagRules::render('property', 'x:y', '"'));
    }
}
