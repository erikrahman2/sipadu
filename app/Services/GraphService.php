<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;

/**
 * Neo4j Graph Service
 *
 * Handles all Cypher queries for node/relationship upsert and
 * relation-path traversal used by the ReBAC engine.
 */
class GraphService
{
    private ClientInterface $client;

    public function __construct()
    {
        $cfg = config('neo4j.connections.default');

        $this->client = ClientBuilder::create()
            ->withDriver(
                'default',
                "{$cfg['scheme']}://{$cfg['host']}:{$cfg['port']}",
                \Laudis\Neo4j\Authentication\Authenticate::basic($cfg['username'], $cfg['password'])
            )
            ->withDefaultDriver('default')
            ->build();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Path Traversal (ReBAC)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check whether a relationship path exists between two nodes.
     *
     * @param  string[] $relTypes  Ordered list of relationship types to traverse.
     */
    public function pathExists(
        string $fromLabel, int $fromId,
        string $toLabel,   int $toId,
        array  $relTypes
    ): bool {
        // Build path pattern: -[:REL1]->()-[:REL2]->...
        $relPattern = implode('', array_map(fn($r) => "-[:$r]->()", $relTypes));
        // Remove trailing () and add final target
        $relPattern = rtrim($relPattern, '()');
        
        $cypher = "MATCH (a:{$fromLabel} {mysql_id: \$fromId}){$relPattern}(b:{$toLabel} {mysql_id: \$toId})
                   RETURN COUNT(a) > 0 AS exists";

        try {
            $result = $this->client->run($cypher, ['fromId' => $fromId, 'toId' => $toId]);
            return (bool) ($result->first()['exists'] ?? false);
        } catch (\Throwable $e) {
            Log::channel('graph')->error('GraphService::pathExists failed', [
                'error' => $e->getMessage(),
                'cypher' => $cypher,
            ]);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Node Upsert
    // ─────────────────────────────────────────────────────────────────────────

    public function upsertUser(array $data): void
    {
        $this->run(
            'MERGE (u:User {mysql_id: $id})
             SET u.name = $name, u.email = $email, u.institution_id = $institution_id',
            $data
        );
    }

    public function upsertInstitution(array $data): void
    {
        $this->run(
            'MERGE (i:Institution {mysql_id: $id})
             SET i.code = $code, i.name = $name, i.type = $type',
            $data
        );
    }

    public function upsertCase(array $data): void
    {
        $this->run(
            'MERGE (c:Case {mysql_id: $id})
             SET c.case_number = $case_number, c.tracking_token = $tracking_token, c.status = $status',
            $data
        );
    }

    public function upsertDocument(array $data): void
    {
        $this->run(
            'MERGE (d:Document {mysql_id: $id})
             SET d.document_type = $document_type, d.status = $status, d.case_id = $case_id',
            $data
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Relationship Upsert
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Link User to Institution with WORKS_AT relationship.
     * (User) -[:WORKS_AT]-> (Institution)
     */
    public function linkUserToInstitution(int $userId, int $institutionId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (i:Institution {mysql_id: $instId})
             MERGE (u)-[:WORKS_AT]->(i)',
            ['userId' => $userId, 'instId' => $institutionId]
        );
    }

    /**
     * Link Institution to Case with HAS relationship.
     * (Institution) -[:HAS]-> (Case)
     */
    public function linkInstitutionToCase(int $institutionId, int $caseId): void
    {
        $this->run(
            'MATCH (i:Institution {mysql_id: $instId}), (c:Case {mysql_id: $caseId})
             MERGE (i)-[:HAS]->(c)',
            ['instId' => $institutionId, 'caseId' => $caseId]
        );
    }

    /**
     * Link User to Case with specified relationship type.
     * Default: (User) -[:SUBMITTED]-> (Case)
     */
    public function linkUserToCase(int $userId, int $caseId, string $relType = 'SUBMITTED'): void
    {
        $this->run(
            "MATCH (u:User {mysql_id: \$userId}), (c:Case {mysql_id: \$caseId})
             MERGE (u)-[:{$relType}]->(c)",
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link Case to Document with HAS_DOCUMENT relationship.
     * (Case) -[:HAS_DOCUMENT]-> (Document)
     */
    public function linkCaseToDocument(int $caseId, int $documentId): void
    {
        $this->run(
            'MATCH (c:Case {mysql_id: $caseId}), (d:Document {mysql_id: $docId})
             MERGE (c)-[:HAS_DOCUMENT]->(d)',
            ['caseId' => $caseId, 'docId' => $documentId]
        );
    }

    /**
     * Link User as verification operator to Case.
     * (User) -[:VERIFY_OPERATOR]-> (Case)
     * Used for assigned_pa_user_id and assigned_disdukcapil_user_id
     */
    public function linkUserAsVerifyOperator(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:VERIFY_OPERATOR]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link User to Case with RELATED_TO relationship.
     * (User) -[:RELATED_TO]-> (Case)
     * Generic relationship for users involved with a case
     */
    public function linkUserRelatedToCase(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:RELATED_TO]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SiPadu Specific Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Link PA Assistant to Case as DRAFT
     * (PA Assistant) -[:DRAFT]-> (Case)
     */
    public function linkPaAssistantDraft(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:DRAFT]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link PA Assistant to Case as REJECTED (but can resubmit)
     * (PA Assistant) -[:DITOLAK]-> (Case)
     */
    public function linkPaAssistantRejected(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:DITOLAK]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link PA Management to Case for review
     * (PA Management) -[:MENUNGGU_REVIEW]-> (Case)
     */
    public function linkPaManagementReview(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:MENUNGGU_REVIEW]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link PA Management to Case as PENGADAAN_PUBLIC
     * (PA Management) -[:PENGADAAN_PUBLIC]-> (Case)
     */
    public function linkPaManagementPublic(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:PENGADAAN_PUBLIC]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link PA Management to Case as PENGADAAN_PA
     * (PA Management) -[:PENGADAAN_PA]-> (Case)
     */
    public function linkPaManagementPa(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:PENGADAAN_PA]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link PA Management to Disdukcapil Staff for validation
     * (PA Management) -[:KIRIM_VALIDASI]-> (Disdukcapil)
     */
    public function linkPaToDisdukcapil(int $paUserId, int $discUserId, int $caseId): void
    {
        $this->run(
            'MATCH (pa:User {mysql_id: $paUserId}), (ds:User {mysql_id: $dsUserId}), (c:Case {mysql_id: $caseId})
             MERGE (pa)-[:KIRIM_VALIDASI {case_id: $caseId}]->(ds)',
            ['paUserId' => $paUserId, 'dsUserId' => $discUserId, 'caseId' => $caseId]
        );
    }

    /**
     * Link Disdukcapil Staff to Case for verification
     * (Disdukcapil) -[:VERIFIKASI]-> (Case)
     */
    public function linkDisdukcapilVerification(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:VERIFIKASI]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link Disdukcapil Staff to Case as COMPLETED with BAST
     * (Disdukcapil) -[:SELESAI]-> (Case)
     */
    public function linkDisdukcapilCompleted(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:SELESAI]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link PA Staff to Case as ARCHIVED
     * (PA Staff) -[:ARSIP]-> (Case)
     */
    public function linkPaStaffArchived(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (c:Case {mysql_id: $caseId})
             MERGE (u)-[:ARSIP]->(c)',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    /**
     * Link Super Admin to Institution (works with both)
     * (Super Admin) -[:SUPER_ADMIN]-> (Institution)
     */
    public function linkSuperAdminToInstitution(int $userId, int $institutionId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId}), (i:Institution {mysql_id: $instId})
             MERGE (u)-[:SUPER_ADMIN]->(i)',
            ['userId' => $userId, 'instId' => $institutionId]
        );
    }

    /**
     * Remove User's VERIFY_OPERATOR link from Case.
     * Called when assignment is removed.
     */
    public function unlinkUserAsVerifyOperator(int $userId, int $caseId): void
    {
        $this->run(
            'MATCH (u:User {mysql_id: $userId})-[r:VERIFY_OPERATOR]->(c:Case {mysql_id: $caseId})
             DELETE r',
            ['userId' => $userId, 'caseId' => $caseId]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Node Deletion
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteNode(string $label, int $id): void
    {
        $this->run(
            "MATCH (n:{$label} {mysql_id: \$id}) DETACH DELETE n",
            ['id' => $id]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Raw Cypher
    // ─────────────────────────────────────────────────────────────────────────

    public function run(string $cypher, array $params = []): SummarizedResult
    {
        try {
            $result = $this->client->run($cypher, $params);
            Log::channel('graph')->debug('Cypher executed', compact('cypher', 'params'));
            return $result;
        } catch (\Throwable $e) {
            Log::channel('graph')->error('Cypher error', [
                'error'  => $e->getMessage(),
                'cypher' => $cypher,
            ]);
            throw $e;
        }
    }
}
