@extends('layouts.admin')

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <!-- Case Info + Stats -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="mb-3 fw-bold">Informasi Kasus</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted" style="width:150px;">No. Kasus</td>
                                <td><strong>{{ $case->case_number }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">NIK Pemohon</td>
                                <td><small><code>{{ $case->petitioner_nik ?? '-' }}</code></small></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nama Pemohon</td>
                                <td>{{ $case->petitioner_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status Kasus</td>
                                <td><span class="badge" style="background-color: #06a8d9; color: white;">{{ config('workflow.states.' . $case->status, $case->status) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal Submit</td>
                                <td>{{ $case->created_at->format('d M Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Validasi</td>
                                <td><strong>{{ $validationStats['total'] }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="mb-3 fw-bold">Statistik Validasi</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Match</span>
                                <strong>{{ $validationStats['match'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Partial Match</span>
                                <strong>{{ $validationStats['partial_match'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Mismatch</span>
                                <strong>{{ $validationStats['mismatch'] }}</strong>
                            </div>
                        </div>

                        <!-- Approve / Reject / Send to Disdukcapil Buttons -->
                        @php
                            $hasUnreviewed = $case->ocrValidations->contains('is_reviewed', false);
                            $allReviewed = $case->ocrValidations->every('is_reviewed', true);
                            $allApproved = $allReviewed && $case->ocrValidations->every('is_approved', true);
                            $anyRejected = $case->ocrValidations->where('is_reviewed', true)->where('is_approved', false)->count() > 0;
                        @endphp

                        <!-- Tombol Aksi Utama - Berdasarkan Status -->
                        @php
                            $isDisdukcapil = $case->status === 'DISDUKCAPIL_VALIDATION';
                            $isRejected = $case->status === 'REJECTED';
                        @endphp

                        @if($isDisdukcapil)
                        <div class="mt-4 pt-3 border-top">
                            <div class="text-center">
                                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                                <h5 class="mt-2 mb-1 text-success">Terkirim ke Disdukcapil</h5>
                                <p class="text-muted small mb-0">Kasus sedang diproses oleh Disdukcapil Staff</p>
                            </div>
                        </div>
                        @elseif($isRejected)
                        <div class="mt-4 pt-3 border-top">
                            <div class="text-center">
                                <i class="fas fa-times-circle text-danger" style="font-size: 48px;"></i>
                                <h5 class="mt-2 mb-1 text-danger">Ditolak</h5>
                                <p class="text-muted small mb-0">Menunggu perbaikan dari PA Assistant</p>
                            </div>
                        </div>
                        @elseif(!$allApproved)
                        <div class="d-flex flex-column gap-2 mt-4 pt-3 border-top">
                            <button type="button" onclick="approveAllValidations()"
                                    class="btn btn-sm w-100"
                                    style="background-color: #28a745; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-paper-plane mr-1"></i> Approve & Kirim ke Disdukcapil
                            </button>
                            <button type="button" onclick="rejectAllValidations()"
                                    class="btn btn-sm w-100"
                                    style="background-color: #dc3545; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-times-circle mr-1"></i> Tolak & Kembalikan ke PA
                            </button>
                        </div>
                        @else
                        <div class="d-flex flex-column gap-2 mt-4 pt-3 border-top">
                            <div class="text-center py-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <span class="ms-2 fw-semibold text-success">Semua Disetujui</span>
                            </div>
                            <button type="button" onclick="approveAllValidations()"
                                    class="btn btn-sm w-100"
                                    style="background-color: #06a8d9; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-paper-plane mr-1"></i> Kirim ke Disdukcapil
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- OCR Validations Section -->
        <div class="mb-3">
            <h6 class="fw-bold" style="font-size: 14px; color: #333;">
                <i style="color: #667eea; "></i>
                Hasil Validasi OCR
            </h6>
        </div>

        @if($case->ocrValidations->isEmpty())
            <div class="alert alert-info">
                <strong>OCR Sedang Diproses...</strong> Belum ada hasil validasi OCR untuk kasus ini.
            </div>
        @else
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; width: 100%;">
                @foreach($case->ocrValidations as $validation)
                    <div style="width: 100%; min-width: 0;">
                        <x-ocr-validation-card :validation="$validation" :case="$case" />
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Documents & Case Data Section -->
        <div class="mb-4">
            <h5 class="mb-3 fw-bold" style="font-size: 15px;">Dokumen yang Diupload</h5>
            
            @php
                $publicDocs = $case->publicSubmission?->documents ?? collect();
                $caseDocs = $case->documents ?? collect();

                // Gunakan dokumen case sebagai sumber utama (sudah jadi schema final).
                $baseDocs = $caseDocs->isNotEmpty() ? $caseDocs : $publicDocs;

                // Dedup by path + filename + type untuk menghindari kartu ganda.
                $displayDocs = $baseDocs
                    ->unique(function ($doc) {
                        $path = $doc->path ?? $doc->stored_path ?? '';
                        $name = $doc->original_name ?? $doc->original_filename ?? '';
                        $type = $doc->document_type ?? '';
                        return strtoupper($type . '|' . $path . '|' . $name);
                    })
                    ->values();
            @endphp
            
            @if($displayDocs->isNotEmpty())
                <div class="row g-3 mb-4" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    @foreach($displayDocs as $doc)
                        <div style="width: 100%; min-width: 0;">
                            @php
                                $isCaseDoc = isset($doc->disk) || isset($doc->path) || isset($doc->original_name);
                                $docType = $doc->document_type ?? '-';
                                $docName = $doc->original_name ?? $doc->original_filename ?? '-';
                                $sizeBytes = $doc->size_bytes ?? $doc->file_size ?? 0;

                                $filePath = '';
                                $fileExists = false;
                                if($isCaseDoc && ($doc->disk ?? null) === 'public' && !empty($doc->path)) {
                                    $normalizedPath = preg_replace('/^public\//', '', $doc->path);
                                    $filePath = 'storage/' . $normalizedPath;
                                    $fileExists = file_exists(storage_path('app/public/' . $normalizedPath));
                                } elseif (!empty($doc->stored_path)) {
                                    $normalizedPath = preg_replace('/^public\//', '', $doc->stored_path);
                                    $filePath = 'storage/' . $normalizedPath;
                                    $fileExists = file_exists(storage_path('app/public/' . $normalizedPath));
                                }

                                $isImage = in_array(strtolower(pathinfo($docName, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                            @endphp
                            @if($filePath !== '' && $fileExists)
                                <a href="{{ asset($filePath) }}" target="_blank" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                    <div class="card shadow-sm" style="height: 100%; overflow: hidden; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow=''">
                                        @if($isImage)
                                            <div style="background: #f0f0f0; height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                <img src="{{ asset($filePath) }}" alt="{{ $doc->original_name }}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                            </div>
                                        @endif
                                        <div class="card-body">
                                            <h6 class="card-title mb-2 badge bg-info" style="font-size: 10px; display: inline-block;">
                                                {{ $docType }}
                                            </h6>
                                            <p class="text-muted mb-1" style="font-size: 12px; word-break: break-all;">{{ $docName }}</p>
                                            <p style="font-size: 11px; color: #6c757d;">Ukuran: {{ number_format($sizeBytes / 1024, 2) }} KB</p>
                                        </div>
                                    </div>
                                </a>
                            @else
                                <div class="card shadow-sm" style="height: 100%; overflow: hidden; background: #f8f9fa;">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2 badge bg-warning" style="font-size: 10px; display: inline-block;">
                                            {{ $docType }}
                                        </h6>
                                        <p class="text-muted mb-1" style="font-size: 12px;">{{ $docName }}</p>
                                        @if($isCaseDoc && ($doc->disk ?? null) !== 'public')
                                            <p style="font-size: 11px; color: #d32f2f;">File lokal - hubungi admin</p>
                                        @else
                                            <p style="font-size: 11px; color: #d32f2f;">File tidak ditemukan</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info small mb-4">
                    Tidak ada dokumen yang diupload untuk kasus ini
                </div>
            @endif
        </div>

        <!-- Case Data Section -->
        <div class="mb-4">
            <h5 class="mb-3 fw-bold" style="font-size: 15px;">Data Perceraian & Kasus</h5>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="mb-3" style="font-size: 13px; color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 8px;">Data Perceraian</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted fw-bold" style="width: 130px;">Nomor Putusan</td>
                                    <td><code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">{{ $case->verdict_number ?? ($case->publicSubmission->verdict_number ?? '-') }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Tanggal Cerai</td>
                                    <td><strong>{{ $case->divorce_date?->format('d M Y') ?? ($case->publicSubmission?->divorce_date?->format('d M Y') ?? '-') }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function approveAllValidations() {
    const caseId = {{ $case->id }};
    const allValidationIds = @json($case->ocrValidations->pluck('id'));

    if (!confirm(`Yakin ingin MENYETUJUI dan MENGIRIM ke DISDUKCAPIL STAFF?\n\nTotal: ${allValidationIds.length} validasi`)) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...';

    Promise.all(allValidationIds.map(id => {
        const formData = new FormData();
        formData.append('validation_id', id);
        formData.append('action', 'approve');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        return fetch(`/dashboard/review/cases/${caseId}/validate`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    }))
    .then(responses => {
        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Mengirim...';

        const formData2 = new FormData();
        formData2.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        return fetch(`/dashboard/review/cases/${caseId}/send-to-disdukcapil`, {
            method: 'POST',
            body: formData2,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    })
    .then(response => response.json())
    .then(data => {
        alert('Berhasil! Kasus dikirim ke DISDUKCAPIL STAFF.');
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Approve & Kirim ke Disdukcapil';
        alert('Gagal memproses.');
    });
}

function rejectAllValidations() {
    const caseId = {{ $case->id }};
    const allValidationIds = @json($case->ocrValidations->pluck('id'));
    const reason = prompt('Masukkan alasan penolakan (akan dikirim ke PA Assistant):');

    if (!reason) return;

    if (!confirm(`Yakin ingin MENOLAK semua validasi?\nKasus akan dikembalikan ke PA ASSISTANT.`)) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...';

    Promise.all(allValidationIds.map(id => {
        const formData = new FormData();
        formData.append('validation_id', id);
        formData.append('action', 'reject');
        formData.append('notes', reason);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        return fetch(`/dashboard/review/cases/${caseId}/validate`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    }))
    .then(responses => {
        alert('Berhasil! Kasus dikembalikan ke PA ASSISTANT.');
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Tolak & Kembalikan ke PA';
        alert('Gagal memproses.');
    });
}
</script>
@endsection
