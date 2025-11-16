<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\RecommendationService;

class CalculateRecommendations extends Command
{
    protected $signature = 'recommendations:calculate {--vendor-id=}';
    protected $description = 'Calculate product recommendations for vendors';

    public function handle(RecommendationService $service)
    {
        $vendorId = $this->option('vendor-id');
        
        $vendors = $vendorId 
            ? User::where('id', $vendorId)->where('role', 'vendor')->get()
            : User::where('role', 'vendor')->get();

        $totalCount = 0;

        foreach ($vendors as $vendor) {
            $this->info("Calculating recommendations for vendor: {$vendor->name}");
            $count = $service->calculateRecommendations($vendor);
            $totalCount += $count;
            $this->info("Generated {$count} recommendations");
        }

        $this->info("Total: {$totalCount} recommendations calculated");
        return 0;
    }
}
