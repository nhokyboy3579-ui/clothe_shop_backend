<?php

use Illuminate\Support\Facades\Route;
// ... (Các use Controller giữ nguyên như cũ) ...
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductSaleController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

// 1. FRONTEND - PUBLIC
Route::get('/', [SiteController::class, 'index'])->name('site.home');
Route::get('/product/{slug}', [SiteController::class, 'productDetail'])->name('site.product.detail');
Route::get('/cart', [CartController::class, 'index'])->name('site.cart.index');
Route::post('/add-to-cart', [CartController::class, 'add'])->name('site.cart.add');
Route::get('/cart/remove/{id}', [CartController::class, 'remove'])->name('site.cart.remove');
Route::post('/cart/update', [CartController::class, 'update'])->name('site.cart.update');

// 2. FRONTEND - CẦN ĐĂNG NHẬP
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [SiteController::class, 'profile'])->name('site.profile');
    Route::put('/profile', [SiteController::class, 'updateProfile'])->name('site.profile.update');

    Route::get('/my-orders', [SiteController::class, 'myOrders'])->name('site.orders');
    Route::get('/my-orders/{id}', [SiteController::class, 'myOrderDetail'])->name('site.order.detail');

    // Route Hủy đơn (MỚI)
    Route::post('/my-orders/cancel/{id}', [SiteController::class, 'cancelOrder'])->name('site.order.cancel');

    Route::get('/checkout', [CartController::class, 'checkout'])->name('site.cart.checkout');
    Route::post('/checkout', [CartController::class, 'processOrder'])->name('site.cart.process');
});

// 3. AUTH
Route::get('/login', [AuthController::class, 'showSiteLogin'])->name('site.login');
Route::post('/login', [AuthController::class, 'siteLogin'])->name('site.login.submit');
Route::get('/register', [AuthController::class, 'showSiteRegister'])->name('site.register');
Route::post('/register', [AuthController::class, 'siteRegister'])->name('site.register.submit');
Route::get('/logout', [AuthController::class, 'siteLogout'])->name('site.logout');
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

// 4. BACKEND (ADMIN)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AuthController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard', [AuthController::class, 'dashboard']);
    Route::get('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/users', [UserController::class, 'index'])->name('user.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/users/store', [UserController::class, 'store'])->name('user.store');
    Route::get('/users/edit/{id}', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/users/update/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/users/destroy/{id}', [UserController::class, 'destroy'])->name('user.destroy');

    Route::resource('category', CategoryController::class);
    Route::resource('attribute', AttributeController::class);
    Route::resource('product', ProductController::class);

    Route::get('/orders', [OrderController::class, 'index'])->name('order.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('order.show');
    Route::put('/orders/update/{id}', [OrderController::class, 'updateStatus'])->name('order.update');
    Route::delete('/orders/destroy/{id}', [OrderController::class, 'destroy'])->name('order.destroy');

    Route::get('/product/{id}/image', [ProductImageController::class, 'index'])->name('product.image.index');
    Route::post('/product/{id}/image', [ProductImageController::class, 'store'])->name('product.image.store');
    Route::delete('/product/image/{id}', [ProductImageController::class, 'destroy'])->name('product.image.destroy');

    Route::get('/product/{id}/sale', [ProductSaleController::class, 'index'])->name('product.sale.index');
    Route::post('/product/{id}/sale', [ProductSaleController::class, 'store'])->name('product.sale.store');
    Route::delete('/product/sale/{id}', [ProductSaleController::class, 'destroy'])->name('product.sale.destroy');

    Route::get('/product/{id}/attributes', [ProductAttributeController::class, 'index'])->name('product.attribute.index');
    Route::post('/product/{id}/attributes', [ProductAttributeController::class, 'store'])->name('product.attribute.store');
    Route::delete('/product/attributes/{id}', [ProductAttributeController::class, 'destroy'])->name('product.attribute.destroy');
});
