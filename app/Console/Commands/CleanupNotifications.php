<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=90}';
    protected $description = 'Clean up old read notifications';

    public function handle(NotificationService $service)
    {
        $days = (int) $this->option('days');
        $this->info("Cleaning up notifications older than {$days} days...");
        $count = $service->cleanOldNotifications($days);
        $this->info("Cleaned up {$count} old notifications");
        return 0;
    }
}
