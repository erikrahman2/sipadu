<?php

namespace App\Console\Commands;

use App\Models\CaseModel;
use App\Models\CaseTransition;
use App\Models\Document;
use App\Models\Institution;
use App\Models\OcrValidation;
use App\Models\User;
use App\Services\GraphService;
use Illuminate\Console\Command;

class SyncReBACGraph extends Command
{
    protected $signature = 'graph:sync-all {--clear} {--migrate-legacy}';
    protected $description = 'Synchronize all data to Neo4j with correct ReBAC relationships';

    public function handle(GraphService $graph): int
    {
        $this->info('🚀 Starting ReBAC Graph Synchronization...');
        $this->newLine();

        $clear = $this->option('clear');
        $migrateLegacy = $this->option('migrate-legacy');

        // Step 1: Clear graph if requested
        if ($clear) {
            $this->info('🗑️ Clearing Neo4j database...');
            try {
                $graph->run('MATCH (n) DETACH DELETE n');
                $this->line('   ✅ Database cleared');
            } catch (\Throwable $e) {
                $this->error("   ❌ Failed to clear database: {$e->getMessage()}");
                return 1;
            }
            $this->newLine();
        }

        // Step 2: Migrate legacy relationships if requested
        if ($migrateLegacy) {
            $this->info('🔄 Migrating legacy relationships...');
            try {
                // Migrate MANAGES to HAS
                $result = $graph->run(
                    'MATCH (i:Institution)-[r:MANAGES]->(c:Case)
                     CREATE (i)-[:HAS]->(c)
                     DELETE r
                     RETURN COUNT(*) AS migrated'
                );
                $count = $result->first()['migrated'] ?? 0;
                $this->line("   ✅ Migrated {$count} MANAGES -> HAS relationships");

                // Migrate Case HAS to Case HAS_DOCUMENT
                $result = $graph->run(
                    'MATCH (c:Case)-[r:HAS]->(d:Document)
                     CREATE (c)-[:HAS_DOCUMENT]->(d)
                     DELETE r
                     RETURN COUNT(*) AS migrated'
                );
                $count = $result->first()['migrated'] ?? 0;
                $this->line("   ✅ Migrated {$count} HAS -> HAS_DOCUMENT relationships");
            } catch (\Throwable $e) {
                $this->error("   ❌ Failed to migrate legacy relationships: {$e->getMessage()}");
                return 1;
            }
            $this->newLine();
        }

        // Step 3: Sync Users
        $this->info('👤 Syncing Users...');
        try {
            $count = 0;
            User::query()->chunk(100, function ($users) use ($graph, &$count) {
                foreach ($users as $user) {
                    $graph->upsertUser([
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'institution_id' => $user->institution_id,
                    ]);
                    $count++;
                }
            });
            $this->line("   ✅ Synced {$count} users");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to sync users: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 4: Sync Institutions
        $this->info('🏛️ Syncing Institutions...');
        try {
            $count = 0;
            Institution::query()->chunk(100, function ($institutions) use ($graph, &$count) {
                foreach ($institutions as $inst) {
                    $graph->upsertInstitution([
                        'id' => $inst->id,
                        'code' => $inst->code,
                        'name' => $inst->name,
                        'type' => $inst->type,
                    ]);
                    $count++;
                }
            });
            $this->line("   ✅ Synced {$count} institutions");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to sync institutions: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 5: Link Users to Institutions (WORKS_AT)
        $this->info('🔗 Creating User -> Institution (WORKS_AT) relationships...');
        try {
            $count = 0;
            User::whereNotNull('institution_id')
                ->chunk(100, function ($users) use ($graph, &$count) {
                    foreach ($users as $user) {
                        $graph->linkUserToInstitution($user->id, $user->institution_id);
                        $count++;
                    }
                });
            $this->line("   ✅ Created {$count} WORKS_AT relationships");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to create WORKS_AT relationships: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 6: Sync Cases
        $this->info('📋 Syncing Cases...');
        try {
            $count = 0;
            CaseModel::query()->chunk(100, function ($cases) use ($graph, &$count) {
                foreach ($cases as $case) {
                    $graph->upsertCase([
                        'id' => $case->id,
                        'case_number' => $case->case_number,
                        'tracking_token' => $case->tracking_token,
                        'status' => $case->status,
                    ]);
                    $count++;
                }
            });
            $this->line("   ✅ Synced {$count} cases");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to sync cases: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 7: Create Case Relationships
        $this->info('🔗 Creating Case relationships...');
        try {
            $submittedCount = 0;
            $hasCount = 0;
            $verifyCount = 0;
            $relatedCount = 0;
            $roleRelatedCount = 0;
            $roleVerifyCount = 0;

            CaseModel::with(['submitter', 'institution', 'assignedPaUser', 'assignedDisdukcapilUser'])
                ->chunk(100, function ($cases) use ($graph, &$submittedCount, &$hasCount, &$verifyCount, &$relatedCount, &$roleRelatedCount, &$roleVerifyCount) {
                    foreach ($cases as $case) {
                        // Institution HAS Case
                        if ($case->institution_id) {
                            $graph->linkInstitutionToCase($case->institution_id, $case->id);
                            $hasCount++;
                        }

                        // User SUBMITTED Case
                        if ($case->submitter_id) {
                            $graph->linkUserToCase($case->submitter_id, $case->id, 'SUBMITTED');
                            $submittedCount++;
                        }

                        // PA User VERIFY_OPERATOR
                        if ($case->assigned_pa_user_id) {
                            $graph->linkUserAsVerifyOperator($case->assigned_pa_user_id, $case->id);
                            $verifyCount++;
                        }

                        // Disdukcapil User VERIFY_OPERATOR
                        if ($case->assigned_disdukcapil_user_id) {
                            $graph->linkUserAsVerifyOperator($case->assigned_disdukcapil_user_id, $case->id);
                            $verifyCount++;
                        }

                        // All users RELATED_TO
                        $relatedIds = collect([
                            $case->submitter_id,
                            $case->assigned_pa_user_id,
                            $case->assigned_disdukcapil_user_id,
                        ])->filter();

                        foreach ($relatedIds as $userId) {
                            $graph->linkUserRelatedToCase($userId, $case->id);
                            $relatedCount++;
                        }

                        [$addedRelated, $addedVerify] = $this->linkHandledUsersToCase($graph, $case);
                        $roleRelatedCount += $addedRelated;
                        $roleVerifyCount += $addedVerify;

                    }
                });

            $this->line("   ✅ SUBMITTED: {$submittedCount} relationships");
            $this->line("   ✅ HAS: {$hasCount} relationships");
            $this->line("   ✅ VERIFY_OPERATOR: {$verifyCount} relationships");
            $this->line("   ✅ RELATED_TO: {$relatedCount} relationships");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to create case relationships: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 8: Sync Documents
        $this->info('📄 Syncing Documents...');
        try {
            $count = 0;
            Document::query()->chunk(100, function ($documents) use ($graph, &$count) {
                foreach ($documents as $doc) {
                    $graph->upsertDocument([
                        'id' => $doc->id,
                        'document_type' => $doc->document_type,
                        'status' => $doc->status,
                        'case_id' => $doc->case_id,
                    ]);
                    $count++;
                }
            });
            $this->line("   ✅ Synced {$count} documents");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to sync documents: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 9: Create Document Relationships
        $this->info('🔗 Creating Document relationships...');
        try {
            $count = 0;
            Document::query()->chunk(100, function ($documents) use ($graph, &$count) {
                foreach ($documents as $doc) {
                    if ($doc->case_id) {
                        $graph->linkCaseToDocument($doc->case_id, $doc->id);
                        $count++;
                    }
                }
            });
            $this->line("   ✅ Created {$count} HAS_DOCUMENT relationships");
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to create document relationships: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Step 10: Refresh ReBAC cache version
        $this->info('🧹 Refreshing ReBAC policy cache...');
        try {
            app(\App\Services\ReBACService::class)->bumpCacheVersion();
            $this->line('   ✅ Policy cache version bumped');
        } catch (\Throwable $e) {
            $this->warn("   ⚠️ Failed to clear cache (non-critical): {$e->getMessage()}");
        }
        $this->newLine();

        // Final verification
        $this->info('✅ ReBAC Graph Synchronization Complete!');
        $this->newLine();
        $this->line('Run <fg=cyan>php artisan graph:verify-rebac --details</> to verify the sync.');

        return 0;
    }

    /**
     * Link users to cases they actually handled in the application history.
     */
    private function linkHandledUsersToCase(GraphService $graph, CaseModel $case): array
    {
        $relatedCount = 0;
        $verifyCount = 0;

        $handledUserIds = collect([
            $case->submitter_id,
            $case->assigned_pa_user_id,
            $case->assigned_disdukcapil_user_id,
        ]);

        $handledUserIds = $handledUserIds
            ->merge(CaseTransition::where('case_id', $case->id)->pluck('transitioned_by'))
            ->merge(OcrValidation::where('case_id', $case->id)->pluck('reviewed_by'))
            ->merge(Document::where('case_id', $case->id)->pluck('uploaded_by'))
            ->filter()
            ->unique()
            ->values();

        if (in_array($case->status, ['PA_REVIEW', 'DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)) {
            $handledUserIds = $handledUserIds
                ->merge(User::role('pa_management')->where('status', 'active')->pluck('id'))
                ->unique()
                ->values();
        }

        foreach ($handledUserIds as $userId) {
            $user = User::find((int) $userId);
            if ($user && $user->hasRole('pa_management')) {
                $graph->linkUserToCase($user->id, $case->id, 'MANAGES');
            } else {
                $graph->linkUserRelatedToCase((int) $userId, $case->id);
            }
            $relatedCount++;
        }

        User::role('disdukcapil_staff')
            ->where('status', 'active')
            ->get()
            ->each(function (User $user) use ($graph, $case, &$verifyCount) {
                if (
                    $case->assigned_disdukcapil_user_id === $user->id ||
                    in_array($case->status, ['DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)
                ) {
                    $graph->linkUserAsVerifyOperator($user->id, $case->id);
                    $verifyCount++;
                }
            });

        return [$relatedCount, $verifyCount];
    }
}
