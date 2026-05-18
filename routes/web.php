<?php

use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/success/{orderNumber}', [OrderController::class, 'success'])->name('order.success');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('products', AdminProductController::class);
});
