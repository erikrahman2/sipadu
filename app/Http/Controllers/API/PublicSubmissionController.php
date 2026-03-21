<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PublicSubmission;
use App\Services\PublicSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PublicSubmissionController (API)
 *
 * Endpoint API untuk pengajuan publik dan tracking.
 * Semua endpoint bersifat publik (tanpa auth).
 */
class PublicSubmissionController extends Controller
{
    public function __construct(private readonly PublicSubmissionService $service)
    {
    }

    // ── Cek kuota NIK ─────────────────────────────────────────────────────────

    public function checkNik(Request $request): JsonResponse
    {
        $request->validate(['nik' => 'required|digits:16']);

        $nik   = $request->input('nik');
        $quota = $this->service->remainingQuota($nik);

        return response()->json([
            'allowed'   => $quota > 0,
            'remaining' => $quota,
            'max'       => PublicSubmission::MAX_SUBMISSIONS,
            'days'      => PublicSubmission::LIMIT_DAYS,
            'next_date' => $quota === 0
                ? $this->service->nextAllowedDate($nik)?->toDateString()
                : null,
        ]);
    }

    // ── Buat pengajuan baru ───────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $maxKb = config('public_submission.max_file_size_mb', 5) * 1024;

        $validated = $request->validate([
            'nik'             => 'required|digits:16',
            'petitioner_name' => 'required|string|max:255',
            'phone_wa'        => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'respondent_name' => 'nullable|string|max:255',
            'respondent_nik'  => 'nullable|digits:16',
            'divorce_date'    => 'nullable|date|before_or_equal:today',
            'verdict_number'  => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:1000',
            'documents.KTP'         => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
            'documents.KK'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
            'documents.AKTA_CERAI'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
            'documents.PUTUSAN_PA'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
            'documents.SURAT_NIKAH' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
            'documents.FOTO_DIRI'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
            'documents.LAINNYA'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxKb,
        ]);

        try {
            $files      = $request->file('documents', []);
            $submission = $this->service->create($validated, $files, $request);

            return response()->json([
                'message'        => 'Pengajuan berhasil diterima.',
                'tracking_token' => $submission->tracking_token,
                'status'         => $submission->status,
                'wa_status'      => $submission->wa_status,
                'tracking_url'   => route('public.tracking.token', $submission->tracking_token),
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    // ── Tracking berdasarkan token ────────────────────────────────────────────

    public function track(string $token): JsonResponse
    {
        $submission = $this->service->findByToken($token);

        if (! $submission) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 404);
        }

        return response()->json($this->service->formatTracking($submission));
    }
}
