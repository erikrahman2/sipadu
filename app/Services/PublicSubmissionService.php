<?php

namespace App\Services;

use App\Jobs\SendPublicSubmissionNotification;
use App\Models\CaseModel;
use App\Models\CaseTransition;
use App\Models\Document;
use App\Models\IntegrationQueue;
use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PublicSubmissionService
 *
 * Mengelola pengajuan publik (tanpa akun pengguna):
 *  - Validasi rate-limit per NIK (max 3 kali dalam 15 hari)
 *  - Simpan data pengajuan + dokumen
 *  - Kirim notifikasi Token ke WhatsApp pemohon
 */
class PublicSubmissionService
{
    public function __construct(private readonly WhatsAppService $wa)
    {
    }

    // ── Rate limit ────────────────────────────────────────────────────────────

    /**
     * Periksa apakah NIK masih boleh mengajukan.
     */
    public function isAllowed(?string $nik): bool
    {
        if (empty($nik)) return true;
        return PublicSubmission::countActiveByNik($nik) < PublicSubmission::MAX_SUBMISSIONS;
    }

    /**
     * Sisa kuota pengajuan untuk NIK ini.
     */
    public function remainingQuota(?string $nik): int
    {
        if (empty($nik)) return PublicSubmission::MAX_SUBMISSIONS;
        $used = PublicSubmission::countActiveByNik($nik);
        return max(0, PublicSubmission::MAX_SUBMISSIONS - $used);
    }

    /**
     * Tanggal kapan NIK bisa mengajukan lagi (null = sudah bisa sekarang).
     * Cek berdasarkan pasangan (suami + istri).
     */
    public function nextAllowedDate(?string $nikSuami, ?string $nikIstri): ?\Carbon\Carbon
    {
        if ($this->isAllowed($nikSuami ?? '')) return null;

        $oldest = PublicSubmission::withoutTrashed()
            ->where(function ($query) use ($nikSuami, $nikIstri) {
                if ($nikSuami) {
                    $query->orWhere('nik_suami', $nikSuami)
                          ->orWhere('nik_istri', $nikSuami);
                }
                if ($nikIstri) {
                    $query->orWhere('nik_suami', $nikIstri)
                          ->orWhere('nik_istri', $nikIstri);
                }
            })
            ->where('status', '!=', 'REJECTED')
            ->where('created_at', '>=', now()->subDays(PublicSubmission::LIMIT_DAYS))
            ->oldest()
            ->value('created_at');

        return $oldest
            ? \Carbon\Carbon::parse($oldest)->addDays(PublicSubmission::LIMIT_DAYS)
            : null;
    }

    // ── Buat pengajuan ────────────────────────────────────────────────────────

