@php
    $docType = strtoupper(str_replace('_', ' ', $validation->document->document_type ?? 'DOC'));
    $matchScore = number_format($validation->overall_match_score ?? 0, 1);
    $document = $validation->document;
    
    $statusColors = [
        'SUCCESS' => '#28a745',
        'PARTIAL' => '#ffc107',
        'FAILED' => '#dc3545',
    ];
    $statusLabels = [
        'SUCCESS' => 'Success',
        'PARTIAL' => 'Partial',
        'FAILED' => 'Failed',
    ];
    $bgColor = $statusColors[$validation->validation_status] ?? '#6c757d';
    $statusLabel = $statusLabels[$validation->validation_status] ?? 'Unknown';
    
    $imageUrl = $document && $document->path ? asset('storage/' . $document->path) : 
                ($document && $document->stored_name ? asset('documents/' . $document->stored_name) : null);
@endphp

<div id="card-{{ $validation->id }}" 
     data-validation-id="{{ $validation->id }}"
     data-is-reviewed="{{ $validation->is_reviewed ? 'true' : 'false' }}"
     style="margin-bottom: 12px;">
    <!-- Header Bar -->
    <div onclick="toggleDetail('detail-{{ $validation->id }}', 'chevron-{{ $validation->id }}')" 
         style="background-color: {{ $bgColor }}; padding: 12px 16px; cursor: pointer; border-radius: 4px 4px 0 0; display: flex; align-items: center; gap: 12px; color: white; user-select: none;">
        <i class="fas fa-chevron-right" id="chevron-{{ $validation->id }}" style="transition: transform 0.3s ease; width: 16px; font-size: 12px;"></i>
        <strong style="font-size: 14px; flex: 1;">{{ $docType }}</strong>
        <span style="background: rgba(255,255,255,0.3); padding: 2px 8px; border-radius: 3px; font-size: 11px;">{{ $matchScore }}%</span>
        <span style="font-size: 12px;">{{ $statusLabel }}</span>
    </div>

    <!-- Detail Section -->
    <div id="detail-{{ $validation->id }}" class="d-none" style="border: 1px solid {{ $bgColor }}; border-top: none; background: white; padding: 16px; border-radius: 0 0 4px 4px;">
        
        <!-- Image -->
        @if($imageUrl)
            <div style="margin-bottom: 16px;">
                <img src="{{ $imageUrl }}" alt="{{ $docType }}" style="width: 100%; height: auto; border-radius: 4px; max-height: 200px; object-fit: cover;">
            </div>
        @endif

        <div style="padding: 0;">
            @php
                $fields = [
                    'nik' => 'NIK',
                    'nama' => 'Nama',
                    'alamat' => 'Alamat',
                    'rt_rw' => 'RT/RW',
                    'kelurahan' => 'Kelurahan',
                    'kecamatan' => 'Kecamatan'
                ];
                $comparisonResults = $validation->comparison_results ?? [];
            @endphp

            <!-- Detail Tabs -->
            <ul class="nav nav-tabs mb-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-comparison-{{ $validation->id }}" 
                            type="button" role="tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#panel-comparison-{{ $validation->id }}"
                            style="font-size: 11px; padding: 6px 10px;">
                        <i class="fas fa-exchange-alt"></i> Bandingan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-summary-{{ $validation->id }}" 
                            type="button" role="tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#panel-summary-{{ $validation->id }}"
                            style="font-size: 11px; padding: 6px 10px;">
                        <i class="fas fa-list"></i> Statistik
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-info-{{ $validation->id }}" 
                            type="button" role="tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#panel-info-{{ $validation->id }}"
                            style="font-size: 11px; padding: 6px 10px;">
                        <i class="fas fa-info-circle"></i> Info
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Tab 1: Field Comparison -->
                <div class="tab-pane fade show active" id="panel-comparison-{{ $validation->id }}" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless" style="font-size: 11px; margin-bottom: 0; table-layout: fixed; width: 100%;">
                            <thead>
                                <tr style="border-bottom: 1px solid #dee2e6; background-color: #e9ecef;">
                                    <th style="font-size: 10px; font-weight: 600; padding: 4px; width: 18%;">Field</th>
                                    <th style="font-size: 10px; font-weight: 600; padding: 4px; width: 27%;">Manual</th>
                                    <th style="font-size: 10px; font-weight: 600; padding: 4px; width: 40%;">OCR</th>
                                    <th style="font-size: 10px; font-weight: 600; padding: 4px; width: 15%; text-align: center;">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fields as $fieldKey => $fieldLabel)
                                    @php
                                        $inputVal = $validation->{"input_$fieldKey"} ?? '-';
                                        $ocrVal = $validation->{"ocr_$fieldKey"} ?? '-';
                                        $rawInputVal = trim((string) ($validation->{"input_$fieldKey"} ?? ''));
                                        $rawOcrVal = trim((string) ($validation->{"ocr_$fieldKey"} ?? ''));
                                        $displayInputVal = $rawInputVal === '' ? '-' : $rawInputVal;
                                        $displayOcrVal = $rawOcrVal === '' ? '-' : $rawOcrVal;
                                        $comparison = $comparisonResults[$fieldKey] ?? null;
                                        $similarity = $comparison['similarity'] ?? 0;
                                        $isMatch = $comparison['match'] ?? false;
                                        
                                        $rowBg = $isMatch ? '#d4edda' : ($similarity >= 0.8 ? '#fff3cd' : '#f8d7da');
                                        $textColor = $isMatch ? '#155724' : ($similarity >= 0.8 ? '#856404' : '#721c24');
                                    @endphp
                                    <tr style="background-color: {{ $rowBg }}; color: {{ $textColor }}; padding: 3px;">
                                        <td style="font-weight: 600; font-size: 10px; padding: 3px; word-break: break-word; overflow-wrap: break-word;">{{ $fieldLabel }}</td>
                                        <td style="font-size: 10px; padding: 3px; word-break: break-word; overflow-wrap: break-word; cursor: pointer; position: relative;"
                                            onclick="makeEditable(this, '{{ $validation->id }}', '{{ $fieldKey }}', 'manual');"
                                            title="Click to edit manual data">
                                            <span id="manual-{{ $validation->id }}-{{ $fieldKey }}" class="manual-field-value" data-value="{{ $rawInputVal }}">
                                                {{ $displayInputVal }}
                                            </span>
                                            <i class="fas fa-edit fa-xs" style="opacity: 0; margin-left: 2px;"></i>
                                        </td>
                                        <td style="font-size: 10px; padding: 3px; word-break: break-word; overflow-wrap: break-word; cursor: pointer; position: relative;" 
                                            onclick="makeEditable(this, '{{ $validation->id }}', '{{ $fieldKey }}', 'ocr');"
                                            title="Click to edit">
                                            <span id="ocr-{{ $validation->id }}-{{ $fieldKey }}" class="field-value" data-value="{{ $rawOcrVal }}">
                                                {{ $displayOcrVal }}
                                            </span>
                                            <i class="fas fa-edit fa-xs" style="opacity: 0; margin-left: 2px;"></i>
                                        </td>
                                        <td style="text-align: center; font-weight: 600; padding: 3px; font-size: 10px; word-break: break-word;">
                                            <span id="pct-{{ $validation->id }}-{{ $fieldKey }}">
                                                {{ number_format($similarity * 100, 0) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted" style="padding: 8px; font-size: 10px;">
                                            No data
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Summary Stats -->
                <div class="tab-pane fade" id="panel-summary-{{ $validation->id }}" role="tabpanel">
                    <div class="row g-1" style="font-size: 11px;">
                        <div class="col-6">
                            <div style="background-color: #d4edda; padding: 8px; border-radius: 4px;">
                                <div style="font-size: 14px; font-weight: 700; color: #155724;">
                                    {{ $validation->fields_matched ?? 0 }}
                                </div>
                                <div style="font-size: 10px; color: #155724; font-weight: 500;">Cocok</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background-color: #fff3cd; padding: 8px; border-radius: 4px;">
                                <div style="font-size: 14px; font-weight: 700; color: #856404;">
                                    {{ ($validation->fields_total ?? 0) - ($validation->fields_matched ?? 0) }}
                                </div>
                                <div style="font-size: 10px; color: #856404; font-weight: 500;">Perlu Cek</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div style="background-color: #e7f3ff; padding: 8px; border-radius: 4px;">
                                <div style="font-size: 16px; font-weight: 700; color: #0052cc;">
                                    {{ number_format($validation->overall_match_score ?? 0, 1) }}%
                                </div>
                                <div style="font-size: 10px; color: #0052cc; font-weight: 500;">Overall Score</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Info & Action -->
                <div class="tab-pane fade" id="panel-info-{{ $validation->id }}" role="tabpanel">
                    <div style="font-size: 11px;">
                        <div class="mb-2">
                            <strong style="font-size: 10px;">Status:</strong>
                            <span class="badge" style="background-color: {{ $bgColor }}; color: white; font-size: 9px;">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="mb-2">
                            <strong style="font-size: 10px;">Proses:</strong>
                            <span style="font-size: 10px;">{{ ($validation->ocrResult?->created_at ?? $validation->created_at)->format('d M Y H:i') }}</span>
                        </div>
                        @if($validation->is_reviewed)
                            <div class="mb-2">
                                <strong style="font-size: 10px;">Review:</strong>
                                <span style="font-size: 10px;">{{ $validation->reviewer?->name ?? 'System' }}</span>
                            </div>
                            <div class="mb-2">
                                <strong style="font-size: 10px;">Tindakan:</strong>
                                <span class="badge bg-{{ \Illuminate\Support\Str::contains($validation->review_action, 'approve') ? 'success' : (\Illuminate\Support\Str::contains($validation->review_action, 'reject') ? 'danger' : 'warning') }}" style="font-size: 9px;">
                                    {{ ucfirst(str_replace('_', ' ', $validation->review_action)) }}
                                </span>
                            </div>
                            @if($validation->review_notes)
                                <div class="mb-2">
                                    <strong style="font-size: 10px;">Catatan:</strong>
                                    <p class="mb-0" style="background-color: #f0f0f0; padding: 4px; border-radius: 3px; font-size: 9px; white-space: pre-wrap; max-height: 60px; overflow-y: auto;">
                                        {{ Illuminate\Support\Str::limit($validation->review_notes, 100) }}
                                    </p>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    @if(!$validation->is_reviewed)
                        <div class="d-flex gap-1 flex-wrap mt-2 pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-success flex-grow-1" onclick="approveValidation({{ $validation->id }})" style="font-size: 9px;">
                                <i class="fas fa-check fa-xs"></i> Approve
                            </button>
                            <button type="button" class="btn btn-sm btn-danger flex-grow-1" onclick="rejectValidation({{ $validation->id }})" style="font-size: 9px;">
                                <i class="fas fa-times fa-xs"></i> Reject
                            </button>
                        </div>
                    @else
                        <div class="mt-2 pt-2 border-top">
                            <div class="alert alert-success mb-0" style="padding: 4px 6px; font-size: 9px;">
                                <small><i class="fas fa-check-circle"></i> Sudah direview</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ocr-validation-card .card {
    border: 1px solid #e0e0e0;
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.ocr-validation-card .card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
    transform: translateY(-2px);
}

.ocr-validation-card .expand-btn:hover {
    background-color: #667eea !important;
    color: white !important;
    border-color: #667eea !important;
}

.ocr-validation-card .nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent !important;
    color: #666;
}

.ocr-validation-card .nav-tabs .nav-link.active {
    border-bottom: 2px solid #667eea !important;
    color: #667eea !important;
    background-color: transparent !important;
}

/* Editable field styles */
.ocr-validation-card td[onclick*="makeEditable"]:hover i.fa-edit {
    opacity: 1 !important;
    color: #667eea;
}

.ocr-validation-card .form-control-sm {
    font-size: 10px;
    padding: 3px 6px;
    border-color: #667eea;
}

.ocr-validation-card .form-control-sm:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}
</style>

