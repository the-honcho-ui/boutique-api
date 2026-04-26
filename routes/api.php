<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StorefrontController;
use App\Http\Controllers\Api\StorefrontReservationController;
use App\Http\Controllers\Api\SuperAdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StaffController;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Paystack webhook - must be outside auth middleware
Route::post('/webhook/paystack', [OrderController::class, 'paystackWebhook']);

// Public storefront routes
Route::prefix('storefront/{slug}')->group(function () {
    Route::get('/', [StorefrontController::class, 'show']);
    Route::get('/products', [StorefrontController::class, 'products']);
    Route::get('/products/{product}', [StorefrontController::class, 'product']);
    Route::get('/categories', [StorefrontController::class, 'categories']);
    Route::post('/reserve-whatsapp', [StorefrontReservationController::class, 'reserveWhatsapp']);
    Route::post('/waitlist', [StorefrontReservationController::class, 'waitlist']);
    Route::post('/checkout', [OrderController::class, 'initializePaystack']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Store
    Route::get('/store', [StoreController::class, 'show']);
    Route::post('/store', [StoreController::class, 'update']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/{product}/toggle-publish', [ProductController::class, 'togglePublish']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/whatsapp', [OrderController::class, 'storeWhatsapp']);

    // Reservations & waitlists
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);

    // Staff management (owner only)
    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::delete('/staff/{user}', [StaffController::class, 'destroy']);
    Route::post('/staff/{user}/toggle', [StaffController::class, 'toggleActive']);
});

// Super admin routes
// Super admin routes
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/stats', [SuperAdminController::class, 'stats']);
    Route::get('/owners', [SuperAdminController::class, 'owners']);
    Route::post('/owners/{user}/activate', [SuperAdminController::class, 'activate']);
    Route::post('/owners/{user}/deactivate', [SuperAdminController::class, 'deactivate']);
    Route::delete('/owners/{user}', [SuperAdminController::class, 'destroy']);
    Route::get('/stores/{store}', [SuperAdminController::class, 'storeDetail']);
});

