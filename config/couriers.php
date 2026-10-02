<?php

use App\Services\PathaoService;
use App\Services\SteadfastService;
use App\Services\SundarbanService;

return [
    // Existing courier slugs are the provider identifiers; no data conversion.
    'drivers' => [
        'steadfast' => SteadfastService::class,
        'pathao' => PathaoService::class,
        'sundarban' => SundarbanService::class,
    ],
];
