<?php

use EcomAuditors\InertiaShopifyApp\Http\Controllers\AuthController;
use EcomAuditors\InertiaShopifyApp\Http\Controllers\BillingController;
use EcomAuditors\InertiaShopifyApp\Http\Controllers\UserController;
use EcomAuditors\InertiaShopifyApp\Http\Controllers\WebhookController;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\HandleAppBridge;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\ProtectIframe;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\VerifyWebhook;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', ProtectIframe::class, HandleAppBridge::class])->group(function () {
    Route::get('auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
    Route::get('auth/token', [AuthController::class, 'token'])->name('auth.token');

    Route::get('billing/return', [BillingController::class, 'return'])->name('billing.return');

    Route::get('/user/profile', [UserController::class, 'show'])->name('user.show');
    Route::put('/user/profile', [UserController::class, 'update'])->name('user.update');
});

Route::any('webhooks/shopify', WebhookController::class)->name('webhooks.shopify')->middleware(VerifyWebhook::class);