<script>
function toggleDetail(detailId, chevronId) {
    const detail = document.getElementById(detailId);
    const chevron = document.getElementById(chevronId);
    
    detail.classList.toggle('d-none');
    if (detail.classList.contains('d-none')) {
        chevron.style.transform = 'rotate(0deg)';
    } else {
        chevron.style.transform = 'rotate(90deg)';
    }
}

function makeEditable(cellElement, validationId, fieldKey, target = 'ocr') {
    const spanSelector = target === 'manual' ? 'span.manual-field-value' : 'span.field-value';
    const span = cellElement.querySelector(spanSelector);
    if (!span) return;
    
    const currentValue = (span.getAttribute('data-value') || '').trim();
    
    // Create input field
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm';
    input.value = currentValue;
    input.style.width = '100%';
    input.style.padding = '4px 8px';
    
    // Replace span with input
    span.replaceWith(input);
    input.focus();
    input.select();
    
    // Handle blur and Enter key
    const saveChange = () => {
        const newValue = input.value.trim();
        const displayValue = newValue === '' ? '-' : newValue;
        
        // Create new span
        const newSpan = document.createElement('span');
        newSpan.className = target === 'manual' ? 'manual-field-value' : 'field-value';
        newSpan.setAttribute('data-value', newValue);
        newSpan.textContent = displayValue;
        
        // Replace input with span
        input.replaceWith(newSpan);
        
        // If value changed, save to backend
        if (newValue !== currentValue) {
            saveOcrCorrection(validationId, fieldKey, newValue, target);
        }
    };
    
    input.addEventListener('blur', saveChange);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            saveChange();
        } else if (e.key === 'Escape') {
            // Cancel edit
            const cancelSpan = document.createElement('span');
            cancelSpan.className = target === 'manual' ? 'manual-field-value' : 'field-value';
            cancelSpan.setAttribute('data-value', currentValue);
            cancelSpan.textContent = currentValue === '' ? '-' : currentValue;
            input.replaceWith(cancelSpan);
        }
    });
}

