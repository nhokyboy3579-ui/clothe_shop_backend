<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Authority Bảo Mật
use App\Http\Controllers\Api\AuthController;

//Admin Controllers
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\AttributeController;
use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\ProductSaleController;
use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\ProductAttributeController;
use App\Http\Controllers\Api\Admin\PostController;
use App\Http\Controllers\Api\Admin\ProductStoreController;
use App\Http\Controllers\Api\Admin\TopicController;

// Site Controllers
use App\Http\Controllers\Api\Site\UserProductController;
use App\Http\Controllers\Api\Site\UserOrderController;
use App\Http\Controllers\Api\Site\UserBannerController;
use App\Http\Controllers\Api\Site\UserProductImageController;
use App\Http\Controllers\Api\Site\ProductInventoryController;
use App\Http\Controllers\Api\Site\UserTopicController;
use App\Http\Controllers\Api\Site\UserPostController;
use App\Http\Controllers\Api\Site\ForgotPasswordController;

// --- 2. ROUTE PUBLIC (KHÔNG CẦN ĐĂNG NHẬP) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products/active', [UserProductController::class, 'indexActiveProducts']);
Route::get('products/sale', [UserProductController::class, 'getFlashSaleProducts']);
Route::get('/products/{id}', [UserProductController::class, 'show']);
Route::get('/banners/slideshow', [UserBannerController::class, 'getSlideshowBanner']);
Route::get('/products/{id}/images', [UserProductImageController::class, 'index']);
Route::get('/products/{id}/inventory', [ProductInventoryController::class, 'show']);
Route::get('topics', [UserTopicController::class, 'index']);
Route::get('posts', [UserPostController::class, 'index']);
Route::get('posts/{slug}', [UserPostController::class, 'show']);
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-reset', [ForgotPasswordController::class, 'verifyAndReset']);

// --- 3. ROUTE USER (CẦN ĐĂNG NHẬP) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/checkout', [UserOrderController::class, 'checkout']);
    Route::get('/my-orders', [UserOrderController::class, 'myOrders']);
    Route::post('/orders/{id}/cancel', [UserOrderController::class, 'cancelOrder']);
});

// --- 4. ROUTE ADMIN (QUẢN TRỊ) ---
// Nên thêm middleware('auth:sanctum') nếu muốn bảo mật
Route::prefix('admin')->group(function () {

    // --- A. Quản lý Kho (Product Store) - CODE MỚI CỦA BẠN ---
    Route::get('/product-store', [ProductStoreController::class, 'index']);
    Route::post('/product-store', [ProductStoreController::class, 'store']);
    Route::put('/product-store/{id}', [ProductStoreController::class, 'update']);
    Route::delete('/product-store/{id}', [ProductStoreController::class, 'destroy']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Route lấy sản phẩm hiển thị ra trang chủ (Active + Sale Price)
    Route::get('/products/active', [ProductController::class, 'getActiveProducts']);
    // Sản phẩm
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Danh mục
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Đơn hàng
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/update/{id}', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // Khách hàng
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Thuộc tính
    Route::group(['prefix' => 'attributes'], function () {
        Route::get('/', [AttributeController::class, 'index']);
        Route::post('/', [AttributeController::class, 'store']);
        Route::put('/{id}', [AttributeController::class, 'update']);
        Route::delete('/{id}', [AttributeController::class, 'destroy']);
    });

    // Banner
    Route::group(['prefix' => 'banners'], function () {
        Route::get('/', [BannerController::class, 'index']);
        Route::post('/', [BannerController::class, 'store']);
        Route::put('/{id}', [BannerController::class, 'update']);
        Route::delete('/{id}', [BannerController::class, 'destroy']);
    });

    // Sale & Images & Attributes
    Route::get('/product-sales', [ProductSaleController::class, 'index']);
    Route::post('/product-sales', [ProductSaleController::class, 'store']);
    Route::put('/product-sales/{id}', [ProductSaleController::class, 'update']);
    Route::delete('/product-sales/{id}', [ProductSaleController::class, 'destroy']);

    Route::prefix('product-images')->group(function () {
        Route::get('/{productId}', [ProductImageController::class, 'index']);
        Route::post('/{productId}', [ProductImageController::class, 'store']);
        Route::delete('/{id}', [ProductImageController::class, 'destroy']);
    });

    Route::get('product-attributes/{productId}', [ProductAttributeController::class, 'index']); // Lấy list theo SP
    Route::post('product-attributes', [ProductAttributeController::class, 'store']); // Thêm mới
    Route::delete('product-attributes/{id}', [ProductAttributeController::class, 'destroy']); // Xóa
    Route::get('attributes-list', [ProductAttributeController::class, 'getAttributeTypes']);

    //Post Manager
    Route::get('posts/topics', [PostController::class, 'getTopics']);
    Route::apiResource('posts', PostController::class);

    //Topic
    Route::apiResource('topics', TopicController::class);
});
