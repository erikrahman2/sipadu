<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FeedSyncService;
use App\Models\Feed;

class SyncFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feeds:sync {--feed= : Sync only a specific feed by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync news from configured feeds to CMS blog posts.';

    /**
     * Execute the console command.
     */
    public function handle(FeedSyncService $service): int
    {
        $feedId = $this->option('feed');

        if ($feedId) {
            $feed = Feed::find($feedId);
            if (!$feed) {
                $this->error("Feed with ID {$feedId} not found.");
                return Command::FAILURE;
            }
            $this->info("Syncing feed: {$feed->name}");
            $created = $service->syncFeed($feed);
            $this->info("Feed '{$feed->name}' synced. {$created} new items processed.");
            return Command::SUCCESS;
        }

        $total = $service->syncAllActive();
        $activeCount = Feed::where('is_active', true)->count();
        $this->info("Sync completed. {$total} new blog posts created from {$activeCount} active feeds.");

        return Command::SUCCESS;
    }
}
