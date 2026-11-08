<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── 1. PUBLIC API ROUTES (Catalog Browsing & Checkout) ───────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:anti-spam');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:anti-spam');
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me'])->middleware('auth.api');
});
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:anti-spam');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:anti-spam');

// Catalog Browsing (Public for Storefront)
Route::get('products', [ProductController::class, 'index']);
Route::get('products/search', [ProductController::class, 'search']);
Route::get('products/featured', [ProductController::class, 'featured']);
Route::get('products/{id}', [ProductController::class, 'show']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);

Route::get('brands', [BrandController::class, 'index']);
Route::get('brands/search', [BrandController::class, 'search']);
Route::get('brands/{id}', [BrandController::class, 'show']);

Route::get('reviews', [ReviewController::class, 'index']);

// Orders (Order placement & customer viewing own orders)
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{id}', [OrderController::class, 'show']);
Route::post('orders', [OrderController::class, 'store']);
Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
Route::put('orders/{id}/cancel', [OrderController::class, 'cancel']);

// Cart API (Storefront bag synchronization & persistence)
Route::get('cart', [CartController::class, 'index']);
Route::post('cart', [CartController::class, 'store']);
Route::put('cart/{id}', [CartController::class, 'update']);
Route::delete('cart/{id}', [CartController::class, 'destroy']);
Route::delete('cart/clear', [CartController::class, 'clear']);

// Read Users / Customers Directory for Admin & Storefront
Route::get('users', [UserController::class, 'index']);
Route::get('customers', [UserController::class, 'index']);

// ── 2. PROTECTED ADMIN ROUTES (Require Admin Privileges) ───
Route::middleware(['admin'])->group(function () {
    // Inventory Management
    Route::post('products', [ProductController::class, 'store']);
    Route::post('products/{id}', [ProductController::class, 'update']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::post('products/upload-image', [ProductController::class, 'uploadImage']);

    // Category Management
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    // Brand Management
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{id}', [BrandController::class, 'update']);
    Route::delete('brands/{id}', [BrandController::class, 'destroy']);

    // Fulfillment Status Controls
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::delete('orders/{id}', [OrderController::class, 'destroy']);

    // Customer & User Directory Control
    Route::get('users/search', [UserController::class, 'search']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('customers', UserController::class);
});
