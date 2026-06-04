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
use App\Http\Controllers\Admin\AuthSettingController as AdminAuthSettingController;
use App\Http\Controllers\Admin\WebsiteSettingController as AdminWebsiteSettingController;
use App\Http\Controllers\Admin\DeliveryLocationController as AdminDeliveryLocationController;
use App\Http\Controllers\Admin\DeliverySettingController as AdminDeliverySettingController;
use App\Http\Controllers\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Admin\GeneralSettingController as AdminGeneralSettingController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentSettingController as AdminPaymentSettingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\CustomerReturnController;
use App\Http\Controllers\CustomerSupportController;
use App\Http\Controllers\CustomerWishlistController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrackOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Vendor\AuthController as VendorAuthController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\ComboController as VendorComboController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PayoutController as VendorPayoutController;
use App\Http\Controllers\Vendor\ProfileController as VendorProfileController;
use App\Http\Controllers\Admin\ReturnRequestController as AdminReturnRequestController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\VendorPayoutController as AdminVendorPayoutController;
use App\Http\Controllers\Admin\WholesaleEnquiryController as AdminWholesaleEnquiryController;
use App\Http\Controllers\Admin\WholesaleQuoteController as AdminWholesaleQuoteController;
use App\Http\Controllers\Admin\WholesaleChatController as AdminWholesaleChatController;
use App\Http\Controllers\Admin\WholesaleCommissionController as AdminWholesaleCommissionController;
use App\Http\Controllers\Admin\VendorWalletController as AdminVendorWalletController;
use App\Http\Controllers\Vendor\WholesaleEnquiryController as VendorWholesaleEnquiryController;
use App\Http\Controllers\Vendor\WholesaleQuoteController as VendorWholesaleQuoteController;
use App\Http\Controllers\Vendor\WholesaleChatController as VendorWholesaleChatController;
use App\Http\Controllers\Vendor\WholesaleEarningsController as VendorWholesaleEarningsController;
use App\Http\Controllers\Customer\WholesaleEnquiryController as CustomerWholesaleEnquiryController;
use App\Http\Controllers\Customer\WholesaleQuoteController as CustomerWholesaleQuoteController;
use App\Http\Controllers\Customer\WholesaleChatController as CustomerWholesaleChatController;
use App\Http\Controllers\Customer\PaykariComboEnquiryController as CustomerPaykariComboEnquiryController;
use App\Http\Controllers\Admin\PaykariComboEnquiryController as AdminPaykariComboEnquiryController;
use App\Http\Controllers\Vendor\PaykariComboEnquiryController as VendorPaykariComboEnquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

