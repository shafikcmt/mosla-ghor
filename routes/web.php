<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ComboController as AdminComboController;
use App\Http\Controllers\Admin\CourierController as AdminCourierController;
use App\Http\Controllers\Admin\CourierApiSettingController as AdminCourierApiSettingController;
use App\Http\Controllers\Admin\CourierOrderController as AdminCourierOrderController;
use App\Http\Controllers\Admin\DeliveryRateController as AdminDeliveryRateController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\WebsiteSettingController as AdminWebsiteSettingController;
use App\Http\Controllers\Admin\DeliveryLocationController as AdminDeliveryLocationController;
use App\Http\Controllers\Admin\DeliverySettingController as AdminDeliverySettingController;
use App\Http\Controllers\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Admin\GeneralSettingController as AdminGeneralSettingController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentSettingController as AdminPaymentSettingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Vendor\AuthController as VendorAuthController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\ComboController as VendorComboController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PayoutController as VendorPayoutController;
use App\Http\Controllers\Vendor\ProfileController as VendorProfileController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\VendorPayoutController as AdminVendorPayoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

// ── Customer / User auth ───────────────────────────────────────────────────
Route::name('customer.')->group(function () {
    Route::get('register',  [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [CustomerAuthController::class, 'register'])->name('register.post');
    Route::get('login',     [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('login',    [CustomerAuthController::class, 'login'])->name('login.post');
    Route::post('logout',   [CustomerAuthController::class, 'logout'])->name('logout');
    Route::get('account',   [CustomerAuthController::class, 'account'])->middleware('customer-auth')->name('account');
});

Route::get('/address/unions/{upazila}', [AddressController::class, 'unions'])->name('address.unions');

Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/success/{orderNumber}', [OrderController::class, 'success'])->name('order.success');

// ── Vendor public routes ───────────────────────────────────────────────────
Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::get('register',  [VendorAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [VendorAuthController::class, 'register'])->name('register.post');
    Route::get('login',     [VendorAuthController::class, 'showLogin'])->name('login');
    Route::post('login',    [VendorAuthController::class, 'login'])->name('login.post');
    Route::post('logout',   [VendorAuthController::class, 'logout'])->name('logout');
});

// ── Vendor authenticated routes ────────────────────────────────────────────
Route::prefix('vendor')->name('vendor.')->middleware('vendor')->group(function () {
    Route::get('dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', VendorProductController::class)->except(['show']);

    Route::resource('combos', VendorComboController::class)->except(['show']);
    Route::post('combos/{combo}/toggle', [VendorComboController::class, 'toggle'])->name('combos.toggle');

    Route::get('orders',                          [VendorOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{vendorOrder}',            [VendorOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{vendorOrder}/fulfillment', [VendorOrderController::class, 'updateFulfillment'])->name('orders.fulfillment');

    Route::get('payouts',  [VendorPayoutController::class, 'index'])->name('payouts.index');
    Route::post('payouts', [VendorPayoutController::class, 'store'])->name('payouts.store');

    Route::get('profile',  [VendorProfileController::class, 'index'])->name('profile.index');
    Route::put('profile',  [VendorProfileController::class, 'update'])->name('profile.update');
});

// ── Admin auth (public) ────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/',      fn() => redirect()->route('admin.dashboard'));
    Route::get('login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('website-settings', [AdminWebsiteSettingController::class, 'index'])->name('website-settings.index');
    Route::post('website-settings', [AdminWebsiteSettingController::class, 'update'])->name('website-settings.update');

    Route::resource('products', AdminProductController::class);

    Route::get('delivery-settings', [AdminDeliverySettingController::class, 'index'])->name('delivery-settings.index');
    Route::post('delivery-settings', [AdminDeliverySettingController::class, 'update'])->name('delivery-settings.update');

    Route::prefix('delivery-zones')->name('delivery-zones.')->group(function () {
        Route::get('/',              [AdminDeliveryZoneController::class, 'index'])->name('index');
        Route::get('/create',        [AdminDeliveryZoneController::class, 'create'])->name('create');
        Route::post('/',             [AdminDeliveryZoneController::class, 'store'])->name('store');
        Route::get('/{deliveryZone}',       [AdminDeliveryZoneController::class, 'show'])->name('show');
        Route::get('/{deliveryZone}/edit',  [AdminDeliveryZoneController::class, 'edit'])->name('edit');
        Route::put('/{deliveryZone}',       [AdminDeliveryZoneController::class, 'update'])->name('update');
        Route::delete('/{deliveryZone}',    [AdminDeliveryZoneController::class, 'destroy'])->name('destroy');
        Route::post('/{deliveryZone}/toggle', [AdminDeliveryZoneController::class, 'toggle'])->name('toggle');

        Route::post('/{deliveryZone}/locations',                           [AdminDeliveryLocationController::class, 'store'])->name('locations.store');
        Route::get('/{deliveryZone}/locations/{location}/edit',            [AdminDeliveryLocationController::class, 'edit'])->name('locations.edit');
        Route::put('/{deliveryZone}/locations/{location}',                 [AdminDeliveryLocationController::class, 'update'])->name('locations.update');
        Route::delete('/{deliveryZone}/locations/{location}',              [AdminDeliveryLocationController::class, 'destroy'])->name('locations.destroy');
        Route::post('/{deliveryZone}/locations/{location}/toggle',         [AdminDeliveryLocationController::class, 'toggle'])->name('locations.toggle');
    });

    Route::get('general-settings', [AdminGeneralSettingController::class, 'index'])->name('general-settings.index');
    Route::post('general-settings', [AdminGeneralSettingController::class, 'update'])->name('general-settings.update');

    Route::get('payment-settings', [AdminPaymentSettingController::class, 'index'])->name('payment-settings.index');
    Route::post('payment-settings', [AdminPaymentSettingController::class, 'update'])->name('payment-settings.update');

    Route::resource('combos', AdminComboController::class);
    Route::post('combos/{combo}/toggle', [AdminComboController::class, 'toggle'])->name('combos.toggle');

    Route::get('customers/export', [AdminCustomerController::class, 'export'])->name('customers.export');
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::put('customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');

    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
    Route::post('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('orders/{order}/update-courier', [AdminOrderController::class, 'updateCourier'])->name('orders.updateCourier');
    Route::post('orders/{order}/send-to-courier', [AdminOrderController::class, 'sendToCourier'])->name('orders.sendToCourier');
    Route::post('orders/{order}/mark-delivered', [AdminOrderController::class, 'markDelivered'])->name('orders.markDelivered');
    Route::post('orders/{order}/mark-returned', [AdminOrderController::class, 'markReturned'])->name('orders.markReturned');
    Route::post('orders/{order}/restore-stock', [AdminOrderController::class, 'restoreStock'])->name('orders.restoreStock');

    Route::resource('couriers', AdminCourierController::class)->except(['show']);
    Route::post('couriers/{courier}/toggle', [AdminCourierController::class, 'toggle'])->name('couriers.toggle');

    Route::resource('delivery-rates', AdminDeliveryRateController::class)->except(['show']);

    Route::get('courier-api-settings', [AdminCourierApiSettingController::class, 'index'])->name('courier-api-settings.index');
    Route::put('courier-api-settings/{courier}', [AdminCourierApiSettingController::class, 'update'])->name('courier-api-settings.update');

    Route::get('courier-orders', [AdminCourierOrderController::class, 'index'])->name('courier-orders.index');

    Route::resource('faqs', AdminFaqController::class);
    Route::post('faqs/{faq}/toggle', [AdminFaqController::class, 'toggle'])->name('faqs.toggle');

    Route::resource('reviews', AdminReviewController::class);
    Route::post('reviews/{review}/toggle', [AdminReviewController::class, 'toggle'])->name('reviews.toggle');

    // ── Vendor management ──────────────────────────────────────────────────
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/',                    [AdminVendorController::class, 'index'])->name('index');
        Route::get('/settings',            [AdminVendorController::class, 'settings'])->name('settings');
        Route::post('/settings',           [AdminVendorController::class, 'saveSettings'])->name('save-settings');
        Route::get('/{vendor}',            [AdminVendorController::class, 'show'])->name('show');
        Route::get('/{vendor}/edit',       [AdminVendorController::class, 'edit'])->name('edit');
        Route::put('/{vendor}',            [AdminVendorController::class, 'update'])->name('update');
        Route::post('/{vendor}/approve',   [AdminVendorController::class, 'approve'])->name('approve');
        Route::post('/{vendor}/reject',    [AdminVendorController::class, 'reject'])->name('reject');
        Route::post('/{vendor}/suspend',   [AdminVendorController::class, 'suspend'])->name('suspend');
        Route::post('/{vendor}/reactivate',[AdminVendorController::class, 'reactivate'])->name('reactivate');
    });

    // Admin approve vendor product
    Route::post('vendor-products/{product}/approve', function (\App\Models\Product $product) {
        $product->update(['approval_status' => 'approved']);
        return back()->with('success', 'পণ্য অনুমোদিত হয়েছে।');
    })->name('vendor-products.approve');

    // Vendor payout management
    Route::prefix('vendor-payouts')->name('vendor-payouts.')->group(function () {
        Route::get('/',                            [AdminVendorPayoutController::class, 'index'])->name('index');
        Route::post('/{vendorPayout}/approve',     [AdminVendorPayoutController::class, 'approve'])->name('approve');
        Route::post('/{vendorPayout}/mark-paid',   [AdminVendorPayoutController::class, 'markPaid'])->name('mark-paid');
        Route::post('/{vendorPayout}/reject',      [AdminVendorPayoutController::class, 'reject'])->name('reject');
    });
});
