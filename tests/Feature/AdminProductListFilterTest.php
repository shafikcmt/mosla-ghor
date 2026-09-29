<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Tests\TestCase;

class AdminProductListFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_admin' => true]));
    }

    private function vendor(string $shop): Vendor
    {
        $user = User::factory()->create(['role' => 'vendor']);

        return Vendor::create(['user_id' => $user->id, 'shop_name' => $shop, 'slug' => \Str::slug($shop) ?: uniqid(),
            'owner_name' => 'Owner '.$shop, 'email' => uniqid().'@example.test', 'phone' => '017'.random_int(10000000, 99999999), 'status' => 'approved']);
    }

    private function product(string $slug, array $attrs = []): Product
    {
        return Product::create(array_replace(['name_bn' => 'পণ্য '.$slug, 'name_en' => ucfirst($slug), 'slug' => $slug,
            'stock' => 5, 'retail_price_1kg' => 100, 'is_active' => true, 'show_in_retail' => true,
            'show_in_wholesale' => false], $attrs));
    }

    private function list(array $query = [])
    {
        return $this->get(route('admin.products.index', $query))->assertOk();
    }

    public function test_ownership_filter_separates_platform_and_vendor_products(): void
    {
        $vendor = $this->vendor('Spice Shop');
        $this->product('platform-jira');
        $this->product('vendor-elach', ['vendor_id' => $vendor->id, 'approval_status' => 'pending']);

        $this->list()->assertSee('platform-jira')->assertSee('vendor-elach')->assertSee('ভেন্ডর: Spice Shop');
        $this->list(['owner' => 'platform'])->assertSee('platform-jira')->assertDontSee('vendor-elach');
        $this->list(['owner' => 'vendor'])->assertSee('vendor-elach')->assertDontSee('platform-jira');
        $this->list(['owner' => 'bogus'])->assertSee('platform-jira')->assertSee('vendor-elach');
    }

    public function test_search_matches_name_slug_sku_brand_category_and_vendor(): void
    {
        $vendor = $this->vendor('Rahim Traders');
        $cat = Category::create(['name_bn' => 'গরম মসলা', 'name_en' => 'Garam Masala', 'slug' => 'garam', 'is_active' => true]);
        $this->product('p-name', ['name_en' => 'Cinnamon Stick']);
        $this->product('p-sku', ['sku' => 'SKU-777']);
        $this->product('p-brand', ['brand' => 'Radhuni']);
        $this->product('p-cat', ['category_id' => $cat->id]);
        $this->product('p-vendor', ['vendor_id' => $vendor->id]);
        $this->product('p-other');

        $cases = ['Cinnamon' => 'p-name', 'p-sku' => 'p-sku', 'SKU-777' => 'p-sku', 'Radhuni' => 'p-brand',
            'Garam' => 'p-cat', 'Rahim' => 'p-vendor'];
        foreach ($cases as $term => $slug) {
            $response = $this->list(['search' => $term])->assertSee($slug);
            $this->assertStringNotContainsString('p-other', $response->getContent(), "Search '{$term}' leaked p-other");
        }
        // LIKE wildcards are matched literally.
        $this->list(['search' => '%'])->assertDontSee('p-other')->assertSee('খোঁজ বা ফিল্টারের সাথে কোনো পণ্য মেলেনি');
        $this->product('under_score');
        $this->product('underxscore');
        $this->list(['search' => 'r_s'])->assertSee('under_score')->assertDontSee('underxscore');
    }

    public function test_filters_combine_with_search(): void
    {
        $vendor = $this->vendor('Combo Vendor');
        $parent = Category::create(['name_bn' => 'মসলা', 'slug' => 'spice', 'is_active' => true]);
        $child = Category::create(['name_bn' => 'জিরা', 'slug' => 'jira', 'parent_id' => $parent->id, 'is_active' => true]);
        $this->product('match-me', ['vendor_id' => $vendor->id, 'category_id' => $child->id, 'is_active' => false,
            'show_in_retail' => false, 'show_in_wholesale' => true, 'brand' => 'Acme']);
        $this->product('wrong-status', ['vendor_id' => $vendor->id, 'category_id' => $child->id, 'is_active' => true,
            'show_in_retail' => false, 'show_in_wholesale' => true, 'brand' => 'Acme']);
        $this->product('wrong-channel', ['vendor_id' => $vendor->id, 'category_id' => $child->id, 'is_active' => false,
            'show_in_retail' => true, 'brand' => 'Acme']);
        $this->product('wrong-owner', ['category_id' => $child->id, 'is_active' => false,
            'show_in_retail' => false, 'show_in_wholesale' => true, 'brand' => 'Acme']);

        $this->list(['search' => 'Acme', 'owner' => 'vendor', 'vendor_id' => $vendor->id, 'status' => 'inactive',
            'channel' => 'wholesale', 'category_id' => $parent->id])
            ->assertSee('match-me')->assertDontSee('wrong-status')->assertDontSee('wrong-channel')->assertDontSee('wrong-owner');

        // Platform owner + a vendor filter can never match.
        $this->list(['owner' => 'platform', 'vendor_id' => $vendor->id])->assertDontSee('match-me')->assertDontSee('wrong-owner');
    }

    public function test_pagination_keeps_filter_query_string(): void
    {
        foreach (range(1, 25) as $i) {
            $this->product('bulk-'.$i, ['brand' => 'Pager']);
        }
        $response = $this->list(['search' => 'Pager', 'status' => 'active']);
        $response->assertSee('search=Pager', false)->assertSee('status=active', false);

        $page2 = $this->list(['search' => 'Pager', 'status' => 'active', 'page' => 2]);
        $this->assertSame(5, substr_count($page2->getContent(), 'data-qe-row'));
        $page2->assertSee('value="Pager"', false)->assertSee('<option value="active" selected', false);
    }

    public function test_thumbnail_uses_image_or_placeholder(): void
    {
        $this->product('with-img', ['main_image' => 'storage/products/abc.webp']);
        $this->product('no-img');

        $html = $this->list()->getContent();
        $this->assertStringContainsString(asset('storage/products/abc.webp'), $html);
        $this->assertStringContainsString('src="'.asset('images/product-placeholder.svg').'"', $html);
        $this->assertStringContainsString('onerror=', $html);
    }

    public function test_delete_returns_to_filtered_list(): void
    {
        $product = $this->product('to-delete');
        $from = route('admin.products.index', ['status' => 'active', 'page' => 1]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->from($from)->delete(route('admin.products.destroy', $product))->assertRedirect($from);
        $this->assertModelMissing($product);
    }
}
