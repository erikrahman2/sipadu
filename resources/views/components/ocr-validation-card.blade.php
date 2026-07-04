@php
    $docType = strtoupper(str_replace('_', ' ', $validation->document->document_type ?? 'DOC'));
    $matchScore = number_format($validation->overall_match_score ?? 0, 1);
    $document = $validation->document;
    
    $statusColors = [
        'MATCH' => '#28a745',
        'PARTIAL_MATCH' => '#ffc107',
        'MISMATCH' => '#dc3545',
        'PENDING' => '#0d6efd',
    ];
    $statusLabels = [
        'MATCH' => 'Match',
        'PARTIAL_MATCH' => 'Partial Match',
        'MISMATCH' => 'Mismatch',
        'PENDING' => 'Pending',
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

            <!-- Overall Score & Waktu Proses -->
            <div class="row g-2 mb-2" style="font-size: 11px;">
                <div class="col-4">
                    <div style=" text-align: center;">
                        <div style="font-size: 14px; font-weight: 700; color: #000;">{{ number_format($validation->overall_match_score ?? 0, 1) }}%</div>
                        <div style="font-size: 9px; color: #000; font-weight: 500;">Overall Score</div>
                    </div>
                </div>
                <div class="col-8">
                    <div>
                        <div style="font-size: 10px; color: #666;">
                            <i class="fas fa-clock" style="margin-right: 4px;"></i>
                            <strong>Proses:</strong> {{ ($validation->ocrResult?->created_at ?? $validation->created_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Field Comparison Table -->
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
                            @endphp
                            <tr style="background-color: {{ $isMatch ? '#d4edda' : ($similarity >= 0.8 ? '#fff3cd' : '#f8d7da') }}; color: {{ $isMatch ? '#155724' : ($similarity >= 0.8 ? '#856404' : '#721c24') }}; padding: 3px;">
                                <td style="font-weight: 600; font-size: 10px; padding: 3px; word-break: break-word; overflow-wrap: break-word;">{{ $fieldLabel }}</td>
                                <td style="font-size: 10px; padding: 3px; word-break: break-word; overflow-wrap: break-word;">{{ $displayInputVal }}</td>
                                <td style="font-size: 10px; padding: 3px; word-break: break-word; overflow-wrap: break-word;">{{ $displayOcrVal }}</td>
                                <td style="text-align: center; font-weight: 600; padding: 3px; font-size: 10px; word-break: break-word;">{{ number_format($similarity * 100, 0) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted" style="padding: 8px; font-size: 10px;">No data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
