<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NegotiationService;

class ProcessExpiredNegotiations extends Command
{
    protected $signature = 'negotiations:process-expired';
    protected $description = 'Process expired price negotiations';

    public function handle(NegotiationService $service)
    {
        $this->info('Processing expired negotiations...');
        $count = $service->processExpiredNegotiations();
        $this->info("Processed {$count} expired negotiations");
        return 0;
    }
}
