<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Web Routes
Route::post('register', [AuthController::class, 'register'])->name('register.post');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Custom Search Routes
Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
Route::get('brands/search', [BrandController::class, 'search'])->name('brands.search');
Route::get('users/search', [UserController::class, 'search'])->name('users.search');

// Web Resource Controllers
Route::resource('products', ProductController::class);
Route::resource('categories', CategoryController::class);
Route::resource('brands', BrandController::class);
Route::resource('orders', OrderController::class);

// Cart Routes
Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::resource('cart', CartController::class);

// Protected Admin Management Web Routes (using 'admin' middleware)
Route::middleware(['admin'])->group(function () {
    Route::resource('users', UserController::class);
});
