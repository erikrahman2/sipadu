<?php

namespace App\Console\Commands;

use App\Services\GraphService;
use Illuminate\Console\Command;

class VerifyReBACSync extends Command
{
    protected $signature = 'graph:verify-rebac {--details}';
    protected $description = 'Verify ReBAC graph relationships are correctly synchronized';

    public function handle(GraphService $graph): int
    {
        $this->info('🔍 Verifying ReBAC Graph Synchronization...');
        $this->newLine();

        $details = $this->option('details');
        $errors = 0;

        // 1. Check node counts
        $this->info('📊 Node Statistics');
        try {
            $result = $graph->run('MATCH (n) RETURN labels(n)[0] AS label, COUNT(n) AS count ORDER BY label');
            $nodeStats = [];
            foreach ($result as $row) {
                $label = $row['label'] ?? 'Unknown';
                $count = $row['count'] ?? 0;
                $nodeStats[$label] = $count;
                $this->line("   {$label}: <info>{$count}</info> nodes");
            }
            if (empty($nodeStats)) {
                $this->error('   ❌ No nodes found in Neo4j!');
                $errors++;
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to query node statistics: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 2. Check relationship statistics
        $this->info('🔗 Relationship Statistics');
        try {
            $result = $graph->run('MATCH ()-[r]-() RETURN type(r) AS rel_type, COUNT(*) AS count ORDER BY count DESC');
            $relStats = [];
            foreach ($result as $row) {
                $type = $row['rel_type'] ?? 'Unknown';
                $count = $row['count'] ?? 0;
                $relStats[$type] = $count;
                $icon = in_array($type, ['WORKS_AT', 'HAS', 'HAS_DOCUMENT', 'VERIFY_OPERATOR', 'SUBMITTED', 'RELATED_TO']) ? '✅' : '⚠️';
                $this->line("   {$icon} {$type}: <info>{$count}</info> relationships");
            }
            if (empty($relStats)) {
                $this->error('   ❌ No relationships found in Neo4j!');
                $errors++;
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to query relationship statistics: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 3. Check for required relationships
        $this->info('🎯 Required Relationships Check');
        $requiredRels = ['WORKS_AT', 'HAS', 'HAS_DOCUMENT', 'SUBMITTED'];
        try {
            foreach ($requiredRels as $relType) {
                $result = $graph->run("MATCH ()-[r:{$relType}]-() RETURN COUNT(*) AS count");
                $count = $result->first()['count'] ?? 0;
                if ($count > 0) {
                    $this->line("   ✅ {$relType}: <info>{$count}</info> found");
                } else {
                    $this->error("   ❌ {$relType}: <error>NOT FOUND</error>");
                    $errors++;
                }
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to check required relationships: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 4. Check for new relationships
        $this->info('🆕 New Relationships Check');
        $newRels = ['VERIFY_OPERATOR', 'RELATED_TO', 'HAS_DOCUMENT'];
        try {
            foreach ($newRels as $relType) {
                $result = $graph->run("MATCH ()-[r:{$relType}]-() RETURN COUNT(*) AS count");
                $count = $result->first()['count'] ?? 0;
                $icon = $count > 0 ? '✅' : '⚠️';
                $this->line("   {$icon} {$relType}: <info>{$count}</info> found");
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to check new relationships: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 5. Verify Institution -> Case relationships use HAS (not MANAGES)
        $this->info('🏛️ Institution -> Case Relationship Check');
        try {
            $result = $graph->run('MATCH (i:Institution)-[r]->(c:Case) RETURN type(r) AS rel_type, COUNT(*) AS count');
            $hasCount = 0;
            $managesCount = 0;
            foreach ($result as $row) {
                if ($row['rel_type'] === 'HAS') {
                    $hasCount = $row['count'];
                } elseif ($row['rel_type'] === 'MANAGES') {
                    $managesCount = $row['count'];
                }
            }
            if ($hasCount > 0) {
                $this->line("   ✅ HAS relationships: <info>{$hasCount}</info> (Correct)");
            } else {
                $this->error('   ❌ No HAS relationships found!');
                $errors++;
            }
            if ($managesCount > 0) {
                $this->warn("   ⚠️ MANAGES relationships: {$managesCount} (Legacy - should migrate)");
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to check Institution->Case relationships: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 6. Verify Case -> Document relationships use HAS_DOCUMENT (not HAS)
        $this->info('📄 Case -> Document Relationship Check');
        try {
            $result = $graph->run('MATCH (c:Case)-[r]->(d:Document) RETURN type(r) AS rel_type, COUNT(*) AS count');
            $hasDocCount = 0;
            $hasCount = 0;
            foreach ($result as $row) {
                if ($row['rel_type'] === 'HAS_DOCUMENT') {
                    $hasDocCount = $row['count'];
                } elseif ($row['rel_type'] === 'HAS') {
                    $hasCount = $row['count'];
                }
            }
            if ($hasDocCount > 0) {
                $this->line("   ✅ HAS_DOCUMENT relationships: <info>{$hasDocCount}</info> (Correct)");
            } else {
                $this->error('   ❌ No HAS_DOCUMENT relationships found!');
                if ($hasCount > 0) {
                    $this->warn("   ⚠️ Found {$hasCount} HAS relationships (Legacy - should migrate)");
                }
                $errors++;
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to check Case->Document relationships: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 7. Check VERIFY_OPERATOR assignments
        $this->info('👤 VERIFY_OPERATOR Assignments Check');
        try {
            $result = $graph->run('MATCH (u:User)-[r:VERIFY_OPERATOR]->(c:Case) RETURN COUNT(*) AS count');
            $count = $result->first()['count'] ?? 0;
            $this->line("   Found <info>{$count}</info> VERIFY_OPERATOR assignments");
            if ($count === 0) {
                $this->warn("   ⚠️ No operator assignments found (may be expected if no cases assigned)");
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to check VERIFY_OPERATOR: {$e->getMessage()}");
            $errors++;
        }
        $this->newLine();

        // 8. Sample path verification if details requested
        if ($details) {
            $this->info('🔍 Sample Path Verification (Details Mode)');
            try {
                // Find a sample User with Institution
                $result = $graph->run('MATCH (u:User)-[:WORKS_AT]->(i:Institution) RETURN u.mysql_id AS user_id, u.email AS email LIMIT 1');
                if ($result->count() > 0) {
                    $row = $result->first();
                    $userId = $row['user_id'];
                    $email = $row['email'];
                    $this->line("   Sample User: {$email} (ID: {$userId})");

                    // Check their case access
                    $pathResult = $graph->run(
                        'MATCH (u:User {mysql_id: $uid})-[*1..3]-(c:Case) RETURN DISTINCT c.case_number LIMIT 5',
                        ['uid' => $userId]
                    );
                    $pathCount = $pathResult->count();
                    $this->line("   Accessible cases: {$pathCount}");
                    foreach ($pathResult as $row) {
                        $this->line("     - " . ($row['case_number'] ?? 'N/A'));
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("   Could not fetch sample paths: {$e->getMessage()}");
            }
            $this->newLine();
        }

        // Summary
        $this->newLine();
        if ($errors === 0) {
            $this->info('✅ ReBAC Graph Synchronization: PASSED');
            $this->info('All required relationships and nodes are properly configured.');
            return 0;
        } else {
            $this->error("❌ ReBAC Graph Synchronization: FAILED ({$errors} errors found)");
            $this->warn('Please run: php artisan graph:sync-all');
            return 1;
        }
    }
}
