<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use Tests\TestCase;

class ProductSaveAndNewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['name_bn' => 'জিরা', 'slug' => 'cumin', 'stock' => 10, 'retail_price_1kg' => 500,
            'is_active' => 1, 'show_in_retail' => 1, 'show_in_wholesale' => 0], $changes);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    private function vendorUser(): array
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $user->id, 'shop_name' => 'QA shop', 'slug' => 'qa-shop', 'owner_name' => 'Owner',
            'phone' => '01700000000', 'email' => 'qa@example.com', 'status' => 'approved', 'is_active' => true]);
        return [$user, $vendor];
    }

    public function test_admin_store_with_after_save_new_goes_to_create_with_flash(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['after_save' => 'new']))
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHas('pe_saved', fn ($s) => $s['name'] === 'জিরা'
                && $s['edit_url'] === route('admin.products.edit', Product::where('slug', 'cumin')->first()));
        $this->assertSame(1, Product::where('slug', 'cumin')->count());

        // The create page shows the saved notice with an edit link.
        $product = Product::where('slug', 'cumin')->first();
        $this->get(route('admin.products.create'))->assertOk()
            ->assertSee('“জিরা” সংরক্ষিত হয়েছে।')->assertSee(route('admin.products.edit', $product), false);
    }

    public function test_admin_update_with_after_save_new_goes_to_create(): void
    {
        $admin = $this->admin();
        $product = Product::create($this->payload());
        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['name_bn' => 'নতুন নাম', 'after_save' => 'new']))
            ->assertRedirect(route('admin.products.create'))->assertSessionHas('pe_saved.name', 'নতুন নাম');
        $this->assertSame('নতুন নাম', $product->fresh()->name_bn);
    }

    public function test_without_after_save_behaviour_is_unchanged(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload())
            ->assertRedirect(route('admin.products.edit', Product::where('slug', 'cumin')->first()))
            ->assertSessionHas('success', 'পণ্য তৈরি হয়েছে।');

        $product = Product::where('slug', 'cumin')->first();
        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload())
            ->assertRedirect(route('admin.products.edit', $product))->assertSessionHas('success', 'পণ্য আপডেট হয়েছে।');
    }

    public function test_other_after_save_values_are_treated_as_normal_save(): void
    {
        $admin = $this->admin();
        $product = Product::create($this->payload());
        // ('new ' is trimmed to 'new' by Laravel's TrimStrings middleware, so it is not listed here.)
        foreach (['https://evil.example', '/admin/users', 'NEW', 'new-product', ['new']] as $value) {
            $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload(['after_save' => $value]))
                ->assertRedirect(route('admin.products.edit', $product));
        }
    }

    public function test_validation_error_stays_on_the_form(): void
    {
        $this->actingAs($this->admin())->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->payload(['name_bn' => '', 'after_save' => 'new']))
            ->assertRedirect(route('admin.products.create'))->assertSessionHasErrors('name_bn')->assertSessionMissing('pe_saved');
        $this->assertSame(0, Product::count());
    }

    public function test_edit_page_shows_new_product_shortcuts_for_admin(): void
    {
        $admin = $this->admin();
        $product = Product::create($this->payload());
        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('সংরক্ষণ করে নতুন পণ্য যোগ করুন')->assertSee('+ নতুন পণ্য')
            ->assertSee('name="after_save" value="new"', false)->assertDontSee('+ আরেকটি নতুন পণ্য');

        $this->actingAs($admin)->withSession(['success' => 'পণ্য আপডেট হয়েছে।'])
            ->get(route('admin.products.edit', $product))->assertSee('+ আরেকটি নতুন পণ্য');
    }

    public function test_vendor_allowed_goes_to_create(): void
    {
        [$user, $vendor] = $this->vendorUser();
        $product = Product::create($this->payload(['vendor_id' => $vendor->id, 'approval_status' => 'approved']));

        $this->actingAs($user)->get(route('vendor.products.edit', $product))->assertOk()->assertSee('সংরক্ষণ করে নতুন পণ্য যোগ করুন');
        $this->actingAs($user)->put(route('vendor.products.update', $product), $this->payload(['name_bn' => 'ভেন্ডর', 'after_save' => 'new']))
            ->assertRedirect(route('vendor.products.create'))->assertSessionHas('pe_saved.edit_url', route('vendor.products.edit', $product));
        $this->assertSame('ভেন্ডর', $product->fresh()->name_bn);

        $this->actingAs($user)->post(route('vendor.products.store'), $this->payload(['slug' => 'v-new', 'after_save' => 'new']))
            ->assertRedirect(route('vendor.products.create'));
        $this->assertSame($vendor->id, Product::where('slug', 'v-new')->value('vendor_id'));
    }

    public function test_vendor_who_cannot_add_products_is_saved_and_kept_on_edit_page(): void
    {
        [$user, $vendor] = $this->vendorUser();
        $product = Product::create($this->payload(['vendor_id' => $vendor->id, 'approval_status' => 'approved']));
        WebsiteSetting::updateOrCreate(['key' => 'vendor_can_add_product'], ['value' => '0']);

        // Buttons and links hidden.
        $this->actingAs($user)->get(route('vendor.products.edit', $product))->assertOk()
            ->assertDontSee('সংরক্ষণ করে নতুন পণ্য যোগ করুন')->assertDontSee('data-pe-new', false);

        // A forged after_save=new still saves and returns to the edit page with a note — never an error.
        $this->actingAs($user)->put(route('vendor.products.update', $product), $this->payload(['name_bn' => 'সংরক্ষিত', 'after_save' => 'new']))
            ->assertRedirect(route('vendor.products.edit', $product))
            ->assertSessionHas('success', 'পণ্য আপডেট হয়েছে।')
            ->assertSessionHas('pe_note');
        $this->assertSame('সংরক্ষিত', $product->fresh()->name_bn);

        $this->actingAs($user)->withSession(['pe_note' => 'পণ্যটি সংরক্ষিত হয়েছে।'])
            ->get(route('vendor.products.edit', $product))->assertSee('পণ্যটি সংরক্ষিত হয়েছে।');
    }

    public function test_unapproved_vendor_sees_no_new_product_buttons(): void
    {
        [$user, $vendor] = $this->vendorUser();
        $vendor->update(['status' => 'pending']);
        $product = Product::create($this->payload(['vendor_id' => $vendor->id, 'approval_status' => 'pending']));
        $this->actingAs($user)->get(route('vendor.products.edit', $product))->assertOk()
            ->assertDontSee('সংরক্ষণ করে নতুন পণ্য যোগ করুন')->assertDontSee('data-pe-new', false);
    }
}
