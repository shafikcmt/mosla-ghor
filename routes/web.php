<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
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
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\ProductReviewController as AdminProductReviewController;
use App\Http\Controllers\TrackOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Vendor\AuthController as VendorAuthController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\ComboController as VendorComboController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PayoutController as VendorPayoutController;
use App\Http\Controllers\Vendor\PickupPointController as VendorPickupPointController;
use App\Http\Controllers\Vendor\ProfileController as VendorProfileController;
use App\Http\Controllers\Vendor\StockController as VendorStockController;
use App\Http\Controllers\Vendor\CustomerController as VendorCustomerController;
use App\Http\Controllers\Vendor\PosOrderController as VendorPosOrderController;
use App\Http\Controllers\Vendor\NotificationController as VendorNotificationController;
use App\Http\Controllers\Admin\ReturnRequestController as AdminReturnRequestController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\VendorPayoutController as AdminVendorPayoutController;
use App\Http\Controllers\Admin\VendorPickupPointController as AdminVendorPickupPointController;
use App\Http\Controllers\Admin\VendorParcelController as AdminVendorParcelController;
use App\Http\Controllers\Admin\VendorStockController as AdminVendorStockController;
use App\Http\Controllers\Admin\VendorCustomerController as AdminVendorCustomerController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\VendorShopController;
use App\Http\Controllers\Admin\WholesaleEnquiryController as AdminWholesaleEnquiryController;
use App\Http\Controllers\Admin\WholesaleQuoteController as AdminWholesaleQuoteController;
use App\Http\Controllers\Admin\WholesaleChatController as AdminWholesaleChatController;
use App\Http\Controllers\Admin\WholesaleCommissionController as AdminWholesaleCommissionController;
use App\Http\Controllers\Admin\VendorWalletController as AdminVendorWalletController;
use App\Http\Controllers\Vendor\WholesaleEnquiryController as VendorWholesaleEnquiryController;
use App\Http\Controllers\Vendor\WholesaleQuoteController as VendorWholesaleQuoteController;
use App\Http\Controllers\Vendor\WholesaleChatController as VendorWholesaleChatController;
use App\Http\Controllers\Vendor\WholesaleEarningsController as VendorWholesaleEarningsController;
use App\Http\Controllers\Customer\WholesaleProductController as CustomerWholesaleProductController;
use App\Http\Controllers\Customer\WholesaleEnquiryController as CustomerWholesaleEnquiryController;
use App\Http\Controllers\Customer\WholesaleQuoteController as CustomerWholesaleQuoteController;
use App\Http\Controllers\Customer\WholesaleChatController as CustomerWholesaleChatController;
use App\Http\Controllers\Customer\PaykariComboEnquiryController as CustomerPaykariComboEnquiryController;
use App\Http\Controllers\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Admin\PaykariComboEnquiryController as AdminPaykariComboEnquiryController;
use App\Http\Controllers\Vendor\PaykariComboEnquiryController as VendorPaykariComboEnquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

// ── Public product detail (SEO-friendly) ───────────────────────────────────
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product:slug}/reviews', [ProductController::class, 'storeReview'])->name('products.reviews.store');

// Public wholesale enquiry (guest or logged-in). Rate-limited for abuse safety.
Route::post('/products/{product:slug}/enquiry', [ProductController::class, 'storeEnquiry'])
    ->middleware('throttle:10,1')
    ->name('products.enquiry.store');

