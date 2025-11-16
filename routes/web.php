<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebController;

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Authentication
Route::get('/login', [WebController::class, 'login'])->name('login');

// Vendor Routes (protected)
Route::prefix('vendor')->middleware('web')->group(function () {
    Route::get('/dashboard', [WebController::class, 'vendorDashboard'])->name('vendor.dashboard');
    Route::get('/products', [WebController::class, 'vendorProducts'])->name('vendor.products');
    Route::get('/products/{id}', [WebController::class, 'vendorProductShow'])->name('vendor.products.show');
    Route::get('/orders', [WebController::class, 'vendorOrders'])->name('vendor.orders');
    Route::get('/orders/{id}', [WebController::class, 'vendorOrderShow'])->name('vendor.orders.show');
    Route::get('/rfqs', [WebController::class, 'vendorRfqs'])->name('vendor.rfqs');
    Route::get('/rfqs/{id}', [WebController::class, 'vendorRfqShow'])->name('vendor.rfqs.show');
    Route::get('/analytics', [WebController::class, 'vendorAnalytics'])->name('vendor.analytics');
    Route::get('/recommendations', [WebController::class, 'vendorRecommendations'])->name('vendor.recommendations');
    Route::get('/approvals', [WebController::class, 'vendorApprovals'])->name('vendor.approvals');
    Route::get('/documents', [WebController::class, 'vendorDocuments'])->name('vendor.documents');
});

// Admin Routes (protected)
Route::prefix('admin')->middleware('web')->group(function () {
    Route::get('/dashboard', [WebController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/vendors', [WebController::class, 'adminVendors'])->name('admin.vendors');
    Route::get('/vendors/{id}', [WebController::class, 'adminVendorShow'])->name('admin.vendors.show');
    Route::get('/products', [WebController::class, 'adminProducts'])->name('admin.products');
    Route::get('/products/{id}', [WebController::class, 'adminProductShow'])->name('admin.products.show');
    Route::get('/orders', [WebController::class, 'adminOrders'])->name('admin.orders');
    Route::get('/orders/{id}', [WebController::class, 'adminOrderShow'])->name('admin.orders.show');
    Route::get('/rfqs', [WebController::class, 'adminRfqs'])->name('admin.rfqs');
    Route::get('/rfqs/{id}', [WebController::class, 'adminRfqShow'])->name('admin.rfqs.show');
});
