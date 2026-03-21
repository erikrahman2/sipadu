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
        $user = \App\Models\User::find($item->aggregate_id);
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
        if ($item->event_type === 'updated' && isset($payload['institution_id'])) {
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
        $case = \App\Models\CaseModel::find($item->aggregate_id);
        if (!$case) return;

        $graph->upsertCase([
            'id'             => $case->id,
            'case_number'    => $case->case_number,
            'tracking_token' => $case->tracking_token,
            'status'         => $case->status,
        ]);

        $graph->linkUserToCase($case->submitter_id, $case->id, 'SUBMITTED');
        $graph->linkInstitutionToCase($case->institution_id, $case->id);
    }

    private function syncDocument(IntegrationQueue $item, GraphService $graph, array $payload): void
    {
        if ($item->event_type === 'deleted') {
            $graph->deleteNode('Document', $item->aggregate_id);
            return;
        }
        $doc = \App\Models\Document::find($item->aggregate_id);
        if (!$doc) return;

        $graph->upsertDocument([
            'id'            => $doc->id,
            'document_type' => $doc->document_type,
            'status'        => $doc->status,
            'case_id'       => $doc->case_id,
        ]);

        $graph->linkCaseToDocument($doc->case_id, $doc->id);
    }
}
