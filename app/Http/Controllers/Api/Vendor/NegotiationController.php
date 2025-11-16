<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\NegotiationService;
use App\Models\PriceNegotiation;
use App\Models\Rfq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NegotiationController extends Controller
{
    protected NegotiationService $negotiationService;

    public function __construct(NegotiationService $negotiationService)
    {
        $this->negotiationService = $negotiationService;
    }

    public function index(Request $request)
    {
        $vendor = Auth::user();

        $negotiations = $this->negotiationService->getNegotiationsForVendor(
            $vendor,
            $request->status
        );

        return response()->json([
            'status' => 'success',
            'data' => $negotiations,
        ]);
    }

    public function initiate(Request $request, Rfq $rfq)
    {
        $user = Auth::user();

        $request->validate([
            'negotiation_type' => 'required|in:price_reduction,volume_discount,payment_terms,delivery_terms,product_specification',
            'original_price' => 'required|numeric|min:0',
            'proposed_price' => 'required|numeric|min:0',
            'terms' => 'nullable|array',
            'max_rounds' => 'nullable|integer|min:1|max:10',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $negotiation = $this->negotiationService->initiate(
            $rfq,
            $user,
            $request->negotiation_type,
            $request->original_price,
            $request->proposed_price,
            $request->terms,
            $request->max_rounds ?? 5,
            now()->addDays($request->expires_in_days ?? 7)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Negotiation initiated successfully',
            'data' => $negotiation->load('messages'),
        ], 201);
    }

    public function show(PriceNegotiation $negotiation)
    {
        $user = Auth::user();

        if ($negotiation->vendor_id !== $user->id && $negotiation->initiated_by !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Mark messages as read
        $this->negotiationService->markMessagesAsRead($negotiation, $user);

        return response()->json([
            'status' => 'success',
            'data' => $negotiation->load(['messages', 'rfq']),
        ]);
    }

    public function counter(Request $request, PriceNegotiation $negotiation)
    {
        $user = Auth::user();

        $request->validate([
            'counter_price' => 'required|numeric|min:0',
            'counter_terms' => 'nullable|array',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $negotiation = $this->negotiationService->counter(
                $negotiation,
                $user,
                $request->counter_price,
                $request->counter_terms,
                $request->message
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Counter offer sent successfully',
                'data' => $negotiation->load('messages'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function accept(Request $request, PriceNegotiation $negotiation)
    {
        $user = Auth::user();

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $negotiation = $this->negotiationService->accept($negotiation, $user, $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Negotiation accepted successfully',
                'data' => $negotiation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function reject(Request $request, PriceNegotiation $negotiation)
    {
        $user = Auth::user();

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $negotiation = $this->negotiationService->reject($negotiation, $user, $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Negotiation rejected successfully',
                'data' => $negotiation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function withdraw(PriceNegotiation $negotiation)
    {
        $user = Auth::user();

        $this->negotiationService->withdraw($negotiation, $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Negotiation withdrawn successfully',
        ]);
    }

    public function stats(Request $request)
    {
        $vendor = Auth::user();

        $stats = $this->negotiationService->getNegotiationStats($vendor);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
