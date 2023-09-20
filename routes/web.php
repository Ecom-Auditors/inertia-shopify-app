<?php

use EcomAuditors\InertiaShopifyApp\Http\Controllers\AuthController;
use EcomAuditors\InertiaShopifyApp\Http\Controllers\UserController;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\HandleAppBridge;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\ProtectIframe;
use Illuminate\Support\Facades\Route;

Route::middleware(ProtectIframe::class)->group(function () {
    Route::get('auth/callback', [AuthController::class, 'callback'])->name('auth.callback');

    Route::middleware(HandleAppBridge::class)->group(function () {
        Route::get('/user/profile', [UserController::class, 'show'])->name('user.show');
        Route::put('/user/profile', [UserController::class, 'update'])->name('user.update');
    });
});