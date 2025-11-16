<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmartSearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'q' => 'required|string|min:1',
            'filters' => 'nullable|array',
            'context' => 'nullable|string',
        ]);

        $result = $this->searchService->search(
            $vendor,
            $request->q,
            $request->filters,
            $request->context ?? 'catalog'
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function popular(Request $request)
    {
        $vendor = Auth::user();

        $popular = $this->searchService->getPopularSearches($vendor, $request->limit ?? 10);

        return response()->json([
            'status' => 'success',
            'data' => $popular,
        ]);
    }

    public function failed(Request $request)
    {
        $vendor = Auth::user();

        $failed = $this->searchService->getFailedSearches($vendor, $request->limit ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => $failed,
        ]);
    }
}
