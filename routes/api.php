<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Api\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\Vendor\ChatController as VendorChatController;
use App\Http\Controllers\Api\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ChatController as AdminChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (requires authentication)
Route::middleware(['auth:sanctum', 'set.locale'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Vendor routes
    Route::middleware('vendor')->prefix('vendor')->group(function () {

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [VendorProductController::class, 'index']);
            Route::get('/categories', [VendorProductController::class, 'categories']);
            Route::get('/search', [VendorProductController::class, 'search']);
            Route::get('/{product}', [VendorProductController::class, 'show']);
            Route::post('/{product}/calculate-price', [VendorProductController::class, 'calculatePrice']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [VendorOrderController::class, 'index']);
            Route::get('/stats', [VendorOrderController::class, 'stats']);
            Route::post('/', [VendorOrderController::class, 'store']);
            Route::get('/{order}', [VendorOrderController::class, 'show']);
            Route::post('/{order}/cancel', [VendorOrderController::class, 'cancel']);
        });

        // Cart
        Route::prefix('cart')->group(function () {
            Route::post('/validate', [VendorOrderController::class, 'validateCart']);
            Route::post('/calculate', [VendorOrderController::class, 'calculateCart']);
        });

        // Chat
        Route::prefix('chat')->group(function () {
            Route::get('/', [VendorChatController::class, 'index']);
            Route::get('/messages', [VendorChatController::class, 'messages']);
            Route::get('/recent', [VendorChatController::class, 'recent']);
            Route::post('/send', [VendorChatController::class, 'send']);
            Route::post('/mark-as-read', [VendorChatController::class, 'markAsRead']);
            Route::get('/unread-count', [VendorChatController::class, 'unreadCount']);
        });
    });

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Vendors
        Route::prefix('vendors')->group(function () {
            Route::get('/', [AdminVendorController::class, 'index']);
            Route::post('/', [AdminVendorController::class, 'store']);
            Route::get('/groups', [AdminVendorController::class, 'groups']);
            Route::get('/{vendor}', [AdminVendorController::class, 'show']);
            Route::put('/{vendor}', [AdminVendorController::class, 'update']);
            Route::delete('/{vendor}', [AdminVendorController::class, 'destroy']);
        });

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index']);
            Route::post('/', [AdminProductController::class, 'store']);
            Route::get('/categories', [AdminProductController::class, 'categories']);
            Route::get('/inventory-stats', [AdminProductController::class, 'inventoryStats']);
            Route::get('/low-stock', [AdminProductController::class, 'lowStock']);
            Route::get('/{product}', [AdminProductController::class, 'show']);
            Route::put('/{product}', [AdminProductController::class, 'update']);
            Route::delete('/{product}', [AdminProductController::class, 'destroy']);

            // Stock management
            Route::post('/{product}/adjust-stock', [AdminProductController::class, 'adjustStock']);
            Route::get('/{product}/stock-history', [AdminProductController::class, 'stockHistory']);

            // Pricing management
            Route::post('/{product}/vendor-pricing', [AdminProductController::class, 'setVendorPricing']);
            Route::post('/{product}/group-pricing', [AdminProductController::class, 'setGroupPricing']);

            // Visibility management
            Route::post('/{product}/vendor-visibility', [AdminProductController::class, 'setVendorVisibility']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get('/stats', [AdminOrderController::class, 'stats']);
            Route::get('/{order}', [AdminOrderController::class, 'show']);
            Route::put('/{order}/notes', [AdminOrderController::class, 'updateNotes']);
            Route::post('/{order}/confirm', [AdminOrderController::class, 'confirm']);
            Route::post('/{order}/start-processing', [AdminOrderController::class, 'startProcessing']);
            Route::post('/{order}/ship', [AdminOrderController::class, 'ship']);
            Route::post('/{order}/deliver', [AdminOrderController::class, 'deliver']);
            Route::post('/{order}/cancel', [AdminOrderController::class, 'cancel']);
        });

        // Chat
        Route::prefix('chat')->group(function () {
            Route::get('/', [AdminChatController::class, 'index']);
            Route::get('/stats', [AdminChatController::class, 'stats']);
            Route::get('/unread', [AdminChatController::class, 'unread']);
            Route::get('/{conversation}', [AdminChatController::class, 'show']);
            Route::get('/{conversation}/messages', [AdminChatController::class, 'messages']);
            Route::get('/{conversation}/recent', [AdminChatController::class, 'recent']);
            Route::post('/{conversation}/send', [AdminChatController::class, 'send']);
            Route::post('/{conversation}/mark-as-read', [AdminChatController::class, 'markAsRead']);
            Route::post('/{conversation}/archive', [AdminChatController::class, 'archive']);
            Route::post('/{conversation}/reactivate', [AdminChatController::class, 'reactivate']);
        });
    });
});
