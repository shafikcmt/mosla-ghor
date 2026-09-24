<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorProductApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::factory()->create(['role' => 'vendor']);
        $this->vendor = Vendor::create(['user_id' => $user->id, 'shop_name' => 'QA shop', 'slug' => 'qa-shop', 'owner_name' => 'Owner', 'phone' => '01700000000', 'email' => 'qa@example.com', 'status' => 'approved', 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('secret123'), 'role' => 'admin', 'is_admin' => true]);
    }

    private function product(string $slug, ?string $status = 'pending', bool $vendor = true): Product
    {
        return Product::create(['name_bn' => $slug, 'slug' => $slug, 'retail_price_1kg' => 500, 'vendor_id' => $vendor ? $this->vendor->id : null, 'approval_status' => $status]);
    }

    public function test_index_lists_vendor_products_with_stats_and_filter(): void
    {
        $this->product('pending-one');
        $this->product('legacy-null', null);
        $this->product('approved-one', 'approved');
        $this->product('rejected-one', 'rejected');
        $this->product('admin-own', null, false);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.vendor-products.index'))
            ->assertOk()
            ->assertViewHas('stats', ['pending' => 2, 'approved' => 1, 'rejected' => 1, 'total' => 4])
            ->assertSee('pending-one')->assertSee('QA shop')->assertDontSee('admin-own');

        $this->actingAs($admin)->get(route('admin.vendor-products.index', ['status' => 'pending']))
            ->assertOk()->assertSee('legacy-null')->assertDontSee('approved-one');
    }

    public function test_approve_reject_and_bulk_approve(): void
    {
        $a = $this->product('a');
        $b = $this->product('b');
        $c = $this->product('c');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.vendor-products.reject', $a), ['reason' => 'ছবি অস্পষ্ট'])->assertRedirect();
        $this->assertSame('rejected', $a->fresh()->approval_status);
        $this->assertSame('ছবি অস্পষ্ট', $a->fresh()->rejection_reason);

        $this->actingAs($admin)->post(route('admin.vendor-products.reject', $b), ['reason' => str_repeat('x', 201)])->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('admin.vendor-products.approve', $a))->assertRedirect();
        $this->assertSame('approved', $a->fresh()->approval_status);
        $this->assertNull($a->fresh()->rejection_reason);

        $this->actingAs($admin)->post(route('admin.vendor-products.bulk-approve'), ['product_ids' => [$b->id, $c->id]])
            ->assertSessionHasNoErrors()->assertSessionHas('success', '2টি পণ্য অনুমোদিত হয়েছে।');
        $this->assertSame('approved', $c->fresh()->approval_status);
    }

    public function test_non_vendor_products_are_not_touched(): void
    {
        $own = $this->product('own', null, false);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.vendor-products.approve', $own))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.vendor-products.bulk-approve'), ['product_ids' => [$own->id]]);
        $this->assertNull($own->fresh()->approval_status);
    }

    public function test_guests_cannot_access(): void
    {
        $this->get(route('admin.vendor-products.index'))->assertRedirect();
    }
}
