<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Get dashboard metrics
     */
    public function dashboard(Request $request)
    {
        $vendor = Auth::user();

        $period = $request->get('period', 'monthly');
        $limit = $request->get('limit', 30);

        $dashboard = $this->analyticsService->getDashboard($vendor, $period, $limit);

        return response()->json([
            'status' => 'success',
            'data' => $dashboard,
        ]);
    }

    /**
     * Get event statistics
     */
    public function events(Request $request)
    {
        $vendor = Auth::user();

        $period = $request->get('period', 'week');

        $stats = $this->analyticsService->getEventStats($vendor, $period);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Get top products
     */
    public function topProducts(Request $request)
    {
        $vendor = Auth::user();

        $limit = $request->get('limit', 10);

        $products = $this->analyticsService->getTopProducts($vendor, $limit);

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Track custom event
     */
    public function track(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'event_type' => 'required|string|max:50',
            'event_category' => 'nullable|string|max:50',
            'event_action' => 'nullable|string|max:100',
            'event_label' => 'nullable|string',
            'event_data' => 'nullable|array',
            'event_value' => 'nullable|numeric',
        ]);

        $event = $this->analyticsService->trackEvent(
            $request->event_type,
            $vendor->id,
            Auth::id(),
            $request->event_category,
            $request->event_action,
            $request->event_label,
            $request->event_data,
            $request->event_value
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Event tracked',
            'data' => $event,
        ]);
    }
}
