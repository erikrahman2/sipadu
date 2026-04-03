<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Institution;
use App\Models\PublicSubmission;
use App\Services\PublicSubmissionService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PublicSubmissionStaffController
 *
 * Halaman dashboard untuk petugas/admin mengelola pengajuan publik.
 * Diakses di bawah route dashboard.public-inbox.*
 */
class PublicSubmissionStaffController extends Controller
{
    public function __construct(
        private readonly PublicSubmissionService $service,
        private readonly WhatsAppService $wa,
    ) {
    }

    // ── Daftar pengajuan masuk ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = PublicSubmission::with('documents')
            ->withoutTrashed()
            ->latest();

        // Filter status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter NIK/nama
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nik', 'like', "%{$search}%")
                  ->orWhere('petitioner_name', 'like', "%{$search}%")
                  ->orWhere('tracking_token', 'like', "%{$search}%");
            });
        }

        $submissions = $query->paginate(20)->withQueryString();
        $counts      = PublicSubmission::withoutTrashed()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard.public-inbox.index', compact('submissions', 'counts'));
    }

    // ── Detail pengajuan ──────────────────────────────────────────────────────

    public function show(int $id)
    {
        $submission  = PublicSubmission::with('documents', 'processor', 'case')->findOrFail($id);
        $institutions = Institution::orderBy('name')->get();

        return view('dashboard.public-inbox.show', compact('submission', 'institutions'));
    }

    // ── Ubah status (SUBMITTED → REVIEWING) ────────────────────────────────────

    public function startReview(int $id)
    {
        $submission = PublicSubmission::findOrFail($id);

        if ($submission->status !== 'SUBMITTED') {
            return back()->with('error', 'Pengajuan ini bukan status SUBMITTED.');
        }

        $submission->update([
            'status'       => 'REVIEWING',
            'processed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Status diperbarui ke "Sedang Ditinjau".');
    }

    // ── Setujui: buat kasus resmi ─────────────────────────────────────────────

    public function approve(Request $request, int $id)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $submission = PublicSubmission::findOrFail($id);

        if (! in_array($submission->status, ['SUBMITTED', 'REVIEWING'])) {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($submission, $request) {
            // Buat kasus resmi dari pengajuan publik
            $case = CaseModel::create([
                'submitter_id'      => Auth::id(),
                'institution_id'    => $request->institution_id,
                'spouse_name'       => $submission->respondent_name,
                'spouse_nik'        => $submission->respondent_nik,
                'divorce_date'      => $submission->divorce_date,
                'verdict_number'    => $submission->verdict_number,
                'notes'             => $submission->notes . ($request->notes ? "\n[Staff: {$request->notes}]" : ''),
                'status'            => 'SUBMITTED',
                'submitted_at'      => now(),
            ]);

            // Tandai pengajuan publik sebagai disetujui
            $submission->update([
                'status'        => 'APPROVED',
                'case_id'       => $case->id,
                'processed_by'  => Auth::id(),
                'processed_at'  => now(),
            ]);

            // Kirim notifikasi WA perubahan status
            $fresh   = $submission->fresh();
            $message = $this->wa->templateStatusBerubah(
                $fresh->petitioner_name,
                'Disetujui — Kasus resmi telah dibuat dengan nomor ' . $case->case_number,
                $fresh->tracking_token,
                route('public.tracking.token', $fresh->tracking_token)
            );
            $result = $this->wa->send($fresh->phone_wa, $message);
            $fresh->update([
                'wa_sent_at'    => now(),
                'wa_message_id' => $result['message_id'],
                'wa_status'     => $result['success'] ? 'sent' : 'failed',
                'wa_error'      => $result['error'],
            ]);
        });

        return redirect()
            ->route('dashboard.public-inbox.show', $id)
            ->with('success', 'Pengajuan disetujui. Kasus resmi telah dibuat.');
    }

    // ── Tolak pengajuan ───────────────────────────────────────────────────────

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $submission = PublicSubmission::findOrFail($id);

        if ($submission->status === 'APPROVED' || $submission->status === 'COMPLETED') {
            return back()->with('error', 'Pengajuan yang sudah disetujui tidak bisa ditolak.');
        }

        $submission->update([
            'status'        => 'REJECTED',
            'notes'         => ($submission->notes ?? '') . "\n[Alasan Penolakan: {$request->reject_reason}]",
            'processed_by'  => Auth::id(),
            'processed_at'  => now(),
        ]);

        // Kirim notifikasi WA
        $message = $this->wa->templateStatusBerubah(
            $submission->petitioner_name,
            'Ditolak — ' . $request->reject_reason,
            $submission->tracking_token,
            route('public.tracking.token', $submission->tracking_token)
        );
        $this->wa->send($submission->phone_wa, $message);

        return back()->with('success', 'Pengajuan ditolak dan notifikasi WA dikirim.');
    }

    // ── Kirim ulang WA ────────────────────────────────────────────────────────

    public function resendWa(int $id)
    {
        $submission = PublicSubmission::findOrFail($id);
        $ok = $this->service->resendWa($submission);

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Notifikasi WhatsApp berhasil dikirim ulang.' : 'Gagal mengirim WhatsApp. Cek log.'
        );
    }
}
