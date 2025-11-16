<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    protected RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function forProduct(Request $request, Product $product)
    {
        $recommendations = $this->recommendationService->getRecommendations(
            $product,
            $request->type,
            $request->limit ?? 10
        );

        return response()->json([
            'status' => 'success',
            'data' => $recommendations,
        ]);
    }

    public function personalized(Request $request)
    {
        $vendor = Auth::user();

        $recommendations = $this->recommendationService->getPersonalizedRecommendations(
            $vendor,
            $request->limit ?? 20
        );

        return response()->json([
            'status' => 'success',
            'data' => $recommendations,
        ]);
    }

    public function trending(Request $request)
    {
        $vendor = Auth::user();

        $trending = $this->recommendationService->getTrendingProducts(
            $vendor,
            $request->limit ?? 20
        );

        return response()->json([
            'status' => 'success',
            'data' => $trending,
        ]);
    }

    public function calculate(Request $request)
    {
        $vendor = Auth::user();

        $count = $this->recommendationService->calculateRecommendations($vendor);

        return response()->json([
            'status' => 'success',
            'message' => "{$count} recommendations calculated",
            'count' => $count,
        ]);
    }

    public function stats(Request $request)
    {
        $vendor = Auth::user();

        $stats = $this->recommendationService->getStats($vendor);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
