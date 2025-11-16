<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\PredictiveOrderingService;

class GeneratePredictions extends Command
{
    protected $signature = 'predictions:generate {--vendor-id=}';
    protected $description = 'Generate order predictions for vendors';

    public function handle(PredictiveOrderingService $service)
    {
        $vendorId = $this->option('vendor-id');
        
        $vendors = $vendorId 
            ? User::where('id', $vendorId)->where('role', 'vendor')->get()
            : User::where('role', 'vendor')->get();

        $totalCount = 0;

        foreach ($vendors as $vendor) {
            $this->info("Generating predictions for vendor: {$vendor->name}");
            $count = $service->generatePredictions($vendor);
            $totalCount += $count;
            $this->info("Generated {$count} predictions");
        }

        $this->info("Total: {$totalCount} predictions generated");
        return 0;
    }
}
