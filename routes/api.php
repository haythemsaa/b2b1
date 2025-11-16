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
use App\Http\Controllers\Api\Admin\RfqController as AdminRfqController;
use App\Http\Controllers\Api\Vendor\InvoiceController as VendorInvoiceController;
use App\Http\Controllers\Api\Vendor\RfqController as VendorRfqController;
use App\Http\Controllers\Api\Vendor\AccountController as VendorAccountController;
use App\Http\Controllers\Api\Vendor\AnalyticsController as VendorAnalyticsController;
use App\Http\Controllers\Api\Vendor\ApprovalController as VendorApprovalController;
use App\Http\Controllers\Api\Vendor\NegotiationController as VendorNegotiationController;
use App\Http\Controllers\Api\Vendor\DocumentController as VendorDocumentController;
use App\Http\Controllers\Api\Vendor\NotificationController as VendorNotificationController;
use App\Http\Controllers\Api\Vendor\RecommendationController as VendorRecommendationController;
use App\Http\Controllers\Api\Vendor\PredictionController as VendorPredictionController;
use App\Http\Controllers\Api\Vendor\AutomationController as VendorAutomationController;
use App\Http\Controllers\Api\Vendor\SmartSearchController as VendorSmartSearchController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\AttributeController as AdminAttributeController;

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
            Route::get('/export', [VendorOrderController::class, 'export']);
            Route::get('/csv-template', [VendorOrderController::class, 'downloadCsvTemplate']);
            Route::post('/', [VendorOrderController::class, 'store']);
            Route::post('/import-csv', [VendorOrderController::class, 'importCsv']);
            Route::post('/confirm-csv-import', [VendorOrderController::class, 'confirmCsvImport']);
            Route::post('/quick-order', [VendorOrderController::class, 'quickOrder']);
            Route::post('/create-from-quick-order', [VendorOrderController::class, 'createFromQuickOrder']);
            Route::get('/{order}', [VendorOrderController::class, 'show']);
            Route::get('/{order}/invoice', [VendorOrderController::class, 'downloadInvoice']);
            Route::post('/{order}/cancel', [VendorOrderController::class, 'cancel']);
            Route::post('/{order}/reorder', [VendorOrderController::class, 'reorder']);
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

        // Invoices & Credit
        Route::prefix('invoices')->group(function () {
            Route::get('/', [VendorInvoiceController::class, 'index']);
            Route::get('/credit-stats', [VendorInvoiceController::class, 'creditStats']);
            Route::get('/overdue', [VendorInvoiceController::class, 'overdue']);
            Route::get('/upcoming', [VendorInvoiceController::class, 'upcoming']);
            Route::get('/payment-history', [VendorInvoiceController::class, 'paymentHistory']);
            Route::get('/{invoice}', [VendorInvoiceController::class, 'show']);
            Route::post('/{invoice}/payment', [VendorInvoiceController::class, 'makePayment']);
        });

        // RFQs (Request for Quotation)
        Route::prefix('rfqs')->group(function () {
            Route::get('/', [VendorRfqController::class, 'index']);
            Route::post('/', [VendorRfqController::class, 'store']);
            Route::get('/{rfq}', [VendorRfqController::class, 'show']);
            Route::put('/{rfq}', [VendorRfqController::class, 'update']);
            Route::post('/{rfq}/submit', [VendorRfqController::class, 'submit']);
            Route::post('/{rfq}/quotes/{quote}/accept', [VendorRfqController::class, 'acceptQuote']);
            Route::post('/{rfq}/quotes/{quote}/reject', [VendorRfqController::class, 'rejectQuote']);
            Route::get('/{rfq}/negotiations', [VendorRfqController::class, 'negotiations']);
            Route::post('/{rfq}/negotiations', [VendorRfqController::class, 'addNegotiation']);
            Route::post('/{rfq}/convert-to-order', [VendorRfqController::class, 'convertToOrder']);
            Route::post('/{rfq}/cancel', [VendorRfqController::class, 'cancel']);
        });

        // Account Management (Multi-User Accounts)
        Route::prefix('account')->group(function () {
            Route::get('/', [VendorAccountController::class, 'index']);
            Route::post('/', [VendorAccountController::class, 'store']);
            Route::get('/{accountUser}', [VendorAccountController::class, 'show']);
            Route::put('/{accountUser}', [VendorAccountController::class, 'update']);
            Route::post('/{accountUser}/activate', [VendorAccountController::class, 'activate']);
            Route::post('/{accountUser}/suspend', [VendorAccountController::class, 'suspend']);
            Route::delete('/{accountUser}', [VendorAccountController::class, 'destroy']);
            Route::post('/{accountUser}/permissions', [VendorAccountController::class, 'updatePermissions']);
            Route::post('/{accountUser}/budget', [VendorAccountController::class, 'updateBudget']);
        });

        // Analytics Dashboard
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [VendorAnalyticsController::class, 'dashboard']);
            Route::get('/events', [VendorAnalyticsController::class, 'events']);
            Route::get('/top-products', [VendorAnalyticsController::class, 'topProducts']);
            Route::post('/track', [VendorAnalyticsController::class, 'track']);
        });

        // Approval Workflows
        Route::prefix('approvals')->group(function () {
            Route::get('/workflows', [VendorApprovalController::class, 'workflows']);
            Route::post('/workflows', [VendorApprovalController::class, 'createWorkflow']);
            Route::put('/workflows/{workflow}', [VendorApprovalController::class, 'updateWorkflow']);
            Route::delete('/workflows/{workflow}', [VendorApprovalController::class, 'deleteWorkflow']);
            Route::get('/requests', [VendorApprovalController::class, 'requests']);
            Route::get('/pending', [VendorApprovalController::class, 'pendingApprovals']);
            Route::post('/requests/{approvalRequest}/approve', [VendorApprovalController::class, 'approve']);
            Route::post('/requests/{approvalRequest}/reject', [VendorApprovalController::class, 'reject']);
            Route::get('/stats', [VendorApprovalController::class, 'stats']);
        });

        // Price Negotiations
        Route::prefix('negotiations')->group(function () {
            Route::get('/', [VendorNegotiationController::class, 'index']);
            Route::post('/rfq/{rfq}/initiate', [VendorNegotiationController::class, 'initiate']);
            Route::get('/{negotiation}', [VendorNegotiationController::class, 'show']);
            Route::post('/{negotiation}/counter', [VendorNegotiationController::class, 'counter']);
            Route::post('/{negotiation}/accept', [VendorNegotiationController::class, 'accept']);
            Route::post('/{negotiation}/reject', [VendorNegotiationController::class, 'reject']);
            Route::post('/{negotiation}/withdraw', [VendorNegotiationController::class, 'withdraw']);
            Route::get('/stats/summary', [VendorNegotiationController::class, 'stats']);
        });

        // Document Management
        Route::prefix('documents')->group(function () {
            Route::get('/', [VendorDocumentController::class, 'index']);
            Route::post('/', [VendorDocumentController::class, 'upload']);
            Route::get('/expiring', [VendorDocumentController::class, 'expiring']);
            Route::get('/stats', [VendorDocumentController::class, 'stats']);
            Route::get('/{document}', [VendorDocumentController::class, 'show']);
            Route::get('/{document}/download', [VendorDocumentController::class, 'download']);
            Route::post('/{document}/share', [VendorDocumentController::class, 'share']);
            Route::post('/{document}/archive', [VendorDocumentController::class, 'archive']);
            Route::delete('/{document}', [VendorDocumentController::class, 'delete']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [VendorNotificationController::class, 'index']);
            Route::get('/unread-count', [VendorNotificationController::class, 'unreadCount']);
            Route::get('/preferences', [VendorNotificationController::class, 'preferences']);
            Route::post('/preferences', [VendorNotificationController::class, 'updatePreferences']);
            Route::post('/mark-all-read', [VendorNotificationController::class, 'markAllAsRead']);
            Route::post('/{notification}/mark-read', [VendorNotificationController::class, 'markAsRead']);
            Route::post('/{notification}/archive', [VendorNotificationController::class, 'archive']);
            Route::get('/stats', [VendorNotificationController::class, 'stats']);
        });

        // AI Recommendations
        Route::prefix('recommendations')->group(function () {
            Route::get('/personalized', [VendorRecommendationController::class, 'personalized']);
            Route::get('/trending', [VendorRecommendationController::class, 'trending']);
            Route::get('/product/{product}', [VendorRecommendationController::class, 'forProduct']);
            Route::post('/calculate', [VendorRecommendationController::class, 'calculate']);
            Route::get('/stats', [VendorRecommendationController::class, 'stats']);
        });

        // Predictive Ordering
        Route::prefix('predictions')->group(function () {
            Route::get('/', [VendorPredictionController::class, 'index']);
            Route::post('/generate', [VendorPredictionController::class, 'generate']);
        });

        // Automation Rules
        Route::prefix('automation')->group(function () {
            Route::get('/rules', [VendorAutomationController::class, 'index']);
            Route::post('/rules', [VendorAutomationController::class, 'store']);
            Route::get('/rules/{rule}/executions', [VendorAutomationController::class, 'executions']);
        });

        // Smart Search
        Route::prefix('search')->group(function () {
            Route::get('/', [VendorSmartSearchController::class, 'search']);
            Route::get('/popular', [VendorSmartSearchController::class, 'popular']);
            Route::get('/failed', [VendorSmartSearchController::class, 'failed']);
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

            // Advanced Product System
            Route::post('/advanced', [AdminProductController::class, 'createAdvanced']);
            Route::put('/{product}/advanced', [AdminProductController::class, 'updateAdvanced']);
            Route::post('/{product}/duplicate', [AdminProductController::class, 'duplicate']);
            Route::post('/bulk-update-stock', [AdminProductController::class, 'bulkUpdateStock']);
        });

        // Categories (Advanced Product System)
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index']);
            Route::post('/', [AdminCategoryController::class, 'store']);
            Route::get('/stats', [AdminCategoryController::class, 'stats']);
            Route::get('/{category}', [AdminCategoryController::class, 'show']);
            Route::put('/{category}', [AdminCategoryController::class, 'update']);
            Route::delete('/{category}', [AdminCategoryController::class, 'destroy']);

            // Attribute assignments
            Route::get('/{category}/attributes', [AdminCategoryController::class, 'attributes']);
            Route::post('/{category}/attributes', [AdminCategoryController::class, 'attachAttribute']);
            Route::put('/{category}/attributes/{attribute}', [AdminCategoryController::class, 'updateAttribute']);
            Route::delete('/{category}/attributes/{attribute}', [AdminCategoryController::class, 'detachAttribute']);
        });

        // Attributes (Advanced Product System)
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AdminAttributeController::class, 'index']);
            Route::post('/', [AdminAttributeController::class, 'store']);
            Route::get('/stats', [AdminAttributeController::class, 'stats']);
            Route::get('/{attribute}', [AdminAttributeController::class, 'show']);
            Route::put('/{attribute}', [AdminAttributeController::class, 'update']);
            Route::delete('/{attribute}', [AdminAttributeController::class, 'destroy']);
            Route::post('/{attribute}/validate', [AdminAttributeController::class, 'validateValue']);
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

        // RFQs (Request for Quotation) - Admin
        Route::prefix('rfqs')->group(function () {
            Route::get('/', [AdminRfqController::class, 'index']);
            Route::get('/stats', [AdminRfqController::class, 'stats']);
            Route::get('/{rfq}', [AdminRfqController::class, 'show']);
            Route::post('/{rfq}/quote', [AdminRfqController::class, 'createQuote']);
            Route::post('/quotes/{quote}/send', [AdminRfqController::class, 'sendQuote']);
            Route::get('/{rfq}/negotiations', [AdminRfqController::class, 'negotiations']);
            Route::post('/{rfq}/negotiations', [AdminRfqController::class, 'addNegotiation']);
            Route::post('/{rfq}/cancel', [AdminRfqController::class, 'cancel']);
        });
    });
});