    /**
     * Proses pengajuan publik baru.
     *
     * @param  array              $data  Data form yang sudah divalidasi
     * @param  UploadedFile[]     $files Array file yang diupload ['type' => UploadedFile]
     * @param  Request            $request Untuk mengambil IP & user-agent
     * @return PublicSubmission
     *
     * @throws \RuntimeException  Jika validasi gagal
     */
    public function create(array $data, array $files, Request $request): PublicSubmission
    {
        $nikSuami = $data['nik_suami'] ?? null;
        $nikIstri = $data['nik_istri'] ?? null;

        // ── Validasi: Kedua NIK harus berbeda ────────────────────────────────
        if ($nikSuami && $nikIstri && $nikSuami === $nikIstri) {
            throw new \RuntimeException(
                'NIK Suami tidak boleh sama dengan NIK Istri.'
            );
        }

        // NOTE: Pembatasan duplicate NIK dihapus untuk memungkinkan testing format dokumen
        // Sistem sekarang bisa menerima pengajuan dengan NIK yang sama berkali-kali

        $submission = DB::transaction(function () use ($data, $files, $request, $nikSuami) {

            Log::info('[DEBUG] PublicSubmission.create() started', [
                'nik_suami' => $nikSuami,
                'institution_id' => $data['institution_id'],
                'files_count' => count($files),
            ]);

            // 1. Normalisasi nomor WA
            $data['phone_wa'] = PublicSubmission::normalizePhone($data['phone_wa']);

            // 2. Simpan pengajuan baru
            /** @var PublicSubmission $submission */
            try {
                // Determine petitioner name (use nama_suami as primary, fallback to nama_istri)
                $petitionerName = $data['nama_suami'] ?? $data['nama_istri'] ?? 'Pemohon';

                $submission = PublicSubmission::create([
                    'nik'               => $data['nik_suami'] ?? $data['nik_istri'] ?? null,
                    'petitioner_name'   => $petitionerName,  // REQUIRED field for database
                    'cerai_type'        => $data['cerai_type'] ?? null,
                    
                    // Data Suami
                    'nik_suami'         => $data['nik_suami'] ?? null,
                    'nama_suami'        => $data['nama_suami'] ?? null,
                    'alamat_suami'      => $data['alamat_suami'] ?? null,
                    'rt_rw_suami'       => $data['rt_rw_suami'] ?? null,
                    'kelurahan_suami'   => $data['kelurahan_suami'] ?? null,
                    'kecamatan_suami'   => $data['kecamatan_suami'] ?? null,

                    // Data Istri
                    'nik_istri'         => $data['nik_istri'] ?? null,
                    'nama_istri'        => $data['nama_istri'] ?? null,
                    'alamat_istri'      => $data['alamat_istri'] ?? null,
                    'rt_rw_istri'       => $data['rt_rw_istri'] ?? null,
                    'kelurahan_istri'   => $data['kelurahan_istri'] ?? null,
                    'kecamatan_istri'   => $data['kecamatan_istri'] ?? null,

                    'phone_wa'          => $data['phone_wa'],
                    'institution_id'    => $data['institution_id'] ?? null,

                    'respondent_name'   => $data['respondent_name'] ?? null,
                    'respondent_nik'    => $data['respondent_nik'] ?? null,
                    'divorce_date'      => $data['divorce_date'] ?? null,
                    'verdict_number'    => $data['verdict_number'] ?? null,
                    'notes'             => $data['notes'] ?? null,

                    'status'            => 'SUBMITTED',  // Public submission status is SUBMITTED, same as PA Assistant cases
                    'is_active'         => true,
                    'ip_address'        => $request->ip(),
                    'user_agent'        => substr($request->userAgent() ?? '', 0, 255),
                ]);

                Log::info('[DEBUG] PublicSubmission created', [
                    'id' => $submission->id,
                    'tracking_token' => $submission->tracking_token,
                ]);

            } catch (\Exception $e) {
                Log::error('[DEBUG] PublicSubmission.create() failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // 3. Simpan dokumen yang diupload
            $hasKtpSuami = false;
            $hasKtpIstri = false;

            foreach ($files as $docType => $file) {
                if (! ($file instanceof UploadedFile) || ! $file->isValid()) continue;

                $allowed = array_keys(PublicSubmissionDocument::$typeLabels);
                $docType = strtoupper($docType);
                if (! in_array($docType, $allowed)) $docType = 'OTHER';

                // Store in public disk so files are accessible via /storage/ URL
                $path = $file->storeAs(
                    'public_submissions/' . $submission->id,
                    $docType . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension(),
                    'public'  // CHANGED: Use 'public' disk instead of 'local'
                );

                PublicSubmissionDocument::create([
                    'public_submission_id' => $submission->id,
                    'document_type'        => $docType,
                    'original_filename'    => $file->getClientOriginalName(),
                    'stored_path'          => $path,
                    'file_size'            => $file->getSize(),
                    'mime_type'            => $file->getMimeType(),
                ]);

                if ($docType === 'KTP_SUAMI') {
                    $hasKtpSuami = true;
                }
                if ($docType === 'KTP_ISTRI') {
                    $hasKtpIstri = true;
                }
            }

            if (! $hasKtpSuami || ! $hasKtpIstri) {
                throw new \RuntimeException('Dokumen KTP suami dan KTP istri wajib diunggah sebelum pengajuan diproses OCR.');
            }

            // 5. Samakan alur dengan PA Assistant: langsung buat case resmi + dokumen standar.
            $submitterId = $this->resolveAutoSubmitterId((int) $submission->institution_id);
            $case = CaseModel::create([
                'submitter_id'          => $submitterId,
                'public_submission_id'  => $submission->id,
                'source_type'           => 'public',
                'institution_id'        => $submission->institution_id,
                'petitioner_nik'        => $submission->nik_suami,
                'petitioner_name'       => $submission->nama_suami,
                'petitioner_phone'      => $submission->phone_wa,
                'petitioner_alamat'     => $submission->alamat_suami,
                'petitioner_rt_rw'      => $submission->rt_rw_suami,
                'petitioner_kelurahan'  => $submission->kelurahan_suami,
                'petitioner_kecamatan'  => $submission->kecamatan_suami,
                'spouse_nik'            => $submission->nik_istri,
                'spouse_name'           => $submission->nama_istri,
                'spouse_alamat'         => $submission->alamat_istri,
                'spouse_rt_rw'          => $submission->rt_rw_istri,
                'spouse_kelurahan'      => $submission->kelurahan_istri,
                'spouse_kecamatan'      => $submission->kecamatan_istri,
                'divorce_date'          => $submission->divorce_date,
                'verdict_number'        => $submission->verdict_number,
                'notes'                 => $submission->notes,
                'status'                => 'SUBMITTED',
                'submitted_at'          => now(),
            ]);

            CaseTransition::create([
                'case_id'         => $case->id,
                'from_state'      => 'DRAFT',
                'to_state'        => 'SUBMITTED',
                'transitioned_by' => $submitterId,
                'reason'          => 'Auto-submit dari pengajuan publik.',
                'metadata'        => ['source' => 'public_submission.create'],
            ]);

            $submission->update(['case_id' => $case->id]);

            // Mirror dokumen publik ke dokumen case agar OCR/review memakai schema yang sama.
            foreach ($submission->documents as $pubDoc) {
                $extension = pathinfo((string) $pubDoc->stored_path, PATHINFO_EXTENSION);
                $storedName = Str::uuid() . ($extension ? '.' . $extension : '');
                $document = Document::create([
                    'case_id'       => $case->id,
                    'uploaded_by'   => $submitterId,
                    'original_name' => $pubDoc->original_filename,
                    'stored_name'   => $storedName,
                    'disk'          => 'public',
                    'path'          => $pubDoc->stored_path,
                    'mime_type'     => $pubDoc->mime_type,
                    'size_bytes'    => (int) $pubDoc->file_size,
                    'document_type' => $this->mapPublicDocumentType($pubDoc->document_type),
                    'checksum'      => file_exists(Storage::disk('public')->path($pubDoc->stored_path))
                        ? hash_file('sha256', Storage::disk('public')->path($pubDoc->stored_path))
                        : null,
                ]);

                event(new \App\Events\DocumentUploaded($document));
            }

            IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $case->id,
                'event_type'     => 'created',
                'payload'        => ['institution_id' => $case->institution_id, 'submitter_id' => $submitterId],
                'available_at'   => now(),
            ]);

            // 6. Kirim notifikasi WA (asynchronous via job queue)
            SendPublicSubmissionNotification::dispatch($submission);

            Log::info('[DEBUG] PublicSubmission.create() completing transaction', [
                'id' => $submission->id,
                'tracking_token' => $submission->tracking_token,
            ]);

            return $submission->fresh(['documents']);
        });

        return $submission;
    }

