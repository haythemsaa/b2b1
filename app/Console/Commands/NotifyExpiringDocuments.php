<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DocumentService;

class NotifyExpiringDocuments extends Command
{
    protected $signature = 'documents:notify-expiring';
    protected $description = 'Notify vendors about expiring documents';

    public function handle(DocumentService $service)
    {
        $this->info('Processing expiring documents...');
        $count = $service->processExpiringDocuments();
        $this->info("Sent {$count} expiring document notifications");
        return 0;
    }
}
