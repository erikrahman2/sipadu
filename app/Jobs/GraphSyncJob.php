<?php

namespace App\Jobs;

use App\Models\GraphSyncLog;
use App\Models\IntegrationQueue;
use App\Services\GraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GraphSyncJob – reads integration_queue outbox and syncs to Neo4j.
 *
 * Implements idempotent upsert with exponential backoff on failure.
 */
class GraphSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 60;

    // ─────────────────────────────────────────────────────────────────────────

    public function handle(GraphService $graph): void
    {
        $batch = IntegrationQueue::pending()
            ->orWhere(fn($q) => $q->failed())
            ->orderBy('available_at')
            ->limit(50)
            ->get();

        foreach ($batch as $item) {
            $this->processItem($item, $graph);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function processItem(IntegrationQueue $item, GraphService $graph): void
    {
        $item->markProcessing();
        $start = microtime(true);

        try {
            $this->sync($item, $graph);

            $item->markSuccess();

            \App\Models\GraphSyncLog::create([
                'queue_id'       => $item->id,
                'operation'      => 'UPSERT_NODE',
                'label_or_rel'   => $item->aggregate_type,
                'success'        => true,
                'duration_ms'    => (int)((microtime(true) - $start) * 1000),
            ]);

        } catch (\Throwable $e) {
            $item->markFailed($e->getMessage());

            \App\Models\GraphSyncLog::create([
                'queue_id'     => $item->id,
                'operation'    => 'UPSERT_NODE',
                'label_or_rel' => $item->aggregate_type,
                'success'      => false,
                'error'        => $e->getMessage(),
                'duration_ms'  => (int)((microtime(true) - $start) * 1000),
            ]);

            Log::channel('graph')->error('GraphSync failed for item', [
                'queue_id'  => $item->id,
                'type'      => $item->aggregate_type,
                'event'     => $item->event_type,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function sync(IntegrationQueue $item, GraphService $graph): void
    {
        $payload = $item->payload;

        match ($item->aggregate_type) {
            'User' => $this->syncUser($item, $graph, $payload),
            'Institution' => $this->syncInstitution($item, $graph, $payload),
            'Case' => $this->syncCase($item, $graph, $payload),
            'Document' => $this->syncDocument($item, $graph, $payload),
            default => Log::channel('graph')->warning('Unknown aggregate type', ['type' => $item->aggregate_type]),
        };
    }

    private function syncUser(IntegrationQueue $item, GraphService $graph, array $payload): void
    {
        if ($item->event_type === 'deleted') {
            $graph->deleteNode('User', $item->aggregate_id);
            return;
        }

        $user = \App\Models\User::withTrashed()->find($item->aggregate_id);
        if (!$user) return;

        $graph->upsertUser([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'institution_id' => $user->institution_id,
        ]);

        if ($user->institution_id) {
            $graph->linkUserToInstitution($user->id, $user->institution_id);
        }

        // Invalidate ReBAC cache when user relationships change
        if (in_array($item->event_type, ['updated', 'restored']) && isset($payload['institution_id'])) {
            \App\Models\CaseModel::where('institution_id', $user->institution_id)
                ->orWhere('submitter_id', $user->id)
                ->each(function ($case) use ($user) {
                    app(\App\Services\ReBACService::class)->invalidateCache($user, 'Case', $case->id);
                });
        }
    }

    private function syncInstitution(IntegrationQueue $item, GraphService $graph, array $payload): void
    {
        if ($item->event_type === 'deleted') {
            $graph->deleteNode('Institution', $item->aggregate_id);
            return;
        }
        $inst = \App\Models\Institution::find($item->aggregate_id);
        if (!$inst) return;

        $graph->upsertInstitution([
            'id'   => $inst->id,
            'code' => $inst->code,
            'name' => $inst->name,
            'type' => $inst->type,
        ]);
    }

    private function syncCase(IntegrationQueue $item, GraphService $graph, array $payload): void
    {
        if ($item->event_type === 'deleted') {
            $graph->deleteNode('Case', $item->aggregate_id);
            return;
        }

        // Use withTrashed() to find soft-deleted cases that are being restored
        $case = \App\Models\CaseModel::withTrashed()->find($item->aggregate_id);
        if (!$case) return;

        $graph->upsertCase([
            'id'             => $case->id,
            'case_number'    => $case->case_number,
            'tracking_token' => $case->tracking_token,
            'status'         => $case->status,
        ]);

        // Link Institution to Case with HAS relationship
        if ($case->institution_id) {
            $graph->linkInstitutionToCase($case->institution_id, $case->id);
        }

        // Link User to Case - submitter with SUBMITTED relationship
        if ($case->submitter_id) {
            $graph->linkUserToCase($case->submitter_id, $case->id, 'SUBMITTED');
        }

        // ─── Link ALL users involved in case transitions ─────────────────────
        $transitions = \App\Models\CaseTransition::where('case_id', $case->id)->get();
        $allInvolvedUserIds = collect();

        foreach ($transitions as $transition) {
            $user = \App\Models\User::find($transition->transitioned_by);
            if (!$user) continue;

            $allInvolvedUserIds->push($transition->transitioned_by);

            // CRITICAL: Upsert user node FIRST to ensure it exists before linking
            $graph->upsertUser([
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'institution_id' => $user->institution_id,
            ]);

            // Determine relationship type based on role and transition
            $relType = $this->determineRelationshipType($user, $transition->to_state);
            
            if ($relType === 'VERIFY_OPERATOR') {
                $graph->linkUserAsVerifyOperator($user->id, $case->id);
            } else {
                $graph->linkUserToCase($user->id, $case->id, $relType);
            }
        }

        // Also link all involved users with RELATED_TO relationship for access control
        $allInvolvedUserIds->unique()->each(function($userId) use ($graph, $case) {
            $graph->linkUserRelatedToCase($userId, $case->id);
        });

        $this->linkHandledUsersToCase($graph, $case);

        // Invalidate ReBAC cache when case is restored or updated
        if (in_array($item->event_type, ['updated', 'restored'])) {
            $allInvolvedUserIds->unique()->each(function($userId) use ($case) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    app(\App\Services\ReBACService::class)->invalidateCache($user, 'Case', $case->id);
                }
            });
        }
    }

    /**
     * Determine relationship type based on user role and transition state
     */
    private function determineRelationshipType(\App\Models\User $user, string $toState): string
    {
        $roles = $user->getRoleNames()->toArray();

        // Disdukcapil staff verification
        if (in_array('disdukcapil_staff', $roles) && 
            in_array($toState, ['DISDUKCAPIL_VALIDATION', 'COMPLETED'])) {
            return 'VERIFY_OPERATOR';
        }

        // PA Management reviewing and approving
        if (in_array('pa_management', $roles) && 
            in_array($toState, ['PA_REVIEW', 'COMPLETED'])) {
            return 'WORKS_ON';
        }

        // PA Staff involved in processing
        if (in_array('pa_staff', $roles) && 
            in_array($toState, ['PA_REVIEW', 'COMPLETED'])) {
            return 'WORKS_ON';
        }

        // PA Assistant processing
        if (in_array('pa_assistant', $roles)) {
            return 'RELATED_TO';
        }

        // Default
        return 'RELATED_TO';
    }

    /**
     * Link users to cases they actually handled in the application history.
     */
    private function linkHandledUsersToCase(GraphService $graph, \App\Models\CaseModel $case): void
    {
        $handledUserIds = collect([
            $case->submitter_id,
            $case->assigned_pa_user_id,
            $case->assigned_disdukcapil_user_id,
        ]);

        $handledUserIds = $handledUserIds
            ->merge(\App\Models\CaseTransition::where('case_id', $case->id)->pluck('transitioned_by'))
            ->merge(\App\Models\OcrValidation::where('case_id', $case->id)->pluck('reviewed_by'))
            ->merge(\App\Models\Document::where('case_id', $case->id)->pluck('uploaded_by'))
            ->filter()
            ->unique()
            ->values();

        if (in_array($case->status, ['PA_REVIEW', 'DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)) {
            $handledUserIds = $handledUserIds
                ->merge(\App\Models\User::role('pa_management')->where('status', 'active')->pluck('id'))
                ->unique()
                ->values();
        }

        foreach ($handledUserIds as $userId) {
            $user = \App\Models\User::find((int) $userId);
            if ($user && $user->hasRole('pa_management')) {
                $graph->linkUserToCase($user->id, $case->id, 'MANAGES');
            } else {
                $graph->linkUserRelatedToCase((int) $userId, $case->id);
            }
        }

        \App\Models\User::role('disdukcapil_staff')
            ->where('status', 'active')
            ->get()
            ->each(function (\App\Models\User $user) use ($graph, $case) {
                if (
                    $case->assigned_disdukcapil_user_id === $user->id ||
                    in_array($case->status, ['DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)
                ) {
                    $graph->linkUserAsVerifyOperator($user->id, $case->id);
                }
            });
    }

    private function syncDocument(IntegrationQueue $item, GraphService $graph, array $payload): void
    {
        if ($item->event_type === 'deleted') {
            $graph->deleteNode('Document', $item->aggregate_id);
            return;
        }

        // Use withTrashed() to find soft-deleted documents that are being restored
        $doc = \App\Models\Document::withTrashed()->find($item->aggregate_id);
        if (!$doc) return;

        $graph->upsertDocument([
            'id'            => $doc->id,
            'document_type' => $doc->document_type,
            'status'        => $doc->status,
            'case_id'       => $doc->case_id,
        ]);

        // Link Case to Document
        if ($doc->case_id) {
            $graph->linkCaseToDocument($doc->case_id, $doc->id);
        }

        // Invalidate ReBAC cache for all users who can access this case's documents
        if (in_array($item->event_type, ['updated', 'restored']) && $doc->case_id) {
            $case = \App\Models\CaseModel::find($doc->case_id);
            if ($case && $case->submitter_id) {
                $user = \App\Models\User::find($case->submitter_id);
                if ($user) {
                    app(\App\Services\ReBACService::class)->invalidateCache($user, 'Document', $doc->id);
                }
            }
        }
    }
}
