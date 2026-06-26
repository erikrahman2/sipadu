<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Institution;
use App\Models\CaseModel;
use App\Models\CaseTransition;
use App\Models\Document;
use App\Models\OcrValidation;
use App\Services\GraphService;
use Illuminate\Database\Seeder;

/**
 * Neo4j Graph Seeder
 *
 * Seeds Neo4j with relationships for ReBAC testing and development.
 * Syncs MySQL data with Neo4j graph database.
 *
 * Run: php artisan db:seed --class=Neo4jSeeder
 */
class Neo4jSeeder extends Seeder
{
    private GraphService $graph;

    public function __construct(GraphService $graph)
    {
        $this->graph = $graph;
    }

    public function run(): void
    {
        $this->command->info('🔗 Seeding Neo4j graph database...');

        try {
            // 1. Sync all users to graph
            $this->seedUsers();

            // 2. Sync all institutions to graph
            $this->seedInstitutions();

            // 3. Sync all cases to graph
            $this->seedCases();

            // 4. Sync all documents to graph
            $this->seedDocuments();

            // 5. Create relationships
            $this->createRelationships();

            $this->command->info('✅ Neo4j seeding completed successfully!');
        } catch (\Exception $e) {
            $this->command->error('❌ Neo4j seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Seed users as User nodes
     */
    private function seedUsers(): void
    {
        $count = User::count();
        $this->command->line("📝 Syncing {$count} users to Neo4j...");

        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $this->graph->upsertUser([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'institution_id' => $user->institution_id,
                ]);
            }
        });

        $this->command->info("   ✓ Synced {$count} users");
    }

    /**
     * Seed institutions as Institution nodes
     */
    private function seedInstitutions(): void
    {
        $count = Institution::count();
        $this->command->line("🏢 Syncing {$count} institutions to Neo4j...");

        Institution::chunk(50, function ($institutions) {
            foreach ($institutions as $institution) {
                $this->graph->upsertInstitution([
                    'id' => $institution->id,
                    'code' => $institution->code,
                    'name' => $institution->name,
                    'type' => $institution->type,
                ]);
            }
        });

        $this->command->info("   ✓ Synced {$count} institutions");
    }

    /**
     * Seed cases as Case nodes
     */
    private function seedCases(): void
    {
        $count = CaseModel::count();
        $this->command->line("📋 Syncing {$count} cases to Neo4j...");

        CaseModel::chunk(100, function ($cases) {
            foreach ($cases as $case) {
                $this->graph->upsertCase([
                    'id' => $case->id,
                    'case_number' => $case->case_number,
                    'tracking_token' => $case->tracking_token,
                    'status' => $case->status,
                ]);
            }
        });

        $this->command->info("   ✓ Synced {$count} cases");
    }

    /**
     * Seed documents as Document nodes
     */
    private function seedDocuments(): void
    {
        $count = Document::count();
        $this->command->line("📄 Syncing {$count} documents to Neo4j...");

        Document::chunk(100, function ($documents) {
            foreach ($documents as $document) {
                $this->graph->upsertDocument([
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'status' => $document->status,
                    'case_id' => $document->case_id,
                ]);
            }
        });

        $this->command->info("   ✓ Synced {$count} documents");
    }

    /**
     * Create all relationships between nodes
     */
    private function createRelationships(): void
    {
        $this->command->line('🔀 Creating relationships...');

        // WORKS_AT: User -> Institution
        $this->createWorksAtRelationships();

        // MANAGES: Institution -> Case
        $this->createManagesRelationships();

        // RELATED_TO / VERIFY_OPERATOR: workflow role users -> Case
        $this->createRoleUserRelationships();

        // SUBMITTED: User -> Case
        $this->createSubmittedRelationships();

        // HAS: Case -> Document
        $this->createHasRelationships();

        $this->command->info('   ✓ All relationships created');
    }

    /**
     * Create WORKS_AT relationships (User -> Institution)
     */
    private function createWorksAtRelationships(): void
    {
        $count = User::whereNotNull('institution_id')->count();
        $this->command->line("   Creating WORKS_AT relationships ({$count})...");

        User::whereNotNull('institution_id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                try {
                    $this->graph->linkUserToInstitution($user->id, $user->institution_id);
                } catch (\Exception $e) {
                    \Log::warning("Failed to create WORKS_AT for user {$user->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->command->info("      ✓ Created {$count} WORKS_AT relationships");
    }

    /**
     * Create MANAGES relationships (Institution -> Case)
     */
    private function createManagesRelationships(): void
    {
        $count = CaseModel::whereNotNull('institution_id')->count();
        $this->command->line("   Creating MANAGES relationships ({$count})...");

        CaseModel::whereNotNull('institution_id')->chunk(100, function ($cases) {
            foreach ($cases as $case) {
                try {
                    $this->graph->linkInstitutionToCase($case->institution_id, $case->id);
                } catch (\Exception $e) {
                    \Log::warning("Failed to create MANAGES for case {$case->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->command->info("      ✓ Created {$count} MANAGES relationships");
    }

    /**
     * Create SUBMITTED relationships (User -> Case)
     */
    private function createSubmittedRelationships(): void
    {
        $count = CaseModel::whereNotNull('submitter_id')->count();
        $this->command->line("   Creating SUBMITTED relationships ({$count})...");

        CaseModel::whereNotNull('submitter_id')->chunk(100, function ($cases) {
            foreach ($cases as $case) {
                try {
                    $this->graph->linkUserToCase($case->submitter_id, $case->id, 'SUBMITTED');
                } catch (\Exception $e) {
                    \Log::warning("Failed to create SUBMITTED for case {$case->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->command->info("      ✓ Created {$count} SUBMITTED relationships");
    }

    /**
     * Create relationships for workflow users that should be visible in Neo4j.
     */
    private function createRoleUserRelationships(): void
    {
        $relatedCount = 0;
        $verifyCount = 0;
        $this->command->line('   Creating role-based user relationships...');

        CaseModel::chunk(100, function ($cases) use (&$relatedCount, &$verifyCount) {
            foreach ($cases as $case) {
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
                        $this->graph->linkUserToCase($user->id, $case->id, 'MANAGES');
                    } else {
                        $this->graph->linkUserRelatedToCase((int) $userId, $case->id);
                    }
                    $relatedCount++;
                }

                User::role('disdukcapil_staff')
                    ->where('status', 'active')
                    ->get()
                    ->each(function (User $user) use ($case, &$verifyCount) {
                        if (
                            $case->assigned_disdukcapil_user_id === $user->id ||
                            in_array($case->status, ['DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)
                        ) {
                            $this->graph->linkUserAsVerifyOperator($user->id, $case->id);
                            $verifyCount++;
                        }
                    });
            }
        });

        $this->command->info("      Created {$relatedCount} role RELATED_TO relationships");
        $this->command->info("      Created {$verifyCount} role VERIFY_OPERATOR relationships");
    }

    /**
     * Create HAS relationships (Case -> Document)
     */
    private function createHasRelationships(): void
    {
        $count = Document::count();
        $this->command->line("   Creating HAS relationships ({$count})...");

        Document::chunk(100, function ($documents) {
            foreach ($documents as $document) {
                try {
                    $this->graph->linkCaseToDocument($document->case_id, $document->id);
                } catch (\Exception $e) {
                    \Log::warning("Failed to create HAS for document {$document->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->command->info("      ✓ Created {$count} HAS relationships");
    }
}
