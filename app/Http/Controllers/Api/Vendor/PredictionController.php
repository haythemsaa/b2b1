<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\PredictiveOrderingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredictionController extends Controller
{
    protected PredictiveOrderingService $predictionService;

    public function __construct(PredictiveOrderingService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    public function index(Request $request)
    {
        $vendor = Auth::user();

        $predictions = $this->predictionService->getPredictions(
            $vendor,
            $request->status,
            $request->limit ?? 50
        );

        return response()->json([
            'status' => 'success',
            'data' => $predictions,
        ]);
    }

    public function generate(Request $request)
    {
        $vendor = Auth::user();

        $count = $this->predictionService->generatePredictions($vendor);

        return response()->json([
            'status' => 'success',
            'message' => "{$count} predictions generated",
            'count' => $count,
        ]);
    }
}
