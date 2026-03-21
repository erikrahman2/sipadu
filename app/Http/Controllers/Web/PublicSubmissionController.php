<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;
use App\Services\PublicSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * PublicSubmissionController (Web)
 *
 * Mengurus halaman publik pengajuan pembaruan dokumen pasca perceraian.
 * Tidak memerlukan autentikasi.
 */
class PublicSubmissionController extends Controller
{
    public function __construct(private readonly PublicSubmissionService $service)
    {
    }

    // ── Halaman form pengajuan ────────────────────────────────────────────────

    public function create(Request $request)
    {
        $nik = $request->query('nik');

        $quota     = $nik ? $this->service->remainingQuota($nik) : null;
        $nextDate  = ($nik && $quota === 0) ? $this->service->nextAllowedDate($nik) : null;
        $docTypes  = PublicSubmissionDocument::$typeLabels;
        $maxFiles  = config('public_submission.max_files_per_type', 3);
        $maxSizeMb = config('public_submission.max_file_size_mb', 5);

        return view('pengajuan.publik', compact(
            'nik', 'quota', 'nextDate', 'docTypes', 'maxFiles', 'maxSizeMb'
        ));
    }

    // ── Cek kuota NIK (AJAX) ─────────────────────────────────────────────────

    public function checkNik(Request $request)
    {
        $request->validate([
            'nik' => 'required|digits:16',
        ]);

        $nik      = $request->input('nik');
        $quota    = $this->service->remainingQuota($nik);
        $nextDate = $quota === 0 ? $this->service->nextAllowedDate($nik) : null;

        return response()->json([
            'allowed'    => $quota > 0,
            'remaining'  => $quota,
            'next_date'  => $nextDate?->translatedFormat('d F Y'),
            'max'        => PublicSubmission::MAX_SUBMISSIONS,
            'days'       => PublicSubmission::LIMIT_DAYS,
        ]);
    }

    // ── Proses pengajuan ──────────────────────────────────────────────────────

    public function store(Request $request)
    {
        // Validasi input form
        $validated = $request->validate([
            'nik'             => 'required|digits:16',
            'petitioner_name' => 'required|string|max:255',
            'phone_wa'        => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'respondent_name' => 'nullable|string|max:255',
            'respondent_nik'  => 'nullable|digits:16',
            'divorce_date'    => 'nullable|date|before_or_equal:today',
            'verdict_number'  => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:1000',

            // Dokumen (setidaknya KTP wajib)
            'documents'              => 'required|array|min:1',
            'documents.KTP'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),
            'documents.KK'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),
            'documents.AKTA_CERAI'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),
            'documents.PUTUSAN_PA'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),
            'documents.SURAT_NIKAH'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),
            'documents.FOTO_DIRI'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),
            'documents.LAINNYA'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . (config('public_submission.max_file_size_mb', 5) * 1024),

            'agreement' => 'required|accepted',
        ], [
            'nik.required'              => 'NIK wajib diisi.',
            'nik.digits'                => 'NIK harus tepat 16 digit angka.',
            'petitioner_name.required'  => 'Nama lengkap wajib diisi.',
            'phone_wa.required'         => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'            => 'Nomor WhatsApp hanya boleh berisi angka (9–15 digit).',
            'documents.KTP.required'    => 'Dokumen KTP wajib diunggah.',
            'documents.*.mimes'         => 'Format dokumen harus JPG, PNG, atau PDF.',
            'documents.*.max'           => 'Ukuran dokumen maksimal ' . config('public_submission.max_file_size_mb', 5) . ' MB.',
            'agreement.required'        => 'Anda harus menyetujui pernyataan kebenaran data.',
            'agreement.accepted'        => 'Anda harus menyetujui pernyataan kebenaran data.',
        ]);

        try {
            $files      = $request->file('documents', []);
            $submission = $this->service->create($validated, $files, $request);

            return redirect()->route('public.submit.success', $submission->tracking_token);

        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['nik' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan teknis. Silakan coba beberapa saat lagi.']);
        }
    }

    // ── Halaman sukses ────────────────────────────────────────────────────────

    public function success(string $token)
    {
        $submission = $this->service->findByToken($token);

        if (! $submission) {
            abort(404, 'Pengajuan tidak ditemukan.');
        }

        return view('pengajuan.sukses', compact('submission'));
    }
}
