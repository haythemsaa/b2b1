<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\RfqQuote;
use App\Services\RfqService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RfqController extends Controller
{
    public function __construct(
        protected RfqService $rfqService
    ) {}

    /**
     * Get all RFQs (admin view)
     */
    public function index(Request $request)
    {
        $query = Rfq::with(['vendor.vendorProfile', 'items', 'quotes'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Only pending review
        if ($request->boolean('pending_review')) {
            $query->submitted();
        }

        // Only active
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $rfqs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => [
                'rfqs' => $rfqs,
                'stats' => $this->rfqService->getAdminStats(),
            ],
        ]);
    }

    /**
     * Get specific RFQ
     */
    public function show(Rfq $rfq)
    {
        $rfq->load([
            'vendor.vendorProfile',
            'items.product',
            'quotes.quotedBy',
            'negotiations.user'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $rfq,
        ]);
    }

    /**
     * Create quote for RFQ
     */
    public function createQuote(Request $request, Rfq $rfq)
    {
        $admin = Auth::user();

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:rfq_items,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_60,net_90',
            'delivery_days' => 'nullable|integer|min:1',
            'valid_for_days' => 'nullable|integer|min:1|max:90',
            'terms_and_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $quote = $this->rfqService->createQuote(
                $rfq,
                $admin,
                $request->items,
                $request->payment_terms,
                $request->delivery_days,
                $request->valid_for_days ?? 30,
                $request->terms_and_conditions,
                $request->notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Quote created successfully',
                'data' => $quote->load('rfq.items'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create quote: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send quote to vendor
     */
    public function sendQuote(RfqQuote $quote)
    {
        try {
            $this->rfqService->sendQuote($quote);

            return response()->json([
                'status' => 'success',
                'message' => 'Quote sent to vendor successfully',
                'data' => $quote->fresh(['rfq']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send quote: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add negotiation message (admin)
     */
    public function addNegotiation(Request $request, Rfq $rfq)
    {
        $admin = Auth::user();

        $request->validate([
            'message' => 'required|string',
            'is_counter_offer' => 'boolean',
            'proposed_price' => 'required_if:is_counter_offer,true|nullable|numeric|min:0',
            'proposed_terms' => 'nullable|in:immediate,net_15,net_30,net_60,net_90',
            'proposed_delivery_days' => 'nullable|integer|min:1',
        ]);

        try {
            $negotiation = $this->rfqService->addNegotiation(
                $rfq,
                $admin,
                $request->message,
                $request->boolean('is_counter_offer'),
                $request->proposed_price,
                $request->proposed_terms,
                $request->proposed_delivery_days
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Message added successfully',
                'data' => $negotiation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get negotiations
     */
    public function negotiations(Rfq $rfq)
    {
        $admin = Auth::user();

        // Mark as read
        $this->rfqService->markNegotiationsAsRead($rfq, $admin);

        $negotiations = $rfq->negotiations()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $negotiations,
        ]);
    }

    /**
     * Cancel RFQ (admin)
     */
    public function cancel(Request $request, Rfq $rfq)
    {
        $admin = Auth::user();

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->rfqService->cancelRfq($rfq, $admin, $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'RFQ cancelled successfully',
                'data' => $rfq->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel RFQ: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get RFQ statistics
     */
    public function stats()
    {
        $stats = $this->rfqService->getAdminStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
