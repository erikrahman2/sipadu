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

        // Link User to Institution with WORKS_AT
        if ($user->institution_id) {
            $graph->linkUserToInstitution($user->id, $user->institution_id);
        }

        // Super Admin links to BOTH institutions (PA and Disdukcapil)
        if ($user->hasRole('super_admin')) {
            \App\Models\Institution::all()->each(function ($inst) use ($graph, $user) {
                $graph->linkSuperAdminToInstitution($user->id, $inst->id);
            });
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

        // Link submitter to Case based on their role
        if ($case->submitter_id) {
            $submitter = \App\Models\User::find($case->submitter_id);
            if ($submitter) {
                $submitterRelType = $this->determineRelationshipType($submitter, $case->status, $case->source_type);
                $this->createUserCaseRelationship($graph, $submitter, $case, $submitterRelType);
            }
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

            // Determine relationship type based on role and case status
            $relType = $this->determineRelationshipType($user, $case->status, $case->source_type);

            // Call specific relationship method based on role
            $this->createUserCaseRelationship($graph, $user, $case, $relType);
        }

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
     * Create specific relationship based on user role and case
     */
    private function createUserCaseRelationship(GraphService $graph, \App\Models\User $user, \App\Models\CaseModel $case, string $relType): void
    {
        // PA Assistant relationships
        if ($user->hasRole('pa_assistant')) {
            if ($relType === 'DRAFT') {
                $graph->linkPaAssistantDraft($user->id, $case->id);
            } elseif ($relType === 'DITOLAK') {
                $graph->linkPaAssistantRejected($user->id, $case->id);
            } elseif ($relType === 'SUBMITTED') {
                $graph->linkUserToCase($user->id, $case->id, 'SUBMITTED');
            }
            return;
        }

        // PA Management relationships
        if ($user->hasRole('pa_management')) {
            if ($relType === 'MENUNGGU_REVIEW') {
                $graph->linkPaManagementReview($user->id, $case->id);
            } elseif ($relType === 'PENGAJUAN_PUBLIC') {
                $graph->linkPaManagementPublic($user->id, $case->id);
            } elseif ($relType === 'PENGAJUAN_PA') {
                $graph->linkPaManagementPa($user->id, $case->id);
            } elseif ($relType === 'KIRIM_VALIDASI') {
                // Find disdukcapil staff and link
                $discUser = \App\Models\User::role('disdukcapil_staff')->first();
                if ($discUser) {
                    $graph->linkPaToDisdukcapil($user->id, $discUser->id, $case->id);
                }
            } else {
                $graph->linkUserToCase($user->id, $case->id, $relType);
            }
            return;
        }

        // Disdukcapil Staff relationships
        if ($user->hasRole('disdukcapil_staff')) {
            if ($relType === 'VERIFIKASI') {
                $graph->linkDisdukcapilVerification($user->id, $case->id);
            } elseif ($relType === 'SELESAI') {
                $graph->linkDisdukcapilCompleted($user->id, $case->id);
            }
            return;
        }

        // PA Staff relationships
        if ($user->hasRole('pa_staff')) {
            if ($relType === 'ARSIP') {
                $graph->linkPaStaffArchived($user->id, $case->id);
            }
            return;
        }

        // Default: RELATED_TO
        $graph->linkUserRelatedToCase($user->id, $case->id);
    }

    /**
     * Determine relationship type based on user role and case status
     */
    private function determineRelationshipType(\App\Models\User $user, string $caseStatus, string $sourceType = null): string
    {
        $roles = $user->getRoleNames()->toArray();

        // PA Assistant - DRAFT or REJECTED cases
        if (in_array('pa_assistant', $roles)) {
            if ($caseStatus === 'DRAFT') {
                return 'DRAFT';
            }
            if ($caseStatus === 'REJECTED') {
                return 'DITOLAK';
            }
            if (in_array($caseStatus, ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW'])) {
                return 'SUBMITTED';
            }
        }

        // PA Management - Pengajuan Public & PA
        if (in_array('pa_management', $roles)) {
            if ($caseStatus === 'PA_REVIEW') {
                return 'MENUNGGU_REVIEW';
            }
            if ($sourceType === 'public') {
                return 'PENGAJUAN_PUBLIC';
            }
            if (in_array($sourceType, ['internal', 'manual'])) {
                return 'PENGAJUAN_PA';
            }
            if ($caseStatus === 'DISDUKCAPIL_VALIDATION') {
                return 'KIRIM_VALIDASI';
            }
        }

        // Disdukcapil Staff - Verification
        if (in_array('disdukcapil_staff', $roles)) {
            if ($caseStatus === 'DISDUKCAPIL_VALIDATION') {
                return 'VERIFIKASI';
            }
            if ($caseStatus === 'COMPLETED') {
                return 'SELESAI';
            }
        }

        // PA Staff - Archive
        if (in_array('pa_staff', $roles)) {
            if (in_array($caseStatus, ['COMPLETED', 'ARCHIVED'])) {
                return 'ARSIP';
            }
        }

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

        // PA Management gets MANAGES relationship for PA_REVIEW and beyond
        if (in_array($case->status, ['PA_REVIEW', 'DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)) {
            \App\Models\User::role('pa_management')
                ->where('status', 'active')
                ->get()
                ->each(function (\App\Models\User $user) use ($graph, $case) {
                    $graph->linkUserToCase($user->id, $case->id, 'MANAGES');
                });
        }

        // Disdukcapil Staff gets VERIFY relationship for DISDUKCAPIL_VALIDATION
        if (in_array($case->status, ['DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'], true)) {
            \App\Models\User::role('disdukcapil_staff')
                ->where('status', 'active')
                ->get()
                ->each(function (\App\Models\User $user) use ($graph, $case) {
                    if ($case->status === 'DISDUKCAPIL_VALIDATION') {
                        $graph->linkDisdukcapilVerification($user->id, $case->id);
                    } else {
                        $graph->linkDisdukcapilCompleted($user->id, $case->id);
                    }
                });
        }

        // PA Staff gets ARSIP relationship for COMPLETED/ARCHIVED
        if (in_array($case->status, ['COMPLETED', 'ARCHIVED'], true)) {
            \App\Models\User::role('pa_staff')
                ->where('status', 'active')
                ->get()
                ->each(function (\App\Models\User $user) use ($graph, $case) {
                    $graph->linkPaStaffArchived($user->id, $case->id);
                });
        }
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
