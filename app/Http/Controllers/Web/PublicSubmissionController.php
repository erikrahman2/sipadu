<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;
use App\Services\PublicSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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

        $quota        = $nik ? $this->service->remainingQuota($nik) : null;
        $nextDate     = ($nik && $quota === 0) ? $this->service->nextAllowedDate($nik) : null;
        $docTypes     = PublicSubmissionDocument::$typeLabels;
        $institutions = Institution::active()->orderBy('name')->get(['id', 'name', 'type']);
        $maxFiles     = config('public_submission.max_files_per_type', 3);
        $maxSizeMb    = config('public_submission.max_file_size_mb', 5);

        return view('pengajuan.publik', compact(
            'nik', 'quota', 'nextDate', 'docTypes', 'institutions', 'maxFiles', 'maxSizeMb'
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
        $maxSizeByte = (config('public_submission.max_file_size_mb', 5) * 1024);
        
        // Clean phone number: remove +62, spaces, and dashes
        $phoneWa = $request->input('phone_wa', '');
        $phoneWa = preg_replace('/^(\+62|0)/', '', $phoneWa);  // Remove +62 or leading 0
        $phoneWa = preg_replace('/\s+/', '', $phoneWa);       // Remove spaces
        $phoneWa = preg_replace('/[-.]/', '', $phoneWa);      // Remove dashes/dots
        
        $request->merge(['phone_wa' => $phoneWa]);
        
        $validated = $request->validate([
            // Data Suami
            'nik_suami'           => 'required|digits:16',
            'nama_suami'          => 'required|string|max:255',
            'alamat_suami'        => 'required|string|max:255',
            'rt_rw_suami'         => 'required|string|max:10',
            'kelurahan_suami'     => 'required|string|max:100',
            'kecamatan_suami'     => 'required|string|max:100',

            // Data Istri
            'nik_istri'           => 'required|digits:16',
            'nama_istri'          => 'required|string|max:255',
            'alamat_istri'        => 'required|string|max:255',
            'rt_rw_istri'         => 'required|string|max:10',
            'kelurahan_istri'     => 'required|string|max:100',
            'kecamatan_istri'     => 'required|string|max:100',

            // Kontak & Institusi
            'phone_wa'            => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id'      => 'required|exists:institutions,id',

            // Data Cerai (opsional)
            'respondent_name'     => 'nullable|string|max:255',
            'respondent_nik'      => 'nullable|digits:16',
            'divorce_date'        => 'nullable|date|before_or_equal:today',
            'verdict_number'      => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:1000',

            // Dokumen
            'documents'                => 'required|array|min:2',
            'documents.KTP_SUAMI'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,
            'documents.KTP_ISTRI'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,
            'documents.AKTA_CERAI'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,
            'documents.PUTUSAN_PA'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,
            'documents.AKTA_NIKAH'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,
            'documents.SURAT_PENGANTAR' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,
            'documents.OTHER'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte,

            'agreement'           => 'required|accepted',
        ], [
            'nik_suami.required'        => 'NIK Suami wajib diisi.',
            'nik_suami.digits'          => 'NIK Suami harus tepat 16 digit angka.',
            'nama_suami.required'       => 'Nama Suami wajib diisi.',
            'alamat_suami.required'     => 'Alamat Suami wajib diisi.',
            'rt_rw_suami.required'      => 'RT/RW Suami wajib diisi.',
            'kelurahan_suami.required'  => 'Kelurahan Suami wajib diisi.',
            'kecamatan_suami.required'  => 'Kecamatan Suami wajib diisi.',

            'nik_istri.required'        => 'NIK Istri wajib diisi.',
            'nik_istri.digits'          => 'NIK Istri harus tepat 16 digit angka.',
            'nama_istri.required'       => 'Nama Istri wajib diisi.',
            'alamat_istri.required'     => 'Alamat Istri wajib diisi.',
            'rt_rw_istri.required'      => 'RT/RW Istri wajib diisi.',
            'kelurahan_istri.required'  => 'Kelurahan Istri wajib diisi.',
            'kecamatan_istri.required'  => 'Kecamatan Istri wajib diisi.',

            'phone_wa.required'         => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'            => 'Nomor WhatsApp tidak valid. Masukkan hanya angka (9–15 digit) tanpa +62 atau simbol lainnya. Contoh: 812345678 atau 08123456789',
            'institution_id.required'   => 'Pilih Pengadilan Agama/Institusi.',
            'institution_id.exists'     => 'Institusi yang dipilih tidak valid.',

            'documents.KTP_SUAMI.required'  => 'Dokumen KTP Suami wajib diunggah.',
            'documents.KTP_ISTRI.required'  => 'Dokumen KTP Istri wajib diunggah.',
            'documents.*.mimes'             => 'Format dokumen harus JPG, PNG, atau PDF.',
            'documents.*.max'               => 'Ukuran dokumen maksimal ' . config('public_submission.max_file_size_mb', 5) . ' MB.',

            'agreement.required'        => 'Anda harus menyetujui pernyataan kebenaran data.',
            'agreement.accepted'        => 'Anda harus menyetujui pernyataan kebenaran data.',
        ]);

        try {
            Log::info('[DEBUG] PublicSubmission.store() - About to call service->create()', [
                'nik_suami' => $validated['nik_suami'] ?? null,
                'nik_istri' => $validated['nik_istri'] ?? null,
            ]);

            $files      = $request->file('documents', []);
            $submission = $this->service->create($validated, $files, $request);

            Log::info('[DEBUG] PublicSubmission.store() - Service returned submission', [
                'id' => $submission->id,
                'tracking_token' => $submission->tracking_token,
            ]);

            return redirect()->route('public.submit.success', $submission->tracking_token);

        } catch (\RuntimeException $e) {
            Log::error('[VALIDATION ERROR]', ['message' => $e->getMessage()]);
            return back()
                ->withInput()
                ->withErrors(['submission' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('[UNEXPECTED ERROR]', ['message' => $e->getMessage(), 'type' => get_class($e)]);
            return back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan: ' . $e->getMessage()]);
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
