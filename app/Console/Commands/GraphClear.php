<?php

namespace App\Console\Commands;

use App\Services\GraphService;
use Illuminate\Console\Command;

class GraphClear extends Command
{
    protected $signature = 'graph:clear {--force : Skip confirmation}';
    protected $description = 'Delete all nodes and relationships from Neo4j database';

    public function handle(GraphService $graph): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will DELETE ALL nodes and relationships from Neo4j. Continue?')) {
                $this->info('Cancelled.');
                return 1;
            }
        }

        $this->info('🗑️  Clearing Neo4j database...');

        try {
            // Delete all relationships and nodes
            $graph->run('MATCH (n) DETACH DELETE n');
            $this->info('✅ All Neo4j nodes and relationships deleted!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error clearing Neo4j: ' . $e->getMessage());
            return 1;
        }
    }
}
