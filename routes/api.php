<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public routes dengan API key security
Route::middleware('api.key')->group(function () {
    // Auth routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
});

// Protected routes dengan API key + Sanctum
Route::middleware(['api.key', 'auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Orders
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Payments
    Route::get('/payments/create/{orderId}', [PaymentController::class, 'createPayment']);
});

// Payment callback routes (public)
Route::get('/payments/success', [PaymentController::class, 'paymentSuccess']);
Route::get('/payments/failed', [PaymentController::class, 'paymentFailed']);

// Webhook route tanpa authentication
Route::post('/webhooks/payment', [WebhookController::class, 'handlePaymentWebhook']);