function saveOcrCorrection(validationId, fieldKey, newValue, target = 'ocr') {
    // Get case ID from URL: /dashboard/review/cases/{id}
    const pathParts = window.location.pathname.split('/');
    const caseId = pathParts[pathParts.length - 1];
    
    const fieldMap = target === 'manual' ? {
        'nik': 'input_nik',
        'nama': 'input_nama',
        'tempat_lahir': 'input_tempat_lahir',
        'tgl_lahir': 'input_tgl_lahir',
        'alamat': 'input_alamat',
        'rt_rw': 'input_rt_rw',
        'kelurahan': 'input_kelurahan',
        'kecamatan': 'input_kecamatan',
        'no_kk': 'input_no_kk',
    } : {
        'nik': 'ocr_nik',
        'nama': 'ocr_nama',
        'tempat_lahir': 'ocr_tempat_lahir',
        'tgl_lahir': 'ocr_tgl_lahir',
        'alamat': 'ocr_alamat',
        'rt_rw': 'ocr_rt_rw',
        'kelurahan': 'ocr_kelurahan',
        'kecamatan': 'ocr_kecamatan',
        'no_kk': 'ocr_no_kk',
    };
    
    const fieldName = fieldMap[fieldKey];
    if (!fieldName) {
        console.error('Unknown field key:', fieldKey);
        return;
    }
    
    const formData = new FormData();
    formData.append('validation_id', validationId);
    formData.append('target', target);
    formData.append(fieldName, newValue);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(`/dashboard/review/cases/${caseId}/correct`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const data = await response.json().catch(() => null);
        if (!response.ok || !data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Gagal menyimpan koreksi');
        }
        location.reload();
    })
    .catch(error => {
        console.error('Error saving correction:', error);
        alert('Gagal menyimpan koreksi. Silakan coba lagi.');
    });
}