// ── Customer / User auth ───────────────────────────────────────────────────
Route::name('customer.')->group(function () {
    Route::get('register',  [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [CustomerAuthController::class, 'register'])->name('register.post');
    Route::get('login',     [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('login',    [CustomerAuthController::class, 'login'])->name('login.post');
    Route::post('logout',   [CustomerAuthController::class, 'logout'])->name('logout');

    // ── Authenticated account section ──────────────────────────────────────
    Route::prefix('account')->middleware('customer-auth')->group(function () {
        Route::get('/',                                    [CustomerAccountController::class, 'dashboard'])->name('account');
        Route::get('/orders',                             [CustomerAccountController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{id}',                        [CustomerAccountController::class, 'orderShow'])->name('orders.show');
        Route::post('/orders/{id}/cancel',                [CustomerAccountController::class, 'cancelOrder'])->name('orders.cancel');

        Route::get('/profile',                            [CustomerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile',                            [CustomerProfileController::class, 'update'])->name('profile.update');

        Route::get('/addresses',                          [CustomerAddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create',                   [CustomerAddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses',                         [CustomerAddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit',           [CustomerAddressController::class, 'edit'])->name('addresses.edit');
        Route::put('/addresses/{address}',                [CustomerAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}',             [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
        Route::post('/addresses/{address}/default',       [CustomerAddressController::class, 'setDefault'])->name('addresses.setDefault');

        Route::get('/returns',                            [CustomerReturnController::class, 'index'])->name('returns.index');
        Route::get('/returns/create/{orderId}',           [CustomerReturnController::class, 'create'])->name('returns.create');
        Route::post('/returns',                           [CustomerReturnController::class, 'store'])->name('returns.store');
        Route::get('/returns/{returnRequest}',            [CustomerReturnController::class, 'show'])->name('returns.show');

        Route::get('/support',                            [CustomerSupportController::class, 'index'])->name('support.index');
        Route::get('/support/create',                     [CustomerSupportController::class, 'create'])->name('support.create');
        Route::post('/support',                           [CustomerSupportController::class, 'store'])->name('support.store');
        Route::get('/support/{supportTicket}',            [CustomerSupportController::class, 'show'])->name('support.show');

        Route::get('/wishlist',                           [CustomerWishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{product}',                [CustomerWishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('/wishlist/{product}',              [CustomerWishlistController::class, 'destroy'])->name('wishlist.destroy');

        // ── Paykari Combo Enquiry ──────────────────────────────────────────────
        Route::prefix('paykari-combo')->name('paykari-combo.')->group(function () {
            Route::get('/',                         [CustomerPaykariComboEnquiryController::class, 'index'])->name('index');
            Route::post('/',                         [CustomerPaykariComboEnquiryController::class, 'store'])->name('store');
            Route::get('/{enquiry}',                 [CustomerPaykariComboEnquiryController::class, 'show'])->name('show');
            Route::patch('/{enquiry}/cancel',        [CustomerPaykariComboEnquiryController::class, 'cancel'])->name('cancel');
            Route::post('/{enquiry}/accept-quote',   [CustomerPaykariComboEnquiryController::class, 'acceptQuote'])->name('accept-quote');
            Route::post('/{enquiry}/decline-quote',  [CustomerPaykariComboEnquiryController::class, 'declineQuote'])->name('decline-quote');
        });

        // ── Wholesale enquiry ──────────────────────────────────────────────
        Route::prefix('wholesale')->name('wholesale.')->group(function () {
            Route::get('enquiries',                           [CustomerWholesaleEnquiryController::class, 'index'])->name('enquiry.index');
            Route::post('enquiries',                          [CustomerWholesaleEnquiryController::class, 'store'])->name('enquiry.store');
            Route::get('enquiries/{enquiry}',                 [CustomerWholesaleEnquiryController::class, 'show'])->name('enquiry.show');
            Route::post('enquiries/{enquiry}/cancel',         [CustomerWholesaleEnquiryController::class, 'cancel'])->name('enquiry.cancel');

            Route::get('quotes',                              [CustomerWholesaleQuoteController::class, 'index'])->name('quote.index');
            Route::get('quotes/{quote}',                      [CustomerWholesaleQuoteController::class, 'show'])->name('quote.show');
            Route::post('quotes/{quote}/accept',              [CustomerWholesaleQuoteController::class, 'accept'])->name('quote.accept');
            Route::post('quotes/{quote}/reject',              [CustomerWholesaleQuoteController::class, 'reject'])->name('quote.reject');

            Route::get('chat/{enquiry}',                      [CustomerWholesaleChatController::class, 'show'])->name('chat.show');
            Route::post('chat/{enquiry}',                     [CustomerWholesaleChatController::class, 'store'])->name('chat.store');
            Route::get('chat/{enquiry}/unread',               [CustomerWholesaleChatController::class, 'unread'])->name('chat.unread');
        });
    });
});

// ── Public order tracking ──────────────────────────────────────────────────
Route::get('/track-order',  [TrackOrderController::class, 'index'])->name('track-order');
Route::post('/track-order', [TrackOrderController::class, 'track'])->name('track-order.submit');

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

    // ── Paykari Combo (Vendor) ─────────────────────────────────────────────
    Route::prefix('paykari-combo')->name('paykari-combo.')->group(function () {
        Route::get('/',                          [VendorPaykariComboEnquiryController::class, 'index'])->name('index');
        Route::get('/{enquiry}',                  [VendorPaykariComboEnquiryController::class, 'show'])->name('show');
        Route::get('/{enquiry}/quote',            [VendorPaykariComboEnquiryController::class, 'createQuote'])->name('quote');
        Route::post('/{enquiry}/quote',           [VendorPaykariComboEnquiryController::class, 'storeQuote'])->name('quote.store');
    });

    // ── Wholesale ──────────────────────────────────────────────────────────
    Route::prefix('wholesale')->name('wholesale.')->group(function () {
        Route::get('enquiries',                            [VendorWholesaleEnquiryController::class, 'index'])->name('enquiry.index');
        Route::get('enquiries/{enquiry}',                  [VendorWholesaleEnquiryController::class, 'show'])->name('enquiry.show');
        Route::post('enquiries/{enquiry}/decline',         [VendorWholesaleEnquiryController::class, 'decline'])->name('enquiry.decline');

        Route::get('enquiries/{enquiry}/quotes/create',    [VendorWholesaleQuoteController::class, 'create'])->name('quote.create');
        Route::post('enquiries/{enquiry}/quotes',          [VendorWholesaleQuoteController::class, 'store'])->name('quote.store');
        Route::get('quotes',                               [VendorWholesaleQuoteController::class, 'index'])->name('quote.index');
        Route::get('quotes/{quote}',                       [VendorWholesaleQuoteController::class, 'show'])->name('quote.show');

        Route::get('chat/{enquiry}',                       [VendorWholesaleChatController::class, 'show'])->name('chat.show');
        Route::post('chat/{enquiry}',                      [VendorWholesaleChatController::class, 'store'])->name('chat.store');
        Route::get('chat/{enquiry}/unread',                [VendorWholesaleChatController::class, 'unread'])->name('chat.unread');

        Route::get('earnings',                             [VendorWholesaleEarningsController::class, 'index'])->name('earnings.index');
    });
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

    Route::get('auth-settings',  [AdminAuthSettingController::class, 'index'])->name('auth-settings.index');
    Route::post('auth-settings', [AdminAuthSettingController::class, 'update'])->name('auth-settings.update');

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
    Route::post('orders/{order}/recalculate-courier', [AdminOrderController::class, 'recalculateCourier'])->name('orders.recalculateCourier');
    Route::post('orders/{order}/send-to-courier', [AdminOrderController::class, 'sendToCourier'])->name('orders.sendToCourier');
    Route::post('orders/{order}/mark-delivered', [AdminOrderController::class, 'markDelivered'])->name('orders.markDelivered');
    Route::post('orders/{order}/mark-returned', [AdminOrderController::class, 'markReturned'])->name('orders.markReturned');
    Route::post('orders/{order}/restore-stock', [AdminOrderController::class, 'restoreStock'])->name('orders.restoreStock');

    Route::resource('couriers', AdminCourierController::class)->except(['show']);
    Route::post('couriers/{courier}/toggle', [AdminCourierController::class, 'toggle'])->name('couriers.toggle');

    Route::resource('delivery-rates', AdminDeliveryRateController::class)->except(['show']);

    Route::get('courier-api-settings', [AdminCourierApiSettingController::class, 'index'])->name('courier-api-settings.index');
    Route::post('courier-api-settings/permissions', [AdminCourierApiSettingController::class, 'saveSettings'])->name('courier-api-settings.permissions');
    Route::put('courier-api-settings/{courier}', [AdminCourierApiSettingController::class, 'update'])->name('courier-api-settings.update');
    Route::post('courier-api-settings/{courier}/test', [AdminCourierApiSettingController::class, 'test'])->name('courier-api-settings.test');

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

    // ── Paykari Combo (Admin) ─────────────────────────────────────────────
    Route::prefix('paykari-combo')->name('paykari-combo.')->group(function () {
        Route::get('/',                             [AdminPaykariComboEnquiryController::class, 'index'])->name('index');
        Route::get('/{enquiry}',                     [AdminPaykariComboEnquiryController::class, 'show'])->name('show');
        Route::patch('/{enquiry}/status',            [AdminPaykariComboEnquiryController::class, 'updateStatus'])->name('status');
        Route::post('/quote/{quote}/approve',        [AdminPaykariComboEnquiryController::class, 'approveQuote'])->name('quote.approve');
        Route::post('/quote/{quote}/reject',         [AdminPaykariComboEnquiryController::class, 'rejectQuote'])->name('quote.reject');
    });

    // ── Wholesale (Admin) ──────────────────────────────────────────────────
    Route::prefix('wholesale')->name('wholesale.')->group(function () {
        Route::get('enquiries',                   [AdminWholesaleEnquiryController::class, 'index'])->name('enquiry.index');
        Route::get('enquiries/{enquiry}',          [AdminWholesaleEnquiryController::class, 'show'])->name('enquiry.show');
        Route::post('enquiries/{enquiry}/status',  [AdminWholesaleEnquiryController::class, 'updateStatus'])->name('enquiry.status');

        Route::get('quotes',                      [AdminWholesaleQuoteController::class, 'index'])->name('quote.index');
        Route::get('quotes/{quote}',               [AdminWholesaleQuoteController::class, 'show'])->name('quote.show');
        Route::post('quotes/{quote}/approve',      [AdminWholesaleQuoteController::class, 'approve'])->name('quote.approve');
        Route::post('quotes/{quote}/reject',       [AdminWholesaleQuoteController::class, 'reject'])->name('quote.reject');

        Route::get('chat',                        [AdminWholesaleChatController::class, 'index'])->name('chat.index');
        Route::get('chat/{enquiry}',               [AdminWholesaleChatController::class, 'show'])->name('chat.show');
        Route::post('chat/{enquiry}',              [AdminWholesaleChatController::class, 'store'])->name('chat.store');
    });

    // ── Commission Settings (Admin) ─────────────────────────────────────────
    Route::prefix('commission')->name('commission.')->group(function () {
        Route::get('settings',                    [AdminWholesaleCommissionController::class, 'index'])->name('settings.index');
        Route::post('settings',                    [AdminWholesaleCommissionController::class, 'store'])->name('settings.store');
        Route::put('settings/{setting}',           [AdminWholesaleCommissionController::class, 'update'])->name('settings.update');
        Route::delete('settings/{setting}',        [AdminWholesaleCommissionController::class, 'destroy'])->name('settings.destroy');
        Route::get('ledger',                      [AdminWholesaleCommissionController::class, 'ledger'])->name('ledger.index');
        Route::post('ledger/{ledger}/settle',      [AdminWholesaleCommissionController::class, 'settle'])->name('ledger.settle');
        Route::post('ledger/bulk-settle',          [AdminWholesaleCommissionController::class, 'bulkSettle'])->name('ledger.bulk-settle');
    });

    // ── Vendor Wallet (Admin) ───────────────────────────────────────────────
    Route::prefix('vendor-wallet')->name('vendor-wallet.')->group(function () {
        Route::get('/',          [AdminVendorWalletController::class, 'index'])->name('index');
        Route::get('/{vendor}',   [AdminVendorWalletController::class, 'show'])->name('show');
    });

    // ── Customer service ───────────────────────────────────────────────────
    Route::get('return-requests',                    [AdminReturnRequestController::class, 'index'])->name('return-requests.index');
    Route::get('return-requests/{returnRequest}',    [AdminReturnRequestController::class, 'show'])->name('return-requests.show');
    Route::put('return-requests/{returnRequest}',    [AdminReturnRequestController::class, 'update'])->name('return-requests.update');

    Route::get('support-tickets',                    [AdminSupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('support-tickets/{supportTicket}',    [AdminSupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('support-tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply'])->name('support-tickets.reply');
});
