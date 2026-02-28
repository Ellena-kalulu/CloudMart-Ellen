<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;


// Public routes (no authentication required)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']); 
    Route::get('/featured', [ProductController::class, 'featured']); 
    Route::get('/category/{categoryName}', [ProductController::class, 'getByCategory']);
    Route::get('/{id}', [ProductController::class, 'show']); 
    Route::get('/{id}/check-stock', [ProductController::class, 'checkStock']); 
});

// Categories (public)
Route::get('/categories', [CategoryController::class, 'index']);

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Order Management (Public)
Route::prefix('orders')->group(function () {
    Route::get('/{orderId}', [OrderController::class, 'show']); 
});

// Protected routes 
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Cart Management
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']); 
        Route::post('/', [CartController::class, 'store']); 
        Route::put('/{itemId}', [CartController::class, 'update']); 
        Route::delete('/{itemId}', [CartController::class, 'destroy']); 
        Route::delete('/', [CartController::class, 'clear']); 
    });

    // Order Management
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']); 
        Route::post('/', [OrderController::class, 'store']); 
        Route::post('/{orderId}/cancel', [OrderController::class, 'cancel']); 
    });

    // Delivery Confirmation 
    Route::post('/delivery/confirm', [OrderController::class, 'confirmDelivery'])->middleware('role:delivery_personnel,admin');

    // Admin: Product management
    Route::prefix('admin/products')->middleware('role:admin')->group(function () {
        Route::get('/', [ProductController::class, 'index']); 
        Route::post('/', [ProductController::class, 'store']); 
        Route::put('/{id}', [ProductController::class, 'update']); 
        Route::delete('/{id}', [ProductController::class, 'destroy']); 
        Route::post('/{id}/stock', [ProductController::class, 'updateStock']); 
    });

    // Admin: User management
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::put('/users/{userId}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);
        Route::get('/stats', [AdminController::class, 'getStats']);
        Route::get('/orders', [AdminController::class, 'getOrders']);
        Route::put('/orders/{orderId}/status', [AdminController::class, 'updateOrderStatus']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::get('product-categories', [ProductController::class, 'categories']);
    });
});

Route::get('/send-sms', [NotificationController::class, 'sendSMSNotification']); 