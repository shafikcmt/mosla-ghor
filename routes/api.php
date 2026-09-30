<?php

use App\Http\Controllers\Api\BotApiController;
use Illuminate\Support\Facades\Route;

/*
| Bot API — for the separate social-media automation app (Messenger / comment
| replies, daily posts). Auth: `Authorization: Bearer mgb_…` created in
| Admin → Bot API. Docs: BOT_API.md.
*/
Route::prefix('bot/v1')->name('api.bot.')->middleware('throttle:300,1')->group(function () {
    Route::get('ping', [BotApiController::class, 'ping'])->middleware(['bot.token', 'throttle:bot-api'])->name('ping');
    Route::get('products', [BotApiController::class, 'products'])->middleware(['bot.token:products:read', 'throttle:bot-api'])->name('products');
    Route::get('products/{id}', [BotApiController::class, 'product'])->whereNumber('id')->middleware(['bot.token:products:read', 'throttle:bot-api'])->name('products.show');
    Route::get('catalog/highlights', [BotApiController::class, 'highlights'])->middleware(['bot.token:catalog:read', 'throttle:bot-api'])->name('highlights');
    Route::get('orders/status', [BotApiController::class, 'orderStatus'])->middleware(['bot.token:orders:read', 'throttle:bot-api'])->name('orders.status');
    Route::post('leads', [BotApiController::class, 'storeLead'])->middleware(['bot.token:leads:write', 'throttle:bot-api'])->name('leads.store');
});
