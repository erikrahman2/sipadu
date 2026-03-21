<?php

namespace App\Services;

use App\Jobs\SendPublicSubmissionNotification;
use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;
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
    public function isAllowed(string $nik): bool
    {
        return PublicSubmission::countActiveByNik($nik) < PublicSubmission::MAX_SUBMISSIONS;
    }

    /**
     * Sisa kuota pengajuan untuk NIK ini.
     */
    public function remainingQuota(string $nik): int
    {
        $used = PublicSubmission::countActiveByNik($nik);
        return max(0, PublicSubmission::MAX_SUBMISSIONS - $used);
    }

    /**
     * Tanggal kapan NIK bisa mengajukan lagi (null = sudah bisa sekarang).
     */
    public function nextAllowedDate(string $nik): ?\Carbon\Carbon
    {
        if ($this->isAllowed($nik)) return null;

        $oldest = PublicSubmission::withoutTrashed()
            ->where('nik', $nik)
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
        $nik = $data['nik'];
        $respondentNik = $data['respondent_nik'] ?? null;

        // ── Validasi 1: NIK pemohon tidak boleh sama dengan NIK pasangan ──────
        if (PublicSubmission::isSameNik($nik, $respondentNik)) {
            throw new \RuntimeException(
                'NIK pemohon tidak boleh sama dengan NIK pasangan.'
            );
        }

        // ── Validasi 2: NIK dibekukan jika sedang dalam proses PA/Disdukcapil ─
        if (PublicSubmission::isNikFrozen($nik)) {
            $reason = PublicSubmission::getFrozenReason($nik);
            
            if ($reason) {
                $statusLabel = match($reason['status']) {
                    'REVIEWING' => 'sedang ditinjau petugas',
                    'WAITING_OCR' => 'dalam proses verifikasi dokumen',
                    'APPROVED' => 'sudah disetujui dan dibuat kasus resmi',
                    'PA_REVIEW' => 'sedang direview oleh Pengadilan Agama',
                    'DISDUKCAPIL_VALIDATION' => 'sedang divalidasi oleh Disdukcapil',
                    default => 'dalam proses'
                };

                $message = "NIK ini tidak dapat mengajukan permohonan baru karena masih ada pengajuan yang {$statusLabel}. ";
                
                if ($reason['type'] === 'public_submission') {
                    $message .= "Token tracking: {$reason['token']}. ";
                } else {
                    $message .= "Nomor kasus: {$reason['case_number']}. ";
                }
                
                $message .= "Silakan tunggu hingga proses selesai atau hubungi kantor terkait untuk informasi lebih lanjut.";
                
                throw new \RuntimeException($message);
            }
        }

        // ── Validasi 3: Pasangan NIK tidak boleh sudah ada di Disdukcapil ─────
        if ($respondentNik && PublicSubmission::hasCoupleInDisdukcapil($nik, $respondentNik)) {
            throw new \RuntimeException(
                'Pasangan NIK ini sudah terdaftar dan sedang/telah diproses oleh Disdukcapil. '
                . 'Silakan hubungi kantor Disdukcapil untuk informasi lebih lanjut.'
            );
        }

        // ── Validasi 4: Rate limit (3x per minggu) ────────────────────────────
        if (! $this->isAllowed($nik)) {
            $next = $this->nextAllowedDate($nik);
            throw new \RuntimeException(
                'NIK ini telah mencapai batas maksimal ' . PublicSubmission::MAX_SUBMISSIONS
                . ' pengajuan dalam ' . PublicSubmission::LIMIT_DAYS . ' hari. '
                . ($next ? 'Anda bisa mengajukan kembali mulai ' . $next->translatedFormat('d F Y') . '.' : '')
            );
        }

        return DB::transaction(function () use ($data, $files, $request, $nik) {

            // 1. Normalisasi nomor WA
            $data['phone_wa'] = PublicSubmission::normalizePhone($data['phone_wa']);

            // 2. Simpan pengajuan baru
            /** @var PublicSubmission $submission */
            $submission = PublicSubmission::create([
                'nik'              => $data['nik'],
                'petitioner_name'  => $data['petitioner_name'],
                'phone_wa'         => $data['phone_wa'],
                'respondent_name'  => $data['respondent_name'] ?? null,
                'respondent_nik'   => $data['respondent_nik'] ?? null,
                'divorce_date'     => $data['divorce_date'] ?? null,
                'verdict_number'   => $data['verdict_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'status'           => 'PENDING',
                'is_active'        => true,
                'ip_address'       => $request->ip(),
                'user_agent'       => substr($request->userAgent() ?? '', 0, 255),
            ]);

            // 3. Replace data lama dengan NIK yang sama (data lama di-soft delete)
            $replaced = PublicSubmission::replaceOldSubmissions($nik, $submission->id);
            if ($replaced > 0) {
                Log::info("PublicSubmission: Replaced {$replaced} old submission(s) for NIK {$nik}", [
                    'new_submission_id' => $submission->id,
                    'nik' => $nik,
                ]);
            }

            // 4. Simpan dokumen yang diupload
            foreach ($files as $docType => $file) {
                if (! ($file instanceof UploadedFile) || ! $file->isValid()) continue;

                $allowed = array_keys(PublicSubmissionDocument::$typeLabels);
                $docType = strtoupper($docType);
                if (! in_array($docType, $allowed)) $docType = 'LAINNYA';

                $path = $file->storeAs(
                    'public_submissions/' . $submission->id,
                    $docType . '_' . time() . '.' . $file->getClientOriginalExtension(),
                    'local'
                );

                PublicSubmissionDocument::create([
                    'public_submission_id' => $submission->id,
                    'document_type'        => $docType,
                    'original_filename'    => $file->getClientOriginalName(),
                    'stored_path'          => $path,
                    'file_size'            => $file->getSize(),
                    'mime_type'            => $file->getMimeType(),
                ]);
            }

            // 5. Kirim notifikasi WA (asynchronous via job queue)
            SendPublicSubmissionNotification::dispatch($submission);

            return $submission->fresh(['documents']);
        });
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