function approveValidation(validationId) {
    // Get case ID from URL: /dashboard/review/cases/{id}
    const pathParts = window.location.pathname.split('/');
    const caseId = pathParts[pathParts.length - 1];
    
    if (!confirm('Yakin ingin menyetujui validasi OCR ini?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('validation_id', validationId);
    formData.append('action', 'approve');
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(`/dashboard/review/cases/${caseId}/validate`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        alert('Validasi OCR disetujui!');
        location.reload();
    })
    .catch(error => {
        console.error('Error approving validation:', error);
        alert('Gagal menyetujui validasi. Silakan coba lagi.');
    });
}

function rejectValidation(validationId) {
    // Get case ID from URL: /dashboard/review/cases/{id}
    const pathParts = window.location.pathname.split('/');
    const caseId = pathParts[pathParts.length - 1];
    
    const notes = prompt('Masukkan alasan penolakan (opsional):');
    
    if (notes === null) {
        return; // User cancelled
    }
    
    const formData = new FormData();
    formData.append('validation_id', validationId);
    formData.append('action', 'reject');
    formData.append('notes', notes);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(`/dashboard/review/cases/${caseId}/validate`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        alert('Validasi OCR ditolak!');
        location.reload();
    })
    .catch(error => {
        console.error('Error rejecting validation:', error);
        alert('Gagal menolak validasi. Silakan coba lagi.');
    });
}
</script>
