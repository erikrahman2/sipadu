<?php

namespace App\Console\Commands;

use App\Jobs\GraphSyncJob;
use Illuminate\Console\Command;

class GraphSyncManual extends Command
{
    protected $signature = 'graph:sync';
    protected $description = 'Dispatch GraphSyncJob to process integration queue';

    public function handle()
    {
        $this->info('Dispatching GraphSyncJob...');
        
        GraphSyncJob::dispatch();
        
        $this->info('✅ GraphSyncJob dispatched!');
        $this->info('Queue worker will process it in a few seconds...');
    }
}
