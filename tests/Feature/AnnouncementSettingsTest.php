<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnouncementSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function admin(): User
    {
        return User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'role'     => 'admin',
            'is_admin' => true,
        ]);
    }

    /** Website-settings update requires the always-present base fields. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'site_name'  => 'মসলা ঘর',
            'hero_title' => 'খাঁটি মসলা',
        ], $overrides);
    }

    public function test_admin_can_save_announcement_settings(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.website-settings.update'), $this->payload([
                'announcement_enabled'    => '1',
                'announcement_text_1'     => 'ঈদ স্পেশাল ছাড়',
                'announcement_text_2'     => 'হোম ডেলিভারি',
                'announcement_link_url'   => 'https://example.com/shop',
                'announcement_link_label' => 'অর্ডার করুন',
                'announcement_bg_color'   => '#C9A227',
                'announcement_text_color' => '#064E2E',
                'announcement_speed'      => 'fast',
            ]))
            ->assertRedirect(route('admin.website-settings.index'));

        $this->assertSame('1', WebsiteSetting::get('announcement_enabled'));
        $this->assertSame('ঈদ স্পেশাল ছাড়', WebsiteSetting::get('announcement_text_1'));

    }

    public function test_text_is_required_when_enabled(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.website-settings.update'), $this->payload([
                'announcement_enabled' => '1',
                'announcement_text_1'  => '',
            ]))
            ->assertSessionHasErrors('announcement_text_1');
    }

    public function test_invalid_link_url_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.website-settings.update'), $this->payload([
                'announcement_enabled' => '1',
                'announcement_text_1'  => 'ok',
                'announcement_link_url' => 'not-a-url',
            ]))
            ->assertSessionHasErrors('announcement_link_url');
    }

    public function test_enabled_announcement_shows_on_home(): void
    {
        WebsiteSetting::updateOrCreate(['key' => 'announcement_enabled'], ['value' => '1']);
        WebsiteSetting::updateOrCreate(['key' => 'announcement_text_1'], ['value' => 'বিশেষ ঘোষণা টেক্সট']);

        $this->get('/')
            ->assertOk()
            ->assertSee('বিশেষ ঘোষণা টেক্সট', false)
            ->assertSee('ms-announcement', false);
    }

    public function test_disabled_announcement_hidden_on_home(): void
    {
        WebsiteSetting::updateOrCreate(['key' => 'announcement_enabled'], ['value' => '0']);
        WebsiteSetting::updateOrCreate(['key' => 'announcement_text_1'], ['value' => 'গোপন টেক্সট']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('গোপন টেক্সট', false)
            ->assertDontSee('ms-announcement', false);
    }

    public function test_missing_and_empty_messages_hide_the_entire_bar(): void
    {
        $this->get('/')->assertOk()->assertDontSee('ms-announcement', false);
        WebsiteSetting::updateOrCreate(['key'=>'announcement_enabled'], ['value'=>'1']);
        WebsiteSetting::updateOrCreate(['key'=>'announcement_text_1'], ['value'=>'   ']);
        WebsiteSetting::updateOrCreate(['key'=>'announcement_text_2'], ['value'=>'Legacy message']);
        $this->get('/')->assertOk()->assertDontSee('ms-announcement', false);
    }

    public function test_admin_updates_refresh_cached_settings_and_storefront_without_manual_clear(): void
    {
        $this->actingAs($this->admin());
        foreach (['/products', 'https://example.com/shop', ''] as $url) {
            WebsiteSetting::allKeyed();
            $message = trim('Updated announcement '.$url);
            $this->post(route('admin.website-settings.update'), $this->payload([
                'announcement_enabled'=>'1', 'announcement_text_1'=>$message,
                'announcement_link_url'=>$url, 'announcement_link_label'=>'Details',
            ]))->assertSessionHasNoErrors();
            $this->assertSame($message, WebsiteSetting::get('announcement_text_1'));
            $response = $this->get('/')->assertOk()->assertSee($message);
            if ($url !== '') {
                $response->assertSee('href="'.$url.'"', false)->assertSee('Details');
            }
        }
        $this->post(route('admin.website-settings.update'), $this->payload())->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('ms-announcement', false);
    }

    public function test_unsafe_urls_are_rejected_and_legacy_values_render_as_plain_text(): void
    {
        $this->actingAs($this->admin());
        foreach (['javascript:alert(1)', 'data:text/html,test', '//example.com', '/\\example.com', "https://example.com/\nfoo", 'ftp://example.com'] as $url) {
            $this->post(route('admin.website-settings.update'), $this->payload(['announcement_link_url'=>$url]))
                ->assertSessionHasErrors('announcement_link_url');
            $html = view('partials.storefront.announcement', ['ws'=>[
                'announcement_enabled'=>'1', 'announcement_text_1'=>'<script>alert(1)</script>',
                'announcement_link_url'=>$url,
            ]])->render();
            $this->assertStringNotContainsString('href=', $html);
            $this->assertStringContainsString('&lt;script&gt;', $html);
        }
    }

    public function test_url_without_label_links_the_message(): void
    {
        $html = view('partials.storefront.announcement', ['ws'=>[
            'announcement_enabled'=>'1', 'announcement_text_1'=>'Read this', 'announcement_link_url'=>'/delivery',
        ]])->render();
        $this->assertStringContainsString('href="/delivery">Read this</a>', $html);
    }
}
