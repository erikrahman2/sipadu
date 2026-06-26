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

        // Log decision (safely handle if policy channel unavailable)
        try {
            $channel = Log::channel('policy');
            if ($channel) {
                $channel->info('ReBAC decision', [
                    'user_id'    => $user->id,
                    'action'     => $action,
                    'resource'   => "{$resourceType}:{$resourceId}",
                    'decision'   => $decision ? 'PERMIT' : 'DENY',
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail if logging unavailable
        }

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
        if ($case && $user->hasRole('disdukcapil_staff') && $case->status === 'DISDUKCAPIL_VALIDATION') {
            return true;
        }

        if ($case && $case->submitter_id === $user->id) {
            return true;
        }

        // Check if user is assigned as operator (PA or Disdukcapil)
        if ($case && ($case->assigned_pa_user_id === $user->id || $case->assigned_disdukcapil_user_id === $user->id)) {
            return true;
        }

        // Check if user works at same institution (both must be non-null)
        if ($case && $case->institution_id !== null && $user->institution_id !== null && $case->institution_id === $user->institution_id) {
            return true;
        }

        // Try Neo4j graph traversal
        try {
            // PERMIT if user submitted the case
            if ($this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['SUBMITTED']
            )) {
                return true;
            }

            // PERMIT if user is assigned as verification operator
            if ($this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['VERIFY_OPERATOR']
            )) {
                return true;
            }

            // PERMIT if user works at the institution that has the case
            return $this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['WORKS_AT', 'HAS']
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
        // Only submitter in DRAFT state can edit
        $case = \App\Models\CaseModel::find($caseId);
        
        // Must be DRAFT status
        if (!$case || $case->status !== 'DRAFT') {
            return false;
        }
        
        // Must be the submitter
        if ($case->submitter_id !== $user->id) {
            return false;
        }
        
        // Check Neo4j path as confirmation
        try {
            return $this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['SUBMITTED']
            );
        } catch (\Exception $e) {
            \Log::warning('Neo4j graph check failed for edit', [
                'case_id' => $caseId,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            // Fallback: already checked submitter_id and DRAFT status
            return $case->submitter_id === $user->id;
        }
    }

    private function canApproveCase(User $user, int $caseId): bool
    {
        if (!$user->hasAnyRole(['pa_management', 'pa_staff'])) {
            return false;
        }
        
        // Try Neo4j: check if user is assigned as PA operator OR works at institution with case
        try {
            // PERMIT if user is assigned as verification operator
            if ($this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['VERIFY_OPERATOR']
            )) {
                return true;
            }

            // PERMIT if user works at institution that has case
            return $this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['WORKS_AT', 'HAS']
            );
        } catch (\Exception $e) {
            \Log::warning('Neo4j graph check failed for approve', [
                'case_id' => $caseId,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function canValidateCase(User $user, int $caseId): bool
    {
        if (!$user->hasRole('disdukcapil_staff')) {
            return false;
        }
        
        // Fallback: check MySQL directly if Neo4j unavailable
        $case = \App\Models\CaseModel::find($caseId);
        if ($case) {
            // PERMIT if case is in DISDUKCAPIL_VALIDATION status (Disdukcapil Staff can validate)
            if ($case->status === 'DISDUKCAPIL_VALIDATION') {
                return true;
            }
            
            // PERMIT if user is assigned as Disdukcapil operator
            if ($case->assigned_disdukcapil_user_id === $user->id) {
                return true;
            }
            
            // PERMIT if user works at Disdukcapil institution
            if ($case->institution_id !== null && $user->institution_id !== null && $case->institution_id === $user->institution_id) {
                return true;
            }
        }
        
        // Try Neo4j: check if user is assigned as Disdukcapil operator
        try {
            // PERMIT if user is assigned as verification operator
            if ($this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['VERIFY_OPERATOR']
            )) {
                return true;
            }

            // PERMIT if user works at institution that has case
            return $this->graph->pathExists(
                'User', $user->id,
                'Case', $caseId,
                ['WORKS_AT', 'HAS']
            );
        } catch (\Exception $e) {
            \Log::warning('Neo4j graph check failed for validate', [
                'case_id' => $caseId,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            // Fallback already handled above - return based on MySQL check
            return false;
        }
    }

    private function canDownloadDocument(User $user, int $documentId): bool
    {
        // User submitted the case that has this document
        try {
            if ($this->graph->pathExists(
                'User', $user->id,
                'Document', $documentId,
                ['SUBMITTED', 'HAS_DOCUMENT']
            )) {
                return true;
            }

            // User is assigned as operator on case that has this document
            if ($this->graph->pathExists(
                'User', $user->id,
                'Document', $documentId,
                ['VERIFY_OPERATOR', 'HAS_DOCUMENT']
            )) {
                return true;
            }

            // User works at institution that has the case that has this document
            return $this->graph->pathExists(
                'User', $user->id,
                'Document', $documentId,
                ['WORKS_AT', 'HAS', 'HAS_DOCUMENT']
            );
        } catch (\Exception $e) {
            \Log::warning('Neo4j graph check failed for document download', [
                'document_id' => $documentId,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
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
