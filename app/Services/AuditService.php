<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Write a structured audit log entry.
     */
    public function log(
        ?User   $user,
        string  $action,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        ?array  $oldValues   = null,
        ?array  $newValues   = null,
        ?Request $request    = null
    ): AuditLog {
        $req = $request ?? request();

        $entry = AuditLog::create([
            'user_id'      => $user?->id,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'ip_address'   => $req->ip(),
            'user_agent'   => $req->userAgent(),
        ]);

        Log::channel('audit')->info('AUDIT', [
            'user_id'   => $user?->id,
            'action'    => $action,
            'subject'   => "{$subjectType}:{$subjectId}",
            'ip'        => $req->ip(),
        ]);

        return $entry;
    }

    /**
     * Log an API access event.
     */
    public function logAccess(Request $request, int $statusCode, int $responseMs): void
    {
        \App\Models\AccessLog::create([
            'user_id'          => optional(auth())->id(),
            'ip_address'       => $request->ip(),
            'method'           => $request->method(),
            'path'             => $request->path(),
            'status_code'      => $statusCode,
            'response_time_ms' => $responseMs,
            'user_agent'       => $request->userAgent(),
        ]);
    }
}
