<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CaseStatusChanged;

/**
 * Workflow Engine – state-machine driven case lifecycle management.
 *
 * States:
 *   DRAFT → SUBMITTED → OCR_PROCESSED → PA_REVIEW
 *        → DISDUKCAPIL_VALIDATION → COMPLETED → ARCHIVED
 *   Any → REJECTED → DRAFT
 */
class WorkflowService
{
    public function __construct(
        private readonly OCRService   $ocrService,
        private readonly AuditService $audit
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Transition Gate
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Perform a state transition with full ACID guarantee.
     */
    public function transition(
        CaseModel $case,
        string    $toState,
        User      $actor,
        ?string   $reason = null,
        array     $metadata = []
    ): CaseModel {
        if (!$case->canTransitionTo($toState)) {
            throw new \DomainException(
                "Transition [{$case->status}] → [{$toState}] tidak diizinkan."
            );
        }

        $this->assertRoleAllowed($case->status, $toState, $actor);

        DB::transaction(function () use ($case, $toState, $actor, $reason, $metadata) {
            $fromState = $case->status;

            $case->update([
                'status'       => $toState,
                'submitted_at' => $toState === 'SUBMITTED' ? now() : $case->submitted_at,
                'completed_at' => in_array($toState, ['COMPLETED', 'ARCHIVED']) ? now() : $case->completed_at,
            ]);

            CaseTransition::create([
                'case_id'          => $case->id,
                'from_state'       => $fromState,
                'to_state'         => $toState,
                'transitioned_by'  => $actor->id,
                'reason'           => $reason,
                'metadata'         => $metadata,
            ]);

            // Outbox event for graph sync
            \App\Models\IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $case->id,
                'event_type'     => 'status_changed',
                'payload'        => [
                    'from'  => $fromState,
                    'to'    => $toState,
                    'by'    => $actor->id,
                ],
                'available_at' => now(),
            ]);
        });

        $this->audit->log(
            $actor,
            "case.{$toState}",
            CaseModel::class,
            $case->id,
            ['status' => $case->getOriginal('status')],
            ['status' => $toState, 'reason' => $reason]
        );

        $this->sendNotifications($case, $toState);

        Log::channel('workflow')->info('Case transitioned', [
            'case_id'   => $case->id,
            'from'      => $case->getOriginal('status'),
            'to'        => $toState,
            'actor_id'  => $actor->id,
        ]);

        return $case->refresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Convenience wrappers
    // ─────────────────────────────────────────────────────────────────────────

    public function submit(CaseModel $case, User $actor): CaseModel
    {
        return $this->transition($case, 'SUBMITTED', $actor);
    }

    public function markOcrProcessed(CaseModel $case, User $actor): CaseModel
    {
        return $this->transition($case, 'OCR_PROCESSED', $actor);
    }

    public function sendToPaReview(CaseModel $case, User $actor, ?string $note = null): CaseModel
    {
        return $this->transition($case, 'PA_REVIEW', $actor, $note);
    }

    public function sendToDisdukcapil(CaseModel $case, User $actor, ?string $note = null): CaseModel
    {
        return $this->transition($case, 'DISDUKCAPIL_VALIDATION', $actor, $note);
    }

    public function complete(CaseModel $case, User $actor): CaseModel
    {
        return $this->transition($case, 'COMPLETED', $actor);
    }

    public function reject(CaseModel $case, User $actor, string $reason): CaseModel
    {
        return $this->transition($case, 'REJECTED', $actor, $reason);
    }

    public function archive(CaseModel $case, User $actor): CaseModel
    {
        return $this->transition($case, 'ARCHIVED', $actor);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function assertRoleAllowed(string $from, string $to, User $actor): void
    {
        $key   = "{$from}->{$to}";
        $roles = config("workflow.transition_roles.{$key}", []);

        if (!empty($roles) && !in_array('system', $roles) && !$actor->hasAnyRole($roles)) {
            throw new \DomainException(
                "User tidak memiliki role yang diizinkan untuk transisi ini."
            );
        }
    }

    private function sendNotifications(CaseModel $case, string $toState): void
    {
        $targets = config("workflow.notifications.{$toState}", []);
        if (empty($targets)) {
            return;
        }

        if (in_array('submitter', $targets) && $case->submitter) {
            $case->submitter->notify(new CaseStatusChanged($case));
        }
    }
}
