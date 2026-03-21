<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\GraphSyncJob;
use App\Models\IntegrationQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /sync/graph   – trigger manual sync (admin only)
    // ─────────────────────────────────────────────────────────────────────────

    public function triggerSync(): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('super_admin')) {
            abort(403, 'Hanya super admin yang bisa memicu sync manual.');
        }

        $pending = IntegrationQueue::pending()->count();
        $failed  = IntegrationQueue::failed()->count();

        dispatch(new GraphSyncJob())->onQueue('graph');

        return response()->json([
            'message'         => 'Graph sync job dijadwalkan.',
            'pending_items'   => $pending,
            'retryable_items' => $failed,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /sync/status
    // ─────────────────────────────────────────────────────────────────────────

    public function status(): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('super_admin')) {
            abort(403);
        }

        return response()->json([
            'pending'    => IntegrationQueue::where('status', 'PENDING')->count(),
            'processing' => IntegrationQueue::where('status', 'PROCESSING')->count(),
            'success'    => IntegrationQueue::where('status', 'SUCCESS')->count(),
            'failed'     => IntegrationQueue::where('status', 'FAILED')->count(),
        ]);
    }
}
