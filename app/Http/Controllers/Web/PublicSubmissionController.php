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
        $nextDate     = ($nik && $quota === 0) ? $this->service->nextAllowedDate($nik, null) : null;
        $docTypes     = PublicSubmissionDocument::$typeLabels;
        $ceraiOptions = $this->ceraiOptions();
        $institutions = Institution::active()->orderBy('name')->get(['id', 'name', 'type']);
        $maxFiles     = config('public_submission.max_files_per_type', 3);
        $maxSizeMb    = config('public_submission.max_file_size_mb', 5);

        return view('pengajuan.publik', compact(
            'nik', 'quota', 'nextDate', 'docTypes', 'ceraiOptions', 'institutions', 'maxFiles', 'maxSizeMb'
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
        $nextDate = $quota === 0 ? $this->service->nextAllowedDate($nik, null) : null;

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
        $maxSizeByte = (config('public_submission.max_file_size_mb', 5) * 1024);
        $ceraiOptions = $this->ceraiOptions();
        $ceraiType = $request->input('cerai_type');
        $isGroupedFlow = is_string($ceraiType) && array_key_exists($ceraiType, $ceraiOptions);

        // Clean phone number: remove +62, spaces, and dashes
        $phoneWa = $request->input('phone_wa', '');
        $phoneWa = preg_replace('/^(\+62|0)/', '', $phoneWa);
        $phoneWa = preg_replace('/\s+/', '', $phoneWa);
        $phoneWa = preg_replace('/[-.]/', '', $phoneWa);

        $request->merge(['phone_wa' => $phoneWa]);

        $rules = [
            'nik_suami'       => 'required|digits:16',
            'nama_suami'      => 'required|string|max:255',
            'alamat_suami'    => 'required|string|max:255',
            'rt_rw_suami'     => 'required|string|max:10',
            'kelurahan_suami' => 'required|string|max:100',
            'kecamatan_suami' => 'required|string|max:100',

            'nik_istri'       => 'required|digits:16',
            'nama_istri'      => 'required|string|max:255',
            'alamat_istri'    => 'required|string|max:255',
            'rt_rw_istri'     => 'required|string|max:10',
            'kelurahan_istri' => 'required|string|max:100',
            'kecamatan_istri' => 'required|string|max:100',

            'phone_wa'        => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id'  => 'required|exists:institutions,id',

            'respondent_name' => 'nullable|string|max:255',
            'respondent_nik'  => 'nullable|digits:16',
            'divorce_date'    => 'nullable|date|before_or_equal:today',
            'verdict_number'  => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:1000',

            'agreement'       => 'required|accepted',
        ];

        $messages = [
            'nik_suami.required'       => 'NIK Suami wajib diisi.',
            'nik_suami.digits'         => 'NIK Suami harus tepat 16 digit angka.',
            'nama_suami.required'      => 'Nama Suami wajib diisi.',
            'alamat_suami.required'    => 'Alamat Suami wajib diisi.',
            'rt_rw_suami.required'     => 'RT/RW Suami wajib diisi.',
            'kelurahan_suami.required' => 'Kelurahan Suami wajib diisi.',
            'kecamatan_suami.required' => 'Kecamatan Suami wajib diisi.',

            'nik_istri.required'       => 'NIK Istri wajib diisi.',
            'nik_istri.digits'         => 'NIK Istri harus tepat 16 digit angka.',
            'nama_istri.required'      => 'Nama Istri wajib diisi.',
            'alamat_istri.required'    => 'Alamat Istri wajib diisi.',
            'rt_rw_istri.required'     => 'RT/RW Istri wajib diisi.',
            'kelurahan_istri.required' => 'Kelurahan Istri wajib diisi.',
            'kecamatan_istri.required' => 'Kecamatan Istri wajib diisi.',

            'phone_wa.required'        => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'           => 'Nomor WhatsApp tidak valid. Masukkan hanya angka (9–15 digit) tanpa +62 atau simbol lainnya. Contoh: 812345678 atau 08123456789',
            'institution_id.required'  => 'Pilih Pengadilan Agama/Institusi.',
            'institution_id.exists'    => 'Institusi yang dipilih tidak valid.',

            'agreement.required'       => 'Anda harus menyetujui pernyataan kebenaran data.',
            'agreement.accepted'       => 'Anda harus menyetujui pernyataan kebenaran data.',

            'documents.*.mimes'        => 'Format dokumen harus JPG, PNG, atau PDF.',
            'documents.*.max'          => 'Ukuran dokumen maksimal ' . config('public_submission.max_file_size_mb', 5) . ' MB.',
        ];

        if ($isGroupedFlow) {
            $rules['cerai_type'] = 'required|in:' . implode(',', array_keys($ceraiOptions));

            $requiredDocs = $this->documentsForCeraiType($ceraiType);
            $rules['documents'] = 'required|array|min:' . count($requiredDocs);

            // Get required doc types for this cerai type
            $requiredDocTypes = $ceraiOptions[$ceraiType]['required'] ?? $requiredDocs;

            // Only required documents are mandatory
            foreach ($requiredDocTypes as $documentType) {
                $rules['documents.' . $documentType] = 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
                $messages['documents.' . $documentType . '.required'] = 'Dokumen ' . ($ceraiOptions[$ceraiType]['docs'][$documentType] ?? $documentType) . ' wajib diunggah.';
            }
            // Optional documents are... optional (nullable)
            foreach ($ceraiOptions[$ceraiType]['docs'] as $docKey => $docLabel) {
                if (!in_array($docKey, $requiredDocTypes)) {
                    $rules['documents.' . $docKey] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
                }
            }
        } else {
            $rules['documents'] = 'required|array|min:2';
            $rules['documents.KTP_SUAMI'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.KTP_ISTRI'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.AKTA_CERAI'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.PUTUSAN_PA'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.AKTA_NIKAH'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.SURAT_PENGANTAR'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.OTHER'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;

            $messages['documents.KTP_SUAMI.required'] = 'Dokumen KTP Suami wajib diunggah.';
            $messages['documents.KTP_ISTRI.required'] = 'Dokumen KTP Istri wajib diunggah.';
        }

        // DEBUG: Log what was received
        Log::info('[DEBUG] Documents received', [
            'cerai_type' => $request->input('cerai_type'),
            'isGroupedFlow' => $isGroupedFlow,
            'files_keys' => array_keys($request->file('documents', [])),
            'files_count' => count($request->file('documents', [])),
        ]);

        $validated = $request->validate($rules, $messages);

        Log::info('[DEBUG] Validation passed', [
            'validated_docs' => array_keys($validated['documents'] ?? []),
        ]);

        if (! $isGroupedFlow) {
            $validated['cerai_type'] = null;
        }

        try {
            Log::info('[DEBUG] PublicSubmission.store() - About to call service->create()', [
                'nik_suami' => $validated['nik_suami'] ?? null,
                'nik_istri' => $validated['nik_istri'] ?? null,
                'cerai_type' => $validated['cerai_type'] ?? null,
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

    private function ceraiOptions(): array
    {
        // Required documents - wajib diupload (hanya KTP)
        $requiredDocs = [
            'KTP_SUAMI'   => 'Upload KTP Suami',
            'KTP_ISTRI'   => 'Upload KTP Istri',
        ];

        // Optional documents - opsional, boleh kosong
        $optionalDocs = [
            'KK'          => 'Upload Kartu Keluarga (KK)',
            'PUTUSAN_PA'  => 'Upload Putusan Pengadilan',
            'AKTA_CERAI'  => 'Upload Akta Cerai',
            'AKTA_NIKAH'  => 'Upload Akta Kawin / Buku Nikah',
        ];

        $allDocs = $requiredDocs + $optionalDocs;

        return [
            'cerai_normal' => [
                'label'       => 'Cerai Normal',
                'description' => 'Untuk pembaruan dokumen standar setelah putusan cerai.',
                'docs'        => $allDocs,
                'required'    => array_keys($requiredDocs),
            ],
            'cerai_mati' => [
                'label'       => 'Cerai Mati',
                'description' => 'Untuk pembaruan dokumen ketika pasangan meninggal dunia.',
                'docs'        => $allDocs + [
                    'AKTA_KEMATIAN'                => 'Upload Akta Kematian',
                    'SURAT_KETERANGAN_AHLI_WARIS' => 'Upload Surat Keterangan Ahli Waris',
                ],
                'required' => array_keys($requiredDocs),
            ],
            'cerai_pindah' => [
                'label'       => 'Cerai Pindah',
                'description' => 'Untuk pembaruan dokumen ketika ada perubahan domisili disertai surat pindah.',
                'docs'        => $allDocs + [
                    'SURAT_PINDAH' => 'Upload Surat Pindah',
                ],
                'required' => array_keys($requiredDocs),
            ],
            'cerai_ghaib' => [
                'label'       => 'Cerai Ghaib (Kehilangan)',
                'description' => 'Untuk pembaruan dokumen ketika pasangan tidak diketahui keberadaannya.',
                'docs'        => $allDocs + [
                    'SURAT_KETERANGAN_GHAIB' => 'Upload Surat Keterangan Ghaib',
                ],
                'required' => array_keys($requiredDocs),
            ],
            'cerai_hak_asuh' => [
                'label'       => 'Cerai Terkait Hak Asuh Anak',
                'description' => 'Untuk pembaruan dokumen yang berkaitan dengan penetapan hak asuh anak.',
                'docs'        => $allDocs + [
                    'AKTA_KELAHIRAN_ANAK' => 'Upload Akta Kelahiran Anak',
                ],
                'required' => array_keys($requiredDocs),
            ],
        ];
    }

    private function documentsForCeraiType(string $ceraiType): array
    {
        $options = $this->ceraiOptions();

        // Return required docs only (not all docs)
        return $options[$ceraiType]['required']
            ?? array_keys($options['cerai_normal']['docs'] ?? []);
    }
}
