<?php

return [
    'enabled' => env('META_CAPI_ENABLED', false),
    'access_token' => env('META_CAPI_ACCESS_TOKEN'),
    'graph_version' => env('META_GRAPH_VERSION', 'v26.0'),
    // Keep analytics asynchronous even when the application's default is sync.
    'queue_connection' => env('META_CAPI_QUEUE_CONNECTION', 'database'),
    'queue' => 'meta-capi',
];
