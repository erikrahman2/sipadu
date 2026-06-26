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
                    </div>
                </div>
            </div>
        </div>

        <!-- Final Action Section -->
        @php
            $totalValidations = $case->ocrValidations->count();
            $pendingReviewCount = $case->ocrValidations->where('is_reviewed', false)->count();
            
            // Jika tidak ada validasi sama sekali, berarti OCR masih processing
            if ($totalValidations === 0) {
                $finalActionState = 'processing';
            } else {
                $finalActionState = match($case->status) {
                    'DISDUKCAPIL_VALIDATION' => 'sent',
                    'NEEDS_REVISION' => 'rejected',
                    default => $pendingReviewCount > 0 ? 'pending' : 'approved',
                };
            }
        @endphp

        <div id="final-action-card" class="card shadow-sm mb-4 border-0"
             style="background: {{ $finalActionState === 'rejected' ? 'linear-gradient(135deg, #fff1f1 0%, #ffffff 100%)' : ($finalActionState === 'processing' || $finalActionState === 'sent' ? 'linear-gradient(135deg, #edf9f0 0%, #ffffff 100%)' : 'linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%)') }};">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <i id="final-action-icon"
                       class="fas {{ $finalActionState === 'processing' ? 'fa-spinner fa-spin' : ($finalActionState === 'rejected' ? 'fa-times-circle' : ($finalActionState === 'sent' ? 'fa-check-circle' : 'fa-check-double')) }}"
                       style="font-size: 18px; color: {{ $finalActionState === 'processing' ? '#ffc107' : ($finalActionState === 'rejected' ? '#dc3545' : ($finalActionState === 'sent' ? '#28a745' : '#667eea')) }}; margin-right: 8px;"></i>
                    <h6 id="final-action-title" class="mb-0 fw-bold" style="font-size: 15px;">
                        @if($finalActionState === 'processing')
                            OCR Sedang Diproses
                        @elseif($finalActionState === 'rejected')
                            Kasus Ditolak
                        @elseif($finalActionState === 'sent')
                            Data Sudah Dikirim ke Disdukcapil
                        @else
                            Tindakan Final Kasus (Global)
                        @endif
                    </h6>
                </div>
                <p id="final-action-description" class="text-muted small mb-4" style="line-height: 1.5;">
                    @if($finalActionState === 'processing')
                        Sistem sedang memproses OCR untuk dokumen yang diupload. Tunggu hingga selesai sebelum melakukan tindakan.
                    @elseif($finalActionState === 'rejected')
                        Kasus ini sudah ditolak dan tidak dapat diproses lebih lanjut. Menunggu tindak lanjut dari PA Assistant.
                    @elseif($finalActionState === 'sent')
                        Data sudah dikirim kepada pihak Disdukcapil dan tidak ada item pending review.
                    @else
                        Approve atau Reject di sini akan diterapkan ke <strong>semua data KTP/dokumen</strong> yang belum direview pada kasus ini.
                    @endif
                </p>

                @if($finalActionState === 'pending')
                    <div id="final-action-buttons" class="d-flex gap-2 flex-wrap">
                        <button id="approve-all-btn" class="btn btn-success btn-lg" 
                                onclick="approveAll(this)"
                                style="flex: 1; min-width: 150px; font-weight: 600; padding: 10px 24px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);">
                            <i class="fas fa-thumbs-up"></i> Approve Semua
                        </button>
                        <button id="reject-all-btn" class="btn btn-danger btn-lg" 
                                onclick="rejectAll(this)"
                                style="flex: 1; min-width: 150px; font-weight: 600; padding: 10px 24px; background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); border: none; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);">
                            <i class="fas fa-times-circle"></i> Reject Semua
                        </button>
                    </div>
                @else
                    <div class="alert {{ $finalActionState === 'processing' ? 'alert-info' : ($finalActionState === 'sent' ? 'alert-success' : 'alert-warning') }} mb-0">
                        @if($finalActionState === 'processing')
                            Silakan tunggu OCR selesai diproses. Halaman akan otomatis direfresh ketika ada hasil.
                        @elseif($finalActionState === 'sent')
                            Tidak ada tombol aksi lagi karena data sudah diteruskan ke Disdukcapil.
                        @else
                            Tidak ada tombol aksi lagi karena kasus sudah ditolak dan menunggu tindak lanjut.
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- OCR Validations Section -->
        <div class="mb-3">
            <h6 class="fw-bold" style="font-size: 14px; color: #333;">
                <i class="fas fa-file-image" style="color: #667eea; margin-right: 8px;"></i>
                Hasil Validasi OCR
            </h6>
        </div>

        <!-- OCR Validations - Card Grid Layout (2 Columns - Fixed) -->
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
            <h5 class="mb-3 fw-bold" style="font-size: 15px;">📄 Dokumen yang Diupload</h5>
            
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
                                                📋 {{ $docType }}
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
                                            📋 {{ $docType }}
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
                    📁 Tidak ada dokumen yang diupload untuk kasus ini
                </div>
            @endif
        </div>

        <!-- Case Data Section -->
        <div class="mb-4">
            <h5 class="mb-3 fw-bold" style="font-size: 15px;">📋 Data Perceraian & Kasus</h5>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="mb-3" style="font-size: 13px; color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 8px;">⚖️ Data Perceraian</h6>
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
function toggleDetail(validationId) {
    const detail = document.getElementById(`detail-${validationId}`);
    const icon = document.getElementById(`chevron-${validationId}`);
    
    if (detail.style.display === 'none') {
        detail.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        detail.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

async function approveAll(buttonElement) {
    const caseId = {{ $case->id }};
    const unreviewed = document.querySelectorAll('[data-is-reviewed="false"]');
    
    if (unreviewed.length === 0) {
        alert('Tidak ada validasi yang menunggu review');
        return;
    }
    
    const confirmAction = window.confirm(`Approve semua ${unreviewed.length} validasi OCR untuk kasus ini?\n\nData akan langsung dikirim ke Disdukcapil setelah approval.`);
    if (!confirmAction) return;

    updateFinalActionState('approving', unreviewed.length);
    
    try {
        const originalText = buttonElement.innerHTML;
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        let approved = 0;
        let failed = 0;
        
        for (const element of unreviewed) {
            const validationId = element.getAttribute('data-validation-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            try {
                const response = await fetch(`/dashboard/review/cases/${caseId}/validate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        validation_id: parseInt(validationId),
                        action: 'approve',
                        notes: 'Bulk approve by PA Management'
                    })
                });
                
                const data = await response.json();
                if (response.ok) {
                    approved++;
                    element.setAttribute('data-is-reviewed', 'true');
                } else {
                    console.error('API response error:', data);
                    failed++;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                failed++;
            }
        }
        
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalText;
        
        if (failed === 0) {
            // Semuanya berhasil di-approve, sekarang kirim ke Disdukcapil
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim ke Disdukcapil...';
            
            try {
                const sendResponse = await fetch(`/dashboard/review/cases/${caseId}/send-to-disdukcapil`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (sendResponse.ok) {
                    updateFinalActionState('sent', approved);
                    alert(`✓ Berhasil approve ${approved} validasi OCR\n✓ Data sudah dikirim ke Disdukcapil\n\nHalaman akan di-refresh...`);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    const errorData = await sendResponse.json();
                    console.error('Send error:', errorData);
                    updateFinalActionState('idle');
                    alert('⚠ Approval berhasil tapi gagal mengirim ke Disdukcapil:\n' + (errorData.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Send fetch error:', error);
                updateFinalActionState('idle');
                alert('⚠ Approval berhasil tapi gagal mengirim ke Disdukcapil:\n' + error.message);
            }
        } else {
            updateFinalActionState('idle');
            alert(`⚠ Berhasil: ${approved}, Gagal: ${failed}\n\nTidak semua validation berhasil di-approve.`);
        }
    } catch (error) {
        console.error('Fatal error in approveAll:', error);
        updateFinalActionState('idle');
        alert('❌ Terjadi kesalahan: ' + error.message);
    }
}

async function rejectAll(buttonElement) {
    const caseId = {{ $case->id }};
    const unreviewed = document.querySelectorAll('[data-is-reviewed="false"]');
    
    if (unreviewed.length === 0) {
        alert('Tidak ada validasi yang menunggu review');
        return;
    }
    
    const reason = prompt(`Reject semua ${unreviewed.length} validasi OCR?\n\nMasukkan alasan reject (optional):`);
    if (reason === null) return;

    updateFinalActionState('rejecting', unreviewed.length);
    
    try {
        const originalText = buttonElement.innerHTML;
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        let rejected = 0;
        let failed = 0;
        
        for (const element of unreviewed) {
            const validationId = element.getAttribute('data-validation-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            try {
                const response = await fetch(`/dashboard/review/cases/${caseId}/validate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        validation_id: parseInt(validationId),
                        action: 'reject',
                        notes: reason || 'Bulk reject by PA Management'
                    })
                });
                
                const data = await response.json();
                if (response.ok) {
                    rejected++;
                    element.setAttribute('data-is-reviewed', 'true');
                } else {
                    console.error('API response error:', data);
                    failed++;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                failed++;
            }
        }
        
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalText;
        
        if (failed === 0) {
            updateFinalActionState('rejected', rejected);
            alert(`✓ Berhasil reject ${rejected} validasi OCR\n• Status kasus: NEEDS_REVISION\n• Notifikasi dikirim ke pemohon & PA Assistant\n\nHalaman akan di-refresh...`);
            setTimeout(() => location.reload(), 1000);
        } else {
            updateFinalActionState('idle');
            alert(`⚠ Berhasil: ${rejected}, Gagal: ${failed}`);
        }
    } catch (error) {
        console.error('Fatal error in rejectAll:', error);
        updateFinalActionState('idle');
        alert('❌ Terjadi kesalahan: ' + error.message);
    }
}

