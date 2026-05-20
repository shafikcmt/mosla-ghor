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
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Route::get('/', HomeController::class);
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/address/unions/{upazila}', [AddressController::class, 'unions'])->name('address.unions');

Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/success/{orderNumber}', [OrderController::class, 'success'])->name('order.success');

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
});
