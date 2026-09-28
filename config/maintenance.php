<?php

/*
|--------------------------------------------------------------------------
| Admin-controlled maintenance mode (App\Http\Middleware\MaintenanceMode)
|--------------------------------------------------------------------------
| On/off, mode and texts live in WebsiteSetting (admin → ওয়েবসাইট সেটিং).
| This file only decides WHICH paths are affected. Patterns use Request::is()
| syntax (no leading slash, * wildcard).
|
| If this file is missing from a cached config, the middleware falls back to
| MaintenanceMode::DEFAULT_RULES — keep both lists in sync. /admin is always
| allowed regardless of this file.
|
| IMPORTANT: payment gateway callbacks / IPN and courier webhooks are called
| by external servers and MUST be added to `always_allowed` when a gateway or
| webhook is integrated — otherwise payments/status updates fail while the
| site is in maintenance. (None exist today.)
*/

return [

    // Never blocked, in any mode.
    'always_allowed' => [
        'admin', 'admin/*',            // admin panel incl. admin/login
        'up',                          // health check
        'storage/*',                   // product images (also used by admin/vendor pages)
        'build/*', 'css/*', 'js/*', 'images/*', 'icons/*',
        'manifest.json', 'sw.js', 'favicon.ico', 'robots.txt',
        // Payment callbacks / IPN / courier webhooks go here, e.g. 'payment/bkash/callback'.
    ],

    // Vendor panel: allowed unless `maintenance_block_vendors` is on.
    'vendor' => ['vendor', 'vendor/*'],

    // Still allowed for vendors when vendors are blocked, so they can log in and read the notice.
    'vendor_always_allowed' => ['vendor/login', 'vendor/login/*', 'vendor/logout'],

    // Full mode: read-only customer pages that stay reachable. [method => patterns]
    'full_read_only' => [
        'GET'  => ['track-order', 'invoice/*', 'wholesale-invoice/*'],
        'POST' => ['track-order', 'logout'], // order lookup (reads only) and customer sign-out
    ],

    // Full mode: blocked even though they match a read-only pattern above (forms that write).
    'full_read_only_except' => ['invoice/*/pay', 'invoice/*/reorder'],

    // Banner mode + `maintenance_block_orders`: routes that place orders.
    'order_paths' => ['checkout', 'checkout/*', 'order', 'invoice/*/reorder'],

];