// Public Paykari combo (bulk) enquiry — guest or logged-in.
Route::post('/paykari-combo/enquiry', [\App\Http\Controllers\Customer\PaykariComboEnquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('paykari-combo.enquiry.store');

// Public wholesale enquiry bag (manage multiple products + submit). Guest allowed.
Route::get('/wholesale/enquiry-bag', [ProductController::class, 'enquiryBag'])->name('wholesale.enquiry-bag');

// Combo / box builder lives in the home-page section; this is a shareable entry point.
Route::get('/combo', fn () => redirect('/#combo-builder'))->name('combo');

// ── Customer / User auth ───────────────────────────────────────────────────
Route::name('customer.')->group(function () {
    Route::get('register',  [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [CustomerAuthController::class, 'register'])->name('register.post');
    Route::get('login',     [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('login',    [CustomerAuthController::class, 'login'])->name('login.post');

    // ── Passwordless OTP login ─────────────────────────────────────────────
    Route::get('login/otp',         [CustomerAuthController::class, 'showOtpRequest'])->name('login.otp');
    Route::post('login/otp',        [CustomerAuthController::class, 'sendOtp'])->name('login.otp.send');
    Route::get('login/otp/verify',  [CustomerAuthController::class, 'showOtpVerify'])->name('login.otp.verify');
    Route::post('login/otp/verify', [CustomerAuthController::class, 'verifyOtp'])->name('login.otp.verify.post');
    Route::post('login/otp/resend', [CustomerAuthController::class, 'resendOtp'])->name('login.otp.resend');

    Route::post('logout',   [CustomerAuthController::class, 'logout'])->name('logout');

    // ── Wholesale product detail (PUBLIC — no login, stays on this URL) ─────
    Route::get('wholesale/products/{product:slug}', [ProductController::class, 'showWholesale'])
        ->name('wholesale.products.show');

    // ── Authenticated account section ──────────────────────────────────────
    Route::prefix('account')->middleware('customer-auth')->group(function () {
        Route::get('/',                                    [CustomerAccountController::class, 'dashboard'])->name('account');
        Route::get('/orders',                             [CustomerAccountController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{id}',                        [CustomerAccountController::class, 'orderShow'])->name('orders.show');
        Route::post('/orders/{id}/cancel',                [CustomerAccountController::class, 'cancelOrder'])->name('orders.cancel');

        Route::get('/profile',                            [CustomerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile',                            [CustomerProfileController::class, 'update'])->name('profile.update');

        Route::get('/notifications',                      [CustomerNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{id}/read',            [CustomerNotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications/read-all',            [CustomerNotificationController::class, 'readAll'])->name('notifications.readAll');

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
            Route::post('quotes/{quote}/confirm',             [CustomerWholesaleQuoteController::class, 'confirmOrder'])->name('quote.confirm');
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

// ── Meesho-style multi-step checkout (Cart → Review → Payment). Guests allowed. ──
Route::post('/checkout/start',                  [CheckoutController::class, 'start'])->name('checkout.start');
Route::get('/checkout/review',                  [CheckoutController::class, 'review'])->name('checkout.review');
Route::post('/checkout/address',                [CheckoutController::class, 'storeAddress'])->name('checkout.address.store');
Route::post('/checkout/select-address/{address}', [CheckoutController::class, 'selectAddress'])->name('checkout.address.select');
Route::get('/checkout/payment',                 [CheckoutController::class, 'payment'])->name('checkout.payment');
// Privacy-safe "is this phone already registered?" check for guest checkout (returns only a boolean).
Route::post('/checkout/check-phone',            [CheckoutController::class, 'checkPhone'])->name('checkout.check-phone');

Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/success/{orderNumber}', [OrderController::class, 'success'])->name('order.success');
// Optional: turn a just-placed guest order into a customer account by setting a password.
Route::post('/order/{orderNumber}/create-account', [OrderController::class, 'createAccount'])->name('order.create-account');

// ── Public token-addressed invoice (vendor POS orders) ──────────────────────
Route::get('/invoice/{token}',          [InvoiceController::class, 'show'])->name('invoice.show');
Route::get('/invoice/{token}/reorder',  [InvoiceController::class, 'reorder'])->name('invoice.reorder');
Route::post('/invoice/{token}/reorder', [InvoiceController::class, 'reorderStore'])->name('invoice.reorder.store');
Route::get('/invoice/{token}/pay',      [InvoiceController::class, 'pay'])->name('invoice.pay');
Route::post('/invoice/{token}/pay',     [InvoiceController::class, 'payStore'])->name('invoice.pay.store');

// ── Vendor public routes ───────────────────────────────────────────────────
Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::get('register',  [VendorAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [VendorAuthController::class, 'register'])->name('register.post');
    Route::get('login',     [VendorAuthController::class, 'showLogin'])->name('login');
    Route::post('login',    [VendorAuthController::class, 'login'])->name('login.post');

    // ── Passwordless OTP login ─────────────────────────────────────────────
    Route::get('login/otp',         [VendorAuthController::class, 'showOtpRequest'])->name('login.otp');
    Route::post('login/otp',        [VendorAuthController::class, 'sendOtp'])->name('login.otp.send');
    Route::get('login/otp/verify',  [VendorAuthController::class, 'showOtpVerify'])->name('login.otp.verify');
    Route::post('login/otp/verify', [VendorAuthController::class, 'verifyOtp'])->name('login.otp.verify.post');
    Route::post('login/otp/resend', [VendorAuthController::class, 'resendOtp'])->name('login.otp.resend');

    Route::post('logout',   [VendorAuthController::class, 'logout'])->name('logout');
});

// ── Vendor authenticated routes ────────────────────────────────────────────
Route::prefix('vendor')->name('vendor.')->middleware('vendor')->group(function () {
    Route::get('dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', VendorProductController::class)->except(['show']);

    // ── Stock management ───────────────────────────────────────────────────
    Route::get('stock',          [VendorStockController::class, 'index'])->name('stock.index');
    Route::get('stock/history',  [VendorStockController::class, 'history'])->name('stock.history');
    Route::post('stock/adjust',  [VendorStockController::class, 'adjust'])->name('stock.adjust');

    // ── Local customers ────────────────────────────────────────────────────
    Route::resource('customers', VendorCustomerController::class)
        ->parameters(['customers' => 'customer'])
        ->except(['show']);

    // ── POS / vendor-created orders ────────────────────────────────────────
    Route::get('pos',         [VendorPosOrderController::class, 'index'])->name('pos.index');
    Route::get('pos/create',  [VendorPosOrderController::class, 'create'])->name('pos.create');
    Route::post('pos',        [VendorPosOrderController::class, 'store'])->name('pos.store');
    Route::get('pos/{order}', [VendorPosOrderController::class, 'show'])->name('pos.show');
    Route::post('pos/{order}/whatsapp',         [VendorPosOrderController::class, 'whatsapp'])->name('pos.whatsapp');
    Route::post('pos/{order}/invoice-toggle',   [VendorPosOrderController::class, 'invoiceToggle'])->name('pos.invoice-toggle');
    Route::post('pos/{order}/collect-payment',  [VendorPosOrderController::class, 'collectPayment'])->name('pos.collect-payment');

    // ── Notifications ──────────────────────────────────────────────────────
    Route::get('notifications',           [VendorNotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}/read', [VendorNotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [VendorNotificationController::class, 'readAll'])->name('notifications.readAll');

    Route::resource('combos', VendorComboController::class)->except(['show']);
    Route::post('combos/{combo}/toggle', [VendorComboController::class, 'toggle'])->name('combos.toggle');

    Route::get('orders',                          [VendorOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{vendorOrder}',            [VendorOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{vendorOrder}/fulfillment', [VendorOrderController::class, 'updateFulfillment'])->name('orders.fulfillment');
    Route::post('orders/{vendorOrder}/parcel',       [VendorOrderController::class, 'parcel'])->name('orders.parcel');

    Route::get('payouts',  [VendorPayoutController::class, 'index'])->name('payouts.index');
    Route::post('payouts', [VendorPayoutController::class, 'store'])->name('payouts.store');

    // ── Vendor pickup points ───────────────────────────────────────────────
    Route::post('pickup-points/{pickupPoint}/default', [VendorPickupPointController::class, 'setDefault'])->name('pickup-points.default');
    Route::resource('pickup-points', VendorPickupPointController::class)
        ->parameters(['pickup-points' => 'pickupPoint'])
        ->except(['show']);

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

    Route::resource('categories', AdminCategoryController::class);

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

    // Admin control over a vendor order's parcel
    Route::post('vendor-orders/{vendorOrder}/parcel', [AdminVendorParcelController::class, 'store'])->name('vendor-orders.parcel.store');
    Route::put('vendor-orders/{vendorOrder}/parcel',  [AdminVendorParcelController::class, 'update'])->name('vendor-orders.parcel.update');
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
    Route::post('courier-api-settings/{courier}/diagnose', [AdminCourierApiSettingController::class, 'diagnose'])->name('courier-api-settings.diagnose');

    Route::get('courier-orders', [AdminCourierOrderController::class, 'index'])->name('courier-orders.index');

    // ── Vendor stock (admin oversight) ─────────────────────────────────────
    Route::get('vendor-stock',         [AdminVendorStockController::class, 'index'])->name('vendor-stock.index');
    Route::post('vendor-stock/adjust', [AdminVendorStockController::class, 'adjust'])->name('vendor-stock.adjust');

    // ── Vendor local customers (admin oversight) ───────────────────────────
    Route::get('vendor-customers',                          [AdminVendorCustomerController::class, 'index'])->name('vendor-customers.index');
    Route::get('vendor-customers/{vendorCustomer}',         [AdminVendorCustomerController::class, 'show'])->name('vendor-customers.show');
    Route::post('vendor-customers/{vendorCustomer}/toggle', [AdminVendorCustomerController::class, 'toggleStatus'])->name('vendor-customers.toggle');
    Route::post('vendor-orders/{order}/settle',            [AdminVendorCustomerController::class, 'settleOrder'])->name('vendor-orders.settle');

    // ── Notifications ──────────────────────────────────────────────────────
    Route::get('notifications',           [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}/read', [AdminNotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('notifications.readAll');

    // ── Vendor pickup points (admin oversight) ─────────────────────────────
    Route::post('vendor-pickup-points/{vendorPickupPoint}/default', [AdminVendorPickupPointController::class, 'setDefault'])->name('vendor-pickup-points.default');
    Route::resource('vendor-pickup-points', AdminVendorPickupPointController::class)
        ->parameters(['vendor-pickup-points' => 'vendorPickupPoint'])
        ->except(['show']);

    Route::resource('faqs', AdminFaqController::class);
    Route::post('faqs/{faq}/toggle', [AdminFaqController::class, 'toggle'])->name('faqs.toggle');

    Route::resource('reviews', AdminReviewController::class);
    Route::post('reviews/{review}/toggle', [AdminReviewController::class, 'toggle'])->name('reviews.toggle');

    // ── Product reviews (customer reviews on product detail pages) ──────────
    Route::get('product-reviews',                          [AdminProductReviewController::class, 'index'])->name('product-reviews.index');
    Route::post('product-reviews/{productReview}/approve', [AdminProductReviewController::class, 'approve'])->name('product-reviews.approve');
    Route::post('product-reviews/{productReview}/pending', [AdminProductReviewController::class, 'pending'])->name('product-reviews.pending');
    Route::delete('product-reviews/{productReview}',       [AdminProductReviewController::class, 'destroy'])->name('product-reviews.destroy');

    // ── Vendor management ──────────────────────────────────────────────────
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/',                    [AdminVendorController::class, 'index'])->name('index');
        Route::get('/settings',            [AdminVendorController::class, 'settings'])->name('settings');
        Route::post('/settings',           [AdminVendorController::class, 'saveSettings'])->name('save-settings');
        Route::get('/create',              [AdminVendorController::class, 'create'])->name('create');
        Route::post('/',                   [AdminVendorController::class, 'store'])->name('store');
        Route::get('/{vendor}',            [AdminVendorController::class, 'show'])->name('show');
        Route::get('/{vendor}/edit',       [AdminVendorController::class, 'edit'])->name('edit');
        Route::put('/{vendor}',            [AdminVendorController::class, 'update'])->name('update');
        Route::post('/{vendor}/approve',   [AdminVendorController::class, 'approve'])->name('approve');
        Route::post('/{vendor}/reject',    [AdminVendorController::class, 'reject'])->name('reject');
        Route::post('/{vendor}/suspend',   [AdminVendorController::class, 'suspend'])->name('suspend');
        Route::post('/{vendor}/reactivate',[AdminVendorController::class, 'reactivate'])->name('reactivate');
        Route::post('/{vendor}/reset-password', [AdminVendorController::class, 'resetPassword'])->name('reset-password');
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
        Route::post('enquiries/{enquiry}/assign',  [AdminWholesaleEnquiryController::class, 'assign'])->name('enquiry.assign');

        Route::get('enquiries/{enquiry}/quote',    [AdminWholesaleQuoteController::class, 'create'])->name('quote.create');
        Route::post('enquiries/{enquiry}/quote',   [AdminWholesaleQuoteController::class, 'store'])->name('quote.store');
        Route::get('quotes',                      [AdminWholesaleQuoteController::class, 'index'])->name('quote.index');
        Route::get('quotes/{quote}',               [AdminWholesaleQuoteController::class, 'show'])->name('quote.show');

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