    private function resolveAutoSubmitterId(int $institutionId): int
    {
        $submitter = User::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['pa_assistant', 'pa_management', 'pa_staff']);
            })
            ->orderBy('id')
            ->first();

        if ($submitter) {
            return $submitter->id;
        }

        $fallback = User::query()->where('status', 'active')->orderBy('id')->first();
        if (! $fallback) {
            throw new \RuntimeException('Tidak ada user aktif untuk submitter otomatis pengajuan publik.');
        }

        return $fallback->id;
    }

    private function mapPublicDocumentType(string $publicType): string
    {
        return DocumentTypeMapper::toCaseType($publicType);
    }

    /**
     * Dispatch OCR processing untuk public submission document.
     */
    private function dispatchOcrForPublicDocument(PublicSubmission $submission, PublicSubmissionDocument $doc): void
    {
        try {
            $fileFullPath = Storage::disk('public')->path($doc->stored_path);
            
            if (!file_exists($fileFullPath)) {
                Log::warning('OCR file not found', [
                    'path' => $fileFullPath,
                    'document_id' => $doc->id,
                ]);
                return;
            }

            // Send to OCR service asynchronously
            dispatch(new \App\Jobs\ProcessPublicSubmissionOcr($submission, $doc));

            Log::info('[OCR] Dispatched OCR job for public submission document', [
                'submission_id' => $submission->id,
                'document_id' => $doc->id,
                'document_type' => $doc->document_type,
                'tracking_token' => $submission->tracking_token,
            ]);

        } catch (\Exception $e) {
            Log::error('[OCR] Failed to dispatch OCR job', [
                'submission_id' => $submission->id,
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── WA Notification ───────────────────────────────────────────────────────

    public function sendWaNotification(PublicSubmission $submission): void
    {
        $trackingUrl = route('public.tracking.token', $submission->tracking_token);
        $message     = $this->wa->templatePengajuanDiterima(
            $submission->petitioner_name,
            $submission->tracking_token,
            $trackingUrl
        );

        $result = $this->wa->send($submission->phone_wa, $message);

        $submission->update([
            'wa_sent_at'   => now(),
            'wa_message_id'=> $result['message_id'],
            'wa_status'    => $result['success'] ? 'sent' : 'failed',
            'wa_error'     => $result['error'],
        ]);

        if (! $result['success']) {
            Log::warning('[PublicSubmission] WA gagal dikirim ke ' . $submission->phone_wa, [
                'submission_id' => $submission->id,
                'error'         => $result['error'],
            ]);
        }
    }

    /**
     * Kirim ulang notifikasi WA jika sebelumnya gagal.
     */
    public function resendWa(PublicSubmission $submission): bool
    {
        SendPublicSubmissionNotification::dispatch($submission);
        return true; // Job dispatched, status akan diupdate setelah job selesai
    }

    // ── Tracking publik ───────────────────────────────────────────────────────

    /**
     * Ambil data pengajuan berdasarkan token (untuk halaman lacak publik).
     */
    public function findByToken(string $token): ?PublicSubmission
    {
        return PublicSubmission::with(['documents'])
            ->where('tracking_token', $token)
            ->withoutTrashed()
            ->first();
    }

    /**
     * Format data tracking untuk respons API/view (sensor data sensitif).
     */
    public function formatTracking(PublicSubmission $sub): array
    {
        return [
            'tracking_token'  => $sub->tracking_token,
            'status'          => $sub->status,
            'status_label'    => $sub->statusLabel(),
            'status_color'    => $sub->statusColor(),
            'petitioner_name' => $sub->petitioner_name,
            // NIK disensor sebagian untuk privasi publik
            'nik'             => $this->maskNik($sub->nik),
            'divorce_date'    => $sub->divorce_date?->translatedFormat('d F Y'),
            'verdict_number'  => $sub->verdict_number,
            'notes'           => $sub->notes,
            'submitted_at'    => $sub->created_at->translatedFormat('d F Y, H:i'),
            'updated_at'      => $sub->updated_at->translatedFormat('d F Y, H:i'),
            'processed_at'    => $sub->processed_at?->translatedFormat('d F Y, H:i'),
            'case_number'     => $sub->case?->case_number,
            'documents_count' => $sub->documents->count(),
            'documents'       => $sub->documents->map(fn($d) => [
                'type'  => $d->document_type,
                'label' => PublicSubmissionDocument::$typeLabels[$d->document_type] ?? $d->document_type,
                'size'  => $d->humanFileSize(),
                'url'   => asset('storage/' . $d->stored_path),          // URL untuk web
                'path'  => $d->stored_path,                             // Path untuk info
                'download' => route('public.tracking.token',  $sub->tracking_token),  // Placeholder
            ])->toArray(),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function maskNik(string $nik): string
    {
        // Tampilkan 4 digit pertama dan 4 terakhir, sisanya bintang
        // Contoh: 3174 xxxxxxxx 0001
        if (strlen($nik) < 8) return str_repeat('*', strlen($nik));
        return substr($nik, 0, 4) . str_repeat('*', strlen($nik) - 8) . substr($nik, -4);
    }
}
