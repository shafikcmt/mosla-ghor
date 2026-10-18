<?php

namespace Tests\Feature;

use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataTransaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class KhataStaffTest extends TestCase
{
    private User $owner;
    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->owner = User::factory()->create(['role' => 'vendor', 'phone' => '01700000001']);
        $this->vendor = Vendor::create(['user_id' => $this->owner->id, 'shop_name' => 'নাফি মসলা মার্ট', 'slug' => 'nafi', 'owner_name' => 'Owner',
            'phone' => '01700000001', 'email' => 'nafi@example.com', 'status' => 'approved', 'is_active' => true, 'panel_mode' => 'inventory']);
    }

    private function addStaff(array $permissions): User
    {
        $this->actingAs($this->owner)->post(route('vendor.khata.staff.store'), [
            'name' => 'করিম', 'phone' => '+880 1811-223344', 'password' => 'secret123', 'permissions' => $permissions,
        ])->assertSessionHasNoErrors()->assertRedirect(route('vendor.khata.staff.index'));
        Auth::logout();

        return User::where('phone', '01811223344')->sole();
    }

    private function item(): KhataItem
    {
        $this->actingAs($this->owner)->post(route('vendor.khata.items.store'), [
            'name' => 'জিরা', 'unit' => 'kg', 'sale_price' => 600, 'purchase_price' => 500, 'opening_stock' => 20,
        ])->assertSessionHasNoErrors();
        Auth::logout();

        return KhataItem::sole();
    }

    public function test_owner_adds_staff_who_logs_in_with_phone_and_lands_in_the_khata(): void
    {
        $this->actingAs($this->owner)->get(route('vendor.khata.staff.create'))->assertOk()->assertSee('কোন কাজগুলো করতে পারবে?');
        $this->get(route('vendor.khata.settings'))->assertOk()->assertSee(route('vendor.khata.staff.index'));
        Auth::logout();
        $staff = $this->addStaff(['sale']);
        $this->assertSame($this->vendor->id, $staff->vendor->id);
        $this->actingAs($this->owner)->get(route('vendor.khata.staff.edit', $staff))->assertOk()->assertSee('01811223344');
        Auth::logout();
        $this->assertTrue($staff->isStaff());

        $this->post(route('vendor.login.post'), ['identifier' => '01811223344', 'password' => 'secret123'])->assertRedirect();
        $this->assertAuthenticatedAs($staff);
        $this->get(route('vendor.dashboard'))->assertRedirect(route('vendor.khata.home'));
        $this->get(route('vendor.products.index'))->assertRedirect(route('vendor.khata.home'));
    }

    public function test_staff_can_only_do_allowed_tasks(): void
    {
        $item = $this->item();
        $staff = $this->addStaff(['sale']);
        $this->actingAs($staff);

        $home = $this->get(route('vendor.khata.home'))->assertOk();
        $home->assertSee('করিম')->assertDontSee('পাওনা <span', false);

        $this->get(route('vendor.khata.trade.create', 'sale'))->assertOk();
        $this->post(route('vendor.khata.trade.store', 'sale'), [
            'date' => now()->toDateString(), 'payment_mode' => 'cash', 'paid' => 600,
            'lines' => [['item_id' => $item->id, 'quantity' => 1, 'price' => 600]],
        ])->assertSessionHasNoErrors();
        $sale = KhataTransaction::where('type', 'sale')->sole();
        $this->assertSame($staff->id, $sale->created_by);
        $this->get(route('vendor.khata.tx.show', $sale))->assertOk(); // own entry
        $this->get(route('vendor.khata.tx.edit', $sale))->assertOk();     // and may fix it
        $this->put(route('vendor.khata.tx.update', $sale), [
            'date' => now()->toDateString(), 'payment_mode' => 'cash', 'paid' => 1200,
            'lines' => [['item_id' => $item->id, 'quantity' => 2, 'price' => 600]],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(18, $item->fresh()->stock);

        foreach ([route('vendor.khata.trade.create', 'purchase'), route('vendor.khata.amount.create', 'expense'),
            route('vendor.khata.report'), route('vendor.khata.transactions'), route('vendor.khata.parties.index'),
            route('vendor.khata.settings'), route('vendor.khata.staff.index')] as $url) {
            $this->get($url)->assertRedirect(route('vendor.khata.home'));
        }
        $this->post(route('vendor.khata.trade.store', 'purchase'), [])->assertForbidden();
        $this->delete(route('vendor.khata.tx.destroy', $sale))->assertForbidden();
        $this->assertSame(1, KhataTransaction::where('type', 'sale')->count());

        // An entry written by the owner is not editable by staff without ledger access.
        Auth::logout();
        $this->actingAs($this->owner)->post(route('vendor.khata.trade.store', 'sale'), [
            'date' => now()->toDateString(), 'payment_mode' => 'cash', 'paid' => 600,
            'lines' => [['item_id' => $item->id, 'quantity' => 1, 'price' => 600]],
        ])->assertSessionHasNoErrors();
        $ownerSale = KhataTransaction::where('created_by', $this->owner->id)->where('type', 'sale')->sole();
        $this->actingAs($staff)->get(route('vendor.khata.tx.edit', $ownerSale))->assertForbidden();

        // The owner sees who wrote it.
        $this->actingAs($this->owner)->get(route('vendor.khata.tx.show', $sale))->assertOk()->assertSee('লিখেছেন');
    }

    public function test_owner_changes_permissions_and_disabling_signs_staff_out(): void
    {
        $staff = $this->addStaff([]);
        $this->actingAs($this->owner)->put(route('vendor.khata.staff.update', $staff), [
            'name' => 'করিম', 'phone' => '01811223344', 'permissions' => ['reports', 'ledger'], 'staff_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->actingAs($staff->fresh())->get(route('vendor.khata.report'))->assertOk();

        $this->actingAs($this->owner)->put(route('vendor.khata.staff.update', $staff), [
            'name' => 'করিম', 'phone' => '01811223344', 'permissions' => ['reports'], 'staff_active' => 0,
        ])->assertSessionHasNoErrors();
        $this->actingAs($staff->fresh())->get(route('vendor.khata.home'))->assertRedirect(route('vendor.login'));
        $this->assertGuest();
    }

    public function test_staff_are_scoped_to_their_shop_and_removal_keeps_history(): void
    {
        $staff = $this->addStaff(['sale']);
        $otherOwner = User::factory()->create(['role' => 'vendor', 'phone' => '01900000009']);
        Vendor::create(['user_id' => $otherOwner->id, 'shop_name' => 'Other', 'slug' => 'other', 'owner_name' => 'O',
            'phone' => '01900000009', 'email' => 'o@example.com', 'status' => 'approved', 'is_active' => true, 'panel_mode' => 'inventory']);
        $this->actingAs($otherOwner)->get(route('vendor.khata.staff.edit', $staff))->assertNotFound();

        $this->actingAs($this->owner)->post(route('vendor.khata.staff.store'), [
            'name' => 'Dup', 'phone' => '01811223344', 'password' => 'secret123',
        ])->assertSessionHasErrors('phone');

        $this->delete(route('vendor.khata.staff.destroy', $staff))->assertRedirect(route('vendor.khata.staff.index'));
        $staff->refresh();
        $this->assertFalse($staff->staff_active);
        $this->assertNull($staff->phone);
        $this->get(route('vendor.khata.staff.index'))->assertOk()->assertDontSee('করিম');
    }
}
