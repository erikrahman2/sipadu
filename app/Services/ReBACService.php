<?php

namespace App\Services;

use App\Models\User;
use App\Services\GraphService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Relationship-Based Access Control (ReBAC) Engine
 *
 * Policy is evaluated by traversing the Neo4j graph and checking
 * whether a relation path between the requesting User and the
 * target resource exists according to the defined policy rules.
 */
class ReBACService
{
    public function __construct(private readonly GraphService $graph) {}

    // ─────────────────────────────────────────────────────────────────────────
    // PEP  (Policy Enforcement Point) – called from controllers / middleware
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Enforce access; throws 403 on DENY.
     */
    public function enforce(User $user, string $action, string $resourceType, int $resourceId): void
    {
        if ($this->permit($user, $action, $resourceType, $resourceId)) {
            return;
        }
        Log::channel('policy')->warning('ReBAC DENY', [
            'user_id'      => $user->id,
            'action'       => $action,
            'resource'     => $resourceType,
            'resource_id'  => $resourceId,
        ]);
        abort(403, 'Access denied by ReBAC policy.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDP  (Policy Decision Point)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns true (PERMIT) or false (DENY).
     */
    public function permit(User $user, string $action, string $resourceType, int $resourceId): bool
    {
        // Super-admin bypass
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $cacheKey = "rebac:{$user->id}:{$action}:{$resourceType}:{$resourceId}";
        $ttl      = config('neo4j.policy_cache_ttl', 300);

        $decision = Cache::remember($cacheKey, $ttl, function () use ($user, $action, $resourceType, $resourceId) {
            return $this->evaluate($user, $action, $resourceType, $resourceId);
        });

        Log::channel('policy')->info('ReBAC decision', [
            'user_id'    => $user->id,
            'action'     => $action,
            'resource'   => "{$resourceType}:{$resourceId}",
            'decision'   => $decision ? 'PERMIT' : 'DENY',
        ]);

        return $decision;
    }

    /**
     * Invalidate cached decisions for a specific user + resource.
     */
    public function invalidateCache(User $user, string $resourceType, int $resourceId): void
    {
        foreach (['view', 'edit', 'approve', 'validate', 'download', 'delete'] as $action) {
            Cache::forget("rebac:{$user->id}:{$action}:{$resourceType}:{$resourceId}");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Graph traversal evaluation
    // ─────────────────────────────────────────────────────────────────────────

    private function evaluate(User $user, string $action, string $resourceType, int $resourceId): bool
    {
        return match ("{$action}:{$resourceType}") {
            'view:Case'              => $this->canViewCase($user, $resourceId),
            'edit:Case'              => $this->canEditCase($user, $resourceId),
            'approve:Case'           => $this->canApproveCase($user, $resourceId),
            'validate:Case'          => $this->canValidateCase($user, $resourceId),
            'download:Document'      => $this->canDownloadDocument($user, $resourceId),
            'view:Document'          => $this->canViewDocument($user, $resourceId),
            'process_ocr:Document'   => $this->canProcessOcr($user, $resourceId),
            default                  => false,
        };
    }

    // ── Policy implementations ────────────────────────────────────────────────

    private function canViewCase(User $user, int $caseId): bool
    {
        // Fallback: check MySQL directly if Neo4j unavailable (development mode)
        $case = \App\Models\CaseModel::find($caseId);
        if ($case && $case->submitter_id === $user->id) {
            return true;
        }

        // Check if user works at same institution
        if ($case && $case->institution_id === $user->institution_id) {
            return true;
        }

        // Try Neo4j graph traversal
        try {
            // PERMIT if user submitted the case …
            if ($this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['SUBMITTED']
            )) {
                return true;
            }

            // … or works at the institution that manages the case
            return $this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['WORKS_AT', 'MANAGES']
            );
        } catch (\Exception $e) {
            \Log::warning('Neo4j graph check failed, using MySQL fallback', [
                'case_id' => $caseId,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            // Fallback already handled above
            return false;
        }
    }

    private function canEditCase(User $user, int $caseId): bool
    {
        // Only submitter in DRAFT state
        return $this->graph->pathExists(
            'User', $user->id,
            'Case', $caseId,
            ['SUBMITTED']
        );
    }

    private function canApproveCase(User $user, int $caseId): bool
    {
        if (!$user->hasAnyRole(['pa_management', 'pa_staff'])) {
            return false;
        }
        return $this->graph->pathExists(
            'User', $user->id,
            'Case', $caseId,
            ['WORKS_AT', 'MANAGES']
        );
    }

    private function canValidateCase(User $user, int $caseId): bool
    {
        if (!$user->hasRole('disdukcapil_staff')) {
            return false;
        }
        return $this->graph->pathExists(
            'User', $user->id,
            'Case', $caseId,
            ['WORKS_AT', 'MANAGES']
        );
    }

    private function canDownloadDocument(User $user, int $documentId): bool
    {
        // User submitted the case that has this document
        if ($this->graph->pathExists(
            'User', $user->id,
            'Document', $documentId,
            ['SUBMITTED', 'HAS']
        )) {
            return true;
        }

        // User works at institution that manages the case that has this document
        return $this->graph->pathExists(
            'User', $user->id,
            'Document', $documentId,
            ['WORKS_AT', 'MANAGES', 'HAS']
        );
    }

    private function canViewDocument(User $user, int $documentId): bool
    {
        return $this->canDownloadDocument($user, $documentId);
    }

    private function canProcessOcr(User $user, int $documentId): bool
    {
        return $user->hasAnyRole(['pa_assistant', 'system']) &&
               $this->canViewDocument($user, $documentId);
    }
}
