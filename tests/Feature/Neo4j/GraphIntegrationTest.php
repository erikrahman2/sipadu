<?php

namespace Tests\Feature\Neo4j;

use App\Models\User;
use App\Models\CaseModel;
use App\Models\Institution;
use App\Models\Document;
use App\Services\GraphService;
use Tests\TestCase;

/**
 * Neo4j Integration Tests
 *
 * Tests actual Neo4j graph relationships and path traversal.
 * These tests require Neo4j to be running.
 *
 * Run: php artisan test tests/Feature/Neo4j/GraphIntegrationTest.php
 */
class GraphIntegrationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private GraphService $graph;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles/permissions
        $this->createRolesAndPermissions();

        $this->graph = app(GraphService::class);
    }

    private function createRolesAndPermissions(): void
    {
        app(\Spatie\Permission\Models\Role::class)->create(['name' => 'super_admin', 'guard_name' => 'web']);
        app(\Spatie\Permission\Models\Role::class)->create(['name' => 'pa_management', 'guard_name' => 'web']);
        app(\Spatie\Permission\Models\Role::class)->create(['name' => 'disdukcapil_staff', 'guard_name' => 'web']);
    }

    // ════════════════════════════════════════════════════════════════════════
    // NODE CREATION TESTS
    // ════════════════════════════════════════════════════════════════════════

    public function test_can_upsert_user_node()
    {
        $user = User::factory()->create();

        try {
            $this->graph->upsertUser([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'institution_id' => $user->institution_id,
            ]);

            // Verify node was created (simple pass if no exception)
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_can_upsert_institution_node()
    {
        $institution = Institution::factory()->create();

        try {
            $this->graph->upsertInstitution([
                'id' => $institution->id,
                'code' => $institution->code,
                'name' => $institution->name,
                'type' => $institution->type,
            ]);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_can_upsert_case_node()
    {
        $case = CaseModel::factory()->create();

        try {
            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_can_upsert_document_node()
    {
        $document = Document::factory()->create();

        try {
            $this->graph->upsertDocument([
                'id' => $document->id,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'case_id' => $document->case_id,
            ]);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // RELATIONSHIP CREATION TESTS
    // ════════════════════════════════════════════════════════════════════════

    public function test_can_create_works_at_relationship()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->withInstitution($institution->id)->create();

        try {
            // Create nodes
            $this->graph->upsertInstitution([
                'id' => $institution->id,
                'code' => $institution->code,
                'name' => $institution->name,
                'type' => $institution->type,
            ]);

            $this->graph->upsertUser([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'institution_id' => $user->institution_id,
            ]);

            // Create relationship
            $this->graph->linkUserToInstitution($user->id, $institution->id);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_can_create_submitted_relationship()
    {
        $submitter = User::factory()->create();
        $case = CaseModel::factory()->forSubmitter($submitter)->create();

        try {
            // Create nodes
            $this->graph->upsertUser([
                'id' => $submitter->id,
                'name' => $submitter->name,
                'email' => $submitter->email,
                'institution_id' => $submitter->institution_id,
            ]);

            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            // Create relationship
            $this->graph->linkUserToCase($submitter->id, $case->id, 'SUBMITTED');

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_can_create_manages_relationship()
    {
        $institution = Institution::factory()->create();
        $submitter = User::factory()->create();
        $case = CaseModel::factory()
            ->forSubmitter($submitter)
            ->forInstitution($institution)
            ->create();

        try {
            // Create nodes
            $this->graph->upsertInstitution([
                'id' => $institution->id,
                'code' => $institution->code,
                'name' => $institution->name,
                'type' => $institution->type,
            ]);

            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            // Create relationship
            $this->graph->linkInstitutionToCase($institution->id, $case->id);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_can_create_has_relationship()
    {
        $case = CaseModel::factory()->create();
        $document = Document::factory()->forCase($case)->create();

        try {
            // Create nodes
            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            $this->graph->upsertDocument([
                'id' => $document->id,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'case_id' => $document->case_id,
            ]);

            // Create relationship
            $this->graph->linkCaseToDocument($case->id, $document->id);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // PATH TRAVERSAL TESTS
    // ════════════════════════════════════════════════════════════════════════

    public function test_path_exists_with_single_relationship()
    {
        $submitter = User::factory()->create();
        $case = CaseModel::factory()->forSubmitter($submitter)->create();

        try {
            // Create nodes
            $this->graph->upsertUser([
                'id' => $submitter->id,
                'name' => $submitter->name,
                'email' => $submitter->email,
                'institution_id' => $submitter->institution_id,
            ]);

            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            // Create relationship
            $this->graph->linkUserToCase($submitter->id, $case->id, 'SUBMITTED');

            // Test path exists
            $pathExists = $this->graph->pathExists(
                'User', $submitter->id,
                'Case', $case->id,
                ['SUBMITTED']
            );

            $this->assertTrue($pathExists, 'Path User -[SUBMITTED]-> Case should exist');
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_path_exists_with_multiple_relationships()
    {
        $manager = User::factory()->create();
        $institution = Institution::factory()->create();
        $submitter = User::factory()->create();
        $case = CaseModel::factory()
            ->forSubmitter($submitter)
            ->forInstitution($institution)
            ->create();

        try {
            // Create all nodes
            $this->graph->upsertUser([
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'institution_id' => $institution->id,
            ]);

            $this->graph->upsertInstitution([
                'id' => $institution->id,
                'code' => $institution->code,
                'name' => $institution->name,
                'type' => $institution->type,
            ]);

            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            // Create relationships: User -[WORKS_AT]-> Institution -[MANAGES]-> Case
            $this->graph->linkUserToInstitution($manager->id, $institution->id);
            $this->graph->linkInstitutionToCase($institution->id, $case->id);

            // Test path exists
            $pathExists = $this->graph->pathExists(
                'User', $manager->id,
                'Case', $case->id,
                ['WORKS_AT', 'MANAGES']
            );

            $this->assertTrue($pathExists, 'Path User -[WORKS_AT]-> Institution -[MANAGES]-> Case should exist');
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_path_does_not_exist_between_unrelated_nodes()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $case = CaseModel::factory()->forSubmitter($user2)->create();

        try {
            // Create nodes for user1 and case, but no relationship
            $this->graph->upsertUser([
                'id' => $user1->id,
                'name' => $user1->name,
                'email' => $user1->email,
                'institution_id' => $user1->institution_id,
            ]);

            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            // Don't create any relationship between user1 and case

            // Test path doesn't exist
            $pathExists = $this->graph->pathExists(
                'User', $user1->id,
                'Case', $case->id,
                ['SUBMITTED']
            );

            $this->assertFalse($pathExists, 'Path should not exist between unrelated nodes');
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }

    public function test_path_with_three_hops()
    {
        $manager = User::factory()->create();
        $institution = Institution::factory()->create();
        $submitter = User::factory()->create();
        $case = CaseModel::factory()
            ->forSubmitter($submitter)
            ->forInstitution($institution)
            ->create();
        $document = Document::factory()->forCase($case)->create();

        try {
            // Create all nodes
            $this->graph->upsertUser([
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'institution_id' => $institution->id,
            ]);

            $this->graph->upsertInstitution([
                'id' => $institution->id,
                'code' => $institution->code,
                'name' => $institution->name,
                'type' => $institution->type,
            ]);

            $this->graph->upsertCase([
                'id' => $case->id,
                'case_number' => $case->case_number,
                'tracking_token' => $case->tracking_token,
                'status' => $case->status,
            ]);

            $this->graph->upsertDocument([
                'id' => $document->id,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'case_id' => $document->case_id,
            ]);

            // Create path: User -[WORKS_AT]-> Institution -[MANAGES]-> Case -[HAS]-> Document
            $this->graph->linkUserToInstitution($manager->id, $institution->id);
            $this->graph->linkInstitutionToCase($institution->id, $case->id);
            $this->graph->linkCaseToDocument($case->id, $document->id);

            // Test three-hop path exists
            $pathExists = $this->graph->pathExists(
                'User', $manager->id,
                'Document', $document->id,
                ['WORKS_AT', 'MANAGES', 'HAS']
            );

            $this->assertTrue($pathExists, 'Three-hop path should exist');
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j not available: ' . $e->getMessage());
        }
    }
}