function submitValidationAction(validationId, action) {
    let confirmMsg = '';
    let modalId = '';
    
    switch(action) {
        case 'approve':
            confirmMsg = 'Approve validasi OCR ini?';
            modalId = 'approveModal';
            break;
        case 'reject':
            confirmMsg = 'Reject validasi OCR ini?';
            modalId = 'rejectModal';
            break;
        case 'request_correction':
            confirmMsg = 'Request koreksi untuk validasi ini?';
            modalId = 'correctionModal';
            break;
    }
    
    if (confirm(confirmMsg)) {
        const modal = document.getElementById(`${modalId}${validationId}`);
        if (modal) {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    }
}

function updateFinalActionState(state, count = null) {
    const card = document.getElementById('final-action-card');
    const icon = document.getElementById('final-action-icon');
    const title = document.getElementById('final-action-title');
    const description = document.getElementById('final-action-description');
    const approveBtn = document.getElementById('approve-all-btn');
    const rejectBtn = document.getElementById('reject-all-btn');

    if (!card || !icon || !title || !description || !approveBtn || !rejectBtn) return;

    const resetButtons = () => {
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        approveBtn.style.opacity = '1';
        rejectBtn.style.opacity = '1';
        approveBtn.style.pointerEvents = 'auto';
        rejectBtn.style.pointerEvents = 'auto';
    };

    if (state === 'approving') {
        card.style.background = 'linear-gradient(135deg, #edf9f0 0%, #ffffff 100%)';
        icon.className = 'fas fa-spinner fa-spin';
        icon.style.color = '#28a745';
        title.textContent = 'Sedang Approve Semua';
        description.innerHTML = count !== null
            ? `Menerapkan approve ke <strong>${count}</strong> validasi yang belum direview.`
            : 'Menerapkan approve ke semua validasi yang belum direview.';
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        return;
    }

    if (state === 'rejecting') {
        card.style.background = 'linear-gradient(135deg, #fff1f1 0%, #ffffff 100%)';
        icon.className = 'fas fa-spinner fa-spin';
        icon.style.color = '#dc3545';
        title.textContent = 'Sedang Reject Semua';
        description.innerHTML = count !== null
            ? `Menerapkan reject ke <strong>${count}</strong> validasi yang belum direview.`
            : 'Menerapkan reject ke semua validasi yang belum direview.';
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        return;
    }

    if (state === 'approved') {
        card.style.background = 'linear-gradient(135deg, #edf9f0 0%, #ffffff 100%)';
        icon.className = 'fas fa-check-circle';
        icon.style.color = '#28a745';
        title.textContent = 'Semua Validasi Sudah Di-Approve';
        description.innerHTML = count !== null
            ? `Berhasil approve <strong>${count}</strong> validasi. Tidak ada item pending review.`
            : 'Semua validasi yang tampil sudah di-approve.';
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        approveBtn.style.opacity = '0.7';
        rejectBtn.style.opacity = '0.7';
        approveBtn.style.pointerEvents = 'none';
        rejectBtn.style.pointerEvents = 'none';
        return;
    }

    if (state === 'sent') {
        card.style.background = 'linear-gradient(135deg, #edf9f0 0%, #ffffff 100%)';
        icon.className = 'fas fa-check-circle';
        icon.style.color = '#28a745';
        title.textContent = 'Data Sudah Dikirim ke Disdukcapil';
        description.innerHTML = count !== null
            ? `Berhasil approve <strong>${count}</strong> validasi dan data sudah dikirim ke Disdukcapil.`
            : 'Data sudah dikirim ke Disdukcapil. Tidak ada tindakan lebih lanjut.';
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        approveBtn.style.opacity = '0.7';
        rejectBtn.style.opacity = '0.7';
        approveBtn.style.pointerEvents = 'none';
        rejectBtn.style.pointerEvents = 'none';
        return;
    }

    if (state === 'rejected') {
        card.style.background = 'linear-gradient(135deg, #fff1f1 0%, #ffffff 100%)';
        icon.className = 'fas fa-times-circle';
        icon.style.color = '#dc3545';
        title.textContent = 'Semua Validasi Sudah Di-Reject';
        description.innerHTML = count !== null
            ? `Berhasil reject <strong>${count}</strong> validasi. Status kasus sudah diperbarui.`
            : 'Semua validasi yang tampil sudah di-reject.';
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        approveBtn.style.opacity = '0.7';
        rejectBtn.style.opacity = '0.7';
        approveBtn.style.pointerEvents = 'none';
        rejectBtn.style.pointerEvents = 'none';
        return;
    }

    card.style.background = 'linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%)';
    icon.className = 'fas fa-check-double';
    icon.style.color = '#667eea';
    title.textContent = 'Tindakan Final Kasus (Global)';
    description.innerHTML = 'Approve atau Reject di sini akan diterapkan ke <strong>semua data KTP/dokumen</strong> yang belum direview pada kasus ini.';
    resetButtons();
}

// Auto-refresh halaman setiap 5 detik jika OCR masih processing
const finalActionState = document.getElementById('final-action-card');
if (finalActionState) {
    const title = finalActionState.querySelector('h6');
    if (title && title.textContent.includes('OCR Sedang Diproses')) {
        console.log('OCR masih processing, auto-refresh setiap 5 detik...');
        setInterval(() => {
            location.reload();
        }, 5000);
    }
}
</script>

@endsection
