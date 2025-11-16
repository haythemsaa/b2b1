<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApprovalService;

class ProcessExpiredApprovals extends Command
{
    protected $signature = 'approvals:process-expired';
    protected $description = 'Process expired approval requests';

    public function handle(ApprovalService $service)
    {
        $this->info('Processing expired approvals...');
        $count = $service->processExpiredRequests();
        $this->info("Processed {$count} expired approval requests");
        return 0;
    }
}
