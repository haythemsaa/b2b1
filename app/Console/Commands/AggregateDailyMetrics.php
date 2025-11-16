<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;

class AggregateDailyMetrics extends Command
{
    protected $signature = 'analytics:aggregate-daily {--date=}';
    protected $description = 'Aggregate daily metrics from events';

    public function handle(AnalyticsService $service)
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : now()->subDay();

        $this->info("Aggregating metrics for {$date->toDateString()}...");

        $vendors = User::where('role', 'vendor')->get();
        $count = 0;

        foreach ($vendors as $vendor) {
            $service->updateDailyMetrics($vendor, $date);
            $count++;
        }

        $this->info("Aggregated metrics for {$count} vendors");
        return 0;
    }
}
