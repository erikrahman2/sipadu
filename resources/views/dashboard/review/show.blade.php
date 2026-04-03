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
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Manual Review</span>
                                <strong>{{ $validationStats['manual_review'] }}</strong>
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
        <div class="card shadow-sm mb-4 border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-double" style="font-size: 18px; color: #667eea; margin-right: 8px;"></i>
                    <h6 class="mb-0 fw-bold" style="font-size: 15px;">Tindakan Final Kasus (Global)</h6>
                </div>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Approve atau Reject di sini akan diterapkan ke <strong>semua data KTP/dokumen</strong> yang belum direview pada kasus ini.
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success btn-lg" 
                            onclick="approveAll(this)"
                            style="flex: 1; min-width: 150px; font-weight: 600; padding: 10px 24px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);">
                        <i class="fas fa-thumbs-up"></i> Approve Semua
                    </button>
                    <button class="btn btn-danger btn-lg" 
                            onclick="rejectAll(this)"
                            style="flex: 1; min-width: 150px; font-weight: 600; padding: 10px 24px; background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); border: none; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);">
                        <i class="fas fa-times-circle"></i> Reject Semua
                    </button>
                </div>
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
                $allDocs = $publicDocs->merge($caseDocs);
            @endphp
            
            @if($allDocs->isNotEmpty())
                <div class="row g-3 mb-4" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    @foreach($publicDocs as $doc)
                        <div style="width: 100%; min-width: 0;">
                            @php
                                $fileUrl = asset('storage/' . $doc->stored_path);
                                $isImage = in_array(strtolower(pathinfo($doc->original_filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                                $fileExists = file_exists(storage_path('app/' . $doc->stored_path));
                            @endphp
                            <a href="{{ $fileUrl }}" target="_blank" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                <div class="card shadow-sm" style="height: 100%; overflow: hidden; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow=''">
                                    @if($isImage && $fileExists)
                                        <div style="background: #f0f0f0; height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                            <img src="{{ $fileUrl }}" alt="{{ $doc->original_filename }}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                        </div>
                                    @endif
                                    <div class="card-body">
                                        <h6 class="card-title mb-2 badge bg-success" style="font-size: 10px; display: inline-block;">
                                            📄 {{ \App\Models\PublicSubmissionDocument::$typeLabels[$doc->document_type] ?? $doc->document_type }}
                                        </h6>
                                        <p class="text-muted mb-1" style="font-size: 12px; word-break: break-all;">{{ $doc->original_filename }}</p>
                                        <p style="font-size: 11px; color: #6c757d;">Ukuran: {{ number_format($doc->file_size / 1024, 2) }} KB</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                    
                    @foreach($caseDocs as $doc)
                        <div style="width: 100%; min-width: 0;">
                            @php
                                $filePath = '';
                                $fileExists = false;
                                if($doc->disk === 'public') {
                                    $filePath = 'storage/' . preg_replace('/^public\//', '', $doc->path);
                                    $fileExists = file_exists(storage_path('app/public/' . preg_replace('/^public\//', '', $doc->path)));
                                }
                                $isImage = in_array(strtolower(pathinfo($doc->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                            @endphp
                            @if($doc->disk === 'public' && $fileExists)
                                <a href="{{ asset($filePath) }}" target="_blank" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                    <div class="card shadow-sm" style="height: 100%; overflow: hidden; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow=''">
                                        @if($isImage)
                                            <div style="background: #f0f0f0; height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                <img src="{{ asset($filePath) }}" alt="{{ $doc->original_name }}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                            </div>
                                        @endif
                                        <div class="card-body">
                                            <h6 class="card-title mb-2 badge bg-info" style="font-size: 10px; display: inline-block;">
                                                📋 {{ $doc->document_type }}
                                            </h6>
                                            <p class="text-muted mb-1" style="font-size: 12px; word-break: break-all;">{{ $doc->original_name }}</p>
                                            <p style="font-size: 11px; color: #6c757d;">Ukuran: {{ number_format($doc->size_bytes / 1024, 2) }} KB</p>
                                        </div>
                                    </div>
                                </a>
                            @else
                                <div class="card shadow-sm" style="height: 100%; overflow: hidden; background: #f8f9fa;">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2 badge bg-warning" style="font-size: 10px; display: inline-block;">
                                            📋 {{ $doc->document_type }}
                                        </h6>
                                        <p class="text-muted mb-1" style="font-size: 12px;">{{ $doc->original_name }}</p>
                                        @if($doc->disk !== 'public')
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
                                    <td><strong>{{ $case->divorce_date?->format('d M Y') ?? ($case->publicSubmission->divorce_date?->format('d M Y') ?? '-') }}</strong></td>
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
    
    const confirmAction = window.confirm(`Approve semua ${unreviewed.length} validasi OCR untuk kasus ini?`);
    if (!confirmAction) return;
    
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
            alert(`✓ Berhasil approve ${approved} validasi OCR\nHalaman akan di-refresh...`);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(`⚠ Berhasil: ${approved}, Gagal: ${failed}`);
        }
    } catch (error) {
        console.error('Fatal error in approveAll:', error);
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
            alert(`✓ Berhasil reject ${rejected} validasi OCR\n• Status kasus: NEEDS_REVISION\n• Notifikasi dikirim ke pemohon & PA Assistant\n\nHalaman akan di-refresh...`);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(`⚠ Berhasil: ${rejected}, Gagal: ${failed}`);
        }
    } catch (error) {
        console.error('Fatal error in rejectAll:', error);
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
</script>

@endsection
