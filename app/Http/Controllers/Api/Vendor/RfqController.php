<?php

namespace App\Http\Controllers\Api\Vendor;

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
     * Get vendor's RFQs
     */
    public function index(Request $request)
    {
        $vendor = Auth::user();

        $query = Rfq::forVendor($vendor->id)
            ->with(['items', 'quotes'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
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
                'stats' => $this->rfqService->getVendorStats($vendor),
            ],
        ]);
    }

    /**
     * Create new RFQ
     */
    public function store(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_budget' => 'nullable|numeric|min:0',
            'required_delivery_date' => 'nullable|date|after:today',
            'priority' => 'required|in:low,medium,high,urgent',
            'expires_in_days' => 'nullable|integer|min:1|max:90',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.sku' => 'nullable|string',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.specifications' => 'nullable|array',
        ]);

        try {
            $rfq = $this->rfqService->createRfq(
                $vendor,
                $request->title,
                $request->items,
                $request->description,
                $request->target_budget,
                $request->required_delivery_date,
                $request->priority,
                $request->expires_in_days ?? 30
            );

            return response()->json([
                'status' => 'success',
                'message' => 'RFQ created successfully',
                'data' => $rfq,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create RFQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific RFQ
     */
    public function show(Rfq $rfq)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $rfq->load([
            'items.product',
            'quotes.quotedBy',
            'negotiations.user'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'rfq' => $rfq,
                'unread_count' => $this->rfqService->getUnreadNegotiations($rfq, $vendor)->count(),
            ],
        ]);
    }

    /**
     * Update RFQ (draft only)
     */
    public function update(Request $request, Rfq $rfq)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$rfq->canBeEdited()) {
            return response()->json([
                'status' => 'error',
                'message' => 'RFQ cannot be edited in current status',
            ], 422);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'target_budget' => 'nullable|numeric|min:0',
            'required_delivery_date' => 'nullable|date|after:today',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'items' => 'sometimes|required|array|min:1',
        ]);

        try {
            $rfq = $this->rfqService->updateRfq($rfq, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'RFQ updated successfully',
                'data' => $rfq,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update RFQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit RFQ
     */
    public function submit(Rfq $rfq)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->rfqService->submitRfq($rfq);

            return response()->json([
                'status' => 'success',
                'message' => 'RFQ submitted successfully',
                'data' => $rfq->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit RFQ: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept quote
     */
    public function acceptQuote(Rfq $rfq, RfqQuote $quote)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->rfqService->acceptQuote($rfq, $quote, $vendor);

            return response()->json([
                'status' => 'success',
                'message' => 'Quote accepted successfully',
                'data' => $rfq->fresh(['quotes']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to accept quote: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject quote
     */
    public function rejectQuote(Request $request, Rfq $rfq, RfqQuote $quote)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->rfqService->rejectQuote($rfq, $quote, $vendor, $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Quote rejected',
                'data' => $rfq->fresh(['quotes']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject quote: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add negotiation message
     */
    public function addNegotiation(Request $request, Rfq $rfq)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

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
                $vendor,
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
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        // Mark as read
        $this->rfqService->markNegotiationsAsRead($rfq, $vendor);

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
     * Convert to order
     */
    public function convertToOrder(Rfq $rfq)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $order = $this->rfqService->convertToOrder($rfq);

            return response()->json([
                'status' => 'success',
                'message' => 'RFQ converted to order successfully',
                'data' => [
                    'order' => $order->load('items'),
                    'rfq' => $rfq->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert to order: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel RFQ
     */
    public function cancel(Request $request, Rfq $rfq)
    {
        $vendor = Auth::user();

        if ($rfq->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->rfqService->cancelRfq($rfq, $vendor, $request->reason);

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
}
