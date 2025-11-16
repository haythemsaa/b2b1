<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebController extends Controller
{
    /**
     * Show login page
     */
    public function login()
    {
        return view('auth.login');
    }

    /**
     * Vendor Dashboard
     */
    public function vendorDashboard()
    {
        return view('vendor.dashboard');
    }

    /**
     * Vendor Products List
     */
    public function vendorProducts()
    {
        return view('vendor.products');
    }

    /**
     * Vendor Product Detail
     */
    public function vendorProductShow($id)
    {
        return view('vendor.product-detail', compact('id'));
    }

    /**
     * Vendor Orders List
     */
    public function vendorOrders()
    {
        return view('vendor.orders');
    }

    /**
     * Vendor Order Detail
     */
    public function vendorOrderShow($id)
    {
        return view('vendor.order-detail', compact('id'));
    }

    /**
     * Vendor RFQs List
     */
    public function vendorRfqs()
    {
        return view('vendor.rfqs');
    }

    /**
     * Vendor RFQ Detail
     */
    public function vendorRfqShow($id)
    {
        return view('vendor.rfq-detail', compact('id'));
    }

    /**
     * Vendor Analytics
     */
    public function vendorAnalytics()
    {
        return view('vendor.analytics');
    }

    /**
     * Vendor Recommendations
     */
    public function vendorRecommendations()
    {
        return view('vendor.recommendations');
    }

    /**
     * Vendor Approvals
     */
    public function vendorApprovals()
    {
        return view('vendor.approvals');
    }

    /**
     * Vendor Documents
     */
    public function vendorDocuments()
    {
        return view('vendor.documents');
    }

    /**
     * Vendor Profile & Settings
     */
    public function vendorProfile()
    {
        return view('vendor.profile');
    }

    /**
     * Vendor Shopping Cart
     */
    public function vendorCart()
    {
        return view('vendor.cart');
    }

    /**
     * Admin Dashboard
     */
    public function adminDashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Admin Vendors List
     */
    public function adminVendors()
    {
        return view('admin.vendors');
    }

    /**
     * Admin Vendor Detail
     */
    public function adminVendorShow($id)
    {
        return view('admin.vendor-detail', compact('id'));
    }

    /**
     * Admin Products List
     */
    public function adminProducts()
    {
        return view('admin.products');
    }

    /**
     * Admin Product Detail
     */
    public function adminProductShow($id)
    {
        return view('admin.product-detail', compact('id'));
    }

    /**
     * Admin Orders List
     */
    public function adminOrders()
    {
        return view('admin.orders');
    }

    /**
     * Admin Order Detail
     */
    public function adminOrderShow($id)
    {
        return view('admin.order-detail', compact('id'));
    }

    /**
     * Admin RFQs List
     */
    public function adminRfqs()
    {
        return view('admin.rfqs');
    }

    /**
     * Admin RFQ Detail
     */
    public function adminRfqShow($id)
    {
        return view('admin.rfq-detail', compact('id'));
    }
}
