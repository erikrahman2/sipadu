@extends('layouts.app')

@section('title', 'Detail Validasi OCR - ' . $case->case_number)

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Detail Validasi OCR</h1>
            <p class="text-muted mb-0">Kasus: <strong>{{ $case->case_number }}</strong></p>
        </div>
        <div>
            <a href="{{ route('dashboard.review.cases') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3">
        <form method="POST" action="{{ route('dashboard.review.refresh_ocr', $case->id) }}" onsubmit="return confirm('Hapus data OCR lama lalu proses ulang cepat untuk kasus ini?');">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-bolt"></i> Proses OCR Cepat + Hapus Data Lama
            </button>
        </form>
    </div>

    <div class="alert alert-primary border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <i class="fas fa-user-shield fa-lg me-2 mt-1"></i>
            <div>
                <strong>Mode Review PA Management</strong><br>
                <small>Halaman ini menampilkan hasil OCR, perbandingan dengan input manual, dan fitur edit hasil OCR langsung sebelum approve/reject.</small>
            </div>
        </div>
    </div>

    <!-- Case Info -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informasi Kasus</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="150">No. Kasus</td>
                                    <td><strong>{{ $case->case_number }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">NIK Pemohon</td>
                                    <td><code>{{ $case->petitioner_nik ?? $case->publicSubmission->nik ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Pemohon</td>
                                    <td>{{ $case->petitioner_name ?? $case->publicSubmission->nama_lengkap ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jenis Layanan</td>
                                    <td>{{ $case->service_type ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="150">Status Kasus</td>
                                    <td><span class="badge bg-info">{{ strtoupper($case->status) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Submit</td>
                                    <td>{{ $case->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Validasi</td>
                                    <td><strong>{{ $validationStats['total'] }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Statistik Validasi</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Match</small>
                            <small class="text-success"><strong>{{ $validationStats['match'] }}</strong></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $validationStats['total'] > 0 ? ($validationStats['match'] / $validationStats['total'] * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Partial Match</small>
                            <small class="text-warning"><strong>{{ $validationStats['partial_match'] }}</strong></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: {{ $validationStats['total'] > 0 ? ($validationStats['partial_match'] / $validationStats['total'] * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Manual Review</small>
                            <small class="text-info"><strong>{{ $validationStats['manual_review'] }}</strong></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: {{ $validationStats['total'] > 0 ? ($validationStats['manual_review'] / $validationStats['total'] * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Mismatch</small>
                            <small class="text-danger"><strong>{{ $validationStats['mismatch'] }}</strong></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" style="width: {{ $validationStats['total'] > 0 ? ($validationStats['mismatch'] / $validationStats['total'] * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Validation Results -->
    @if($case->ocrValidations->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Belum ada hasil validasi OCR untuk kasus ini.
        </div>
    @else
        @foreach($case->ocrValidations as $validation)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                Validasi OCR - {{ $validation->document ? strtoupper(str_replace('_', ' ', $validation->document->document_type)) : 'Unknown' }}
                            </h5>
                            <small class="text-muted">{{ $validation->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <div>
                            <span class="badge {{ $validation->getStatusBadgeClass() }} fs-6">
                                {{ $validation->getStatusLabel() }}
                            </span>
                            <span class="badge bg-dark fs-6 ms-2">
                                Score: {{ number_format($validation->overall_match_score, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Comparison Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="20%">Field</th>
                                    <th width="35%">Data Manual Input</th>
                                    <th width="35%">Data OCR</th>
                                    <th width="10%" class="text-center">Match Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $fields = [
                                        'nik' => 'NIK',
                                        'nama' => 'Nama Lengkap',
                                        'tempat_lahir' => 'Tempat Lahir',
                                        'tgl_lahir' => 'Tanggal Lahir',
                                        'alamat' => 'Alamat',
                                        'rt_rw' => 'RT/RW',
                                        'kelurahan' => 'Kelurahan',
                                        'kecamatan' => 'Kecamatan',
                                        'no_kk' => 'No. KK'
                                    ];
                                    $comparisonResults = $validation->comparison_results ?? [];
                                @endphp
                                
                                @foreach($fields as $fieldKey => $fieldLabel)
                                    @php
                                        $inputValue = $validation->{"input_$fieldKey"} ?? '-';
                                        $ocrValue = $validation->{"ocr_$fieldKey"} ?? '-';
                                        
                                        // Defensive extraction: handle various data structures
                                        $fieldData = data_get($comparisonResults, $fieldKey, []);
                                        $similarityRaw = is_array($fieldData) ? ($fieldData['similarity'] ?? 0) : 0;
                                        
                                        // Ensure similarity is numeric
                                        if (is_array($similarityRaw)) {
                                          $similarityRaw = $similarityRaw[0] ?? 0;
                                        }
                                        
                                        $similarity = (float) $similarityRaw;
                                        $matchScore = round($similarity * 100, 1);
                                        $isMatch = $matchScore >= 95;
                                        $isPartial = $matchScore >= 80 && $matchScore < 95;
                                        $isMismatch = $matchScore < 80;
                                    @endphp
                                    <tr class="{{ $isMismatch ? 'table-danger' : ($isPartial ? 'table-warning' : '') }}">
                                        <td>
                                            <strong>{{ $fieldLabel }}</strong>
                                            @if($fieldKey === 'nik')
                                                <span class="badge bg-danger ms-1">CRITICAL</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($inputValue && $inputValue !== '-')
                                                <span class="text-dark">{{ $inputValue }}</span>
                                            @else
                                                <span class="text-muted fst-italic">Tidak ada data</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ocrValue && $ocrValue !== '-')
                                                <span class="text-dark">{{ $ocrValue }}</span>
                                            @else
                                                <span class="text-muted fst-italic">Tidak terdeteksi</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="progress mb-1" style="height: 20px; width: 80px;">
                                                    <div class="progress-bar {{ $isMatch ? 'bg-success' : ($isPartial ? 'bg-warning' : 'bg-danger') }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $matchScore }}%"
                                                         aria-valuenow="{{ $matchScore }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small><strong>{{ number_format($matchScore, 1) }}%</strong></small>
                                                @if($isMatch)
                                                    <small class="text-success"><i class="fas fa-check"></i></small>
                                                @elseif($isMismatch)
                                                    <small class="text-danger"><i class="fas fa-times"></i></small>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Review Section -->
                    <div class="mt-4">
                        @if($validation->is_reviewed)
                            <!-- Already Reviewed -->
                            <div class="alert alert-success">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                    <div class="flex-grow-1">
                                        <h5 class="alert-heading">Sudah Direview</h5>
                                        <p class="mb-1"><strong>Aksi:</strong> {{ ucfirst($validation->review_action) }}</p>
                                        <p class="mb-1"><strong>Oleh:</strong> {{ $validation->reviewer->name ?? '-' }}</p>
                                        <p class="mb-1"><strong>Tanggal:</strong> {{ $validation->reviewed_at?->format('d M Y H:i') ?? '-' }}</p>
                                        @if($validation->review_notes)
                                            <p class="mb-0"><strong>Catatan:</strong> {{ $validation->review_notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Review Actions -->
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editOcrModal{{ $validation->id }}">
                                    <i class="fas fa-pen"></i> Edit Hasil OCR
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $validation->id }}">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $validation->id }}">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#correctionModal{{ $validation->id }}">
                                    <i class="fas fa-edit"></i> Request Correction
                                </button>
                            </div>

                            <!-- Edit OCR Modal -->
                            <div class="modal fade" id="editOcrModal{{ $validation->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('dashboard.review.correct', $case->id) }}">
                                            @csrf
                                            <input type="hidden" name="validation_id" value="{{ $validation->id }}">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-pen"></i> Edit Hasil OCR
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-muted small mb-3">Perbaiki data hasil OCR jika ada salah baca. Sistem akan hitung ulang skor validasi otomatis.</p>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">NIK</label>
                                                        <input type="text" class="form-control" name="ocr_nik" value="{{ old('ocr_nik', $validation->ocr_nik) }}" maxlength="16">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">No. KK</label>
                                                        <input type="text" class="form-control" name="ocr_no_kk" value="{{ old('ocr_no_kk', $validation->ocr_no_kk) }}" maxlength="16">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Nama Lengkap</label>
                                                        <input type="text" class="form-control" name="ocr_nama" value="{{ old('ocr_nama', $validation->ocr_nama) }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Tempat Lahir</label>
                                                        <input type="text" class="form-control" name="ocr_tempat_lahir" value="{{ old('ocr_tempat_lahir', $validation->ocr_tempat_lahir) }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Tanggal Lahir</label>
                                                        <input type="text" class="form-control" name="ocr_tgl_lahir" value="{{ old('ocr_tgl_lahir', $validation->ocr_tgl_lahir) }}" placeholder="Contoh: 1990-01-31 / 31-01-1990">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">RT/RW</label>
                                                        <input type="text" class="form-control" name="ocr_rt_rw" value="{{ old('ocr_rt_rw', $validation->ocr_rt_rw) }}" maxlength="10">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Kelurahan</label>
                                                        <input type="text" class="form-control" name="ocr_kelurahan" value="{{ old('ocr_kelurahan', $validation->ocr_kelurahan) }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Kecamatan</label>
                                                        <input type="text" class="form-control" name="ocr_kecamatan" value="{{ old('ocr_kecamatan', $validation->ocr_kecamatan) }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Alamat</label>
                                                        <textarea class="form-control" name="ocr_alamat" rows="3">{{ old('ocr_alamat', $validation->ocr_alamat) }}</textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Catatan Koreksi (opsional)</label>
                                                        <textarea class="form-control" name="correction_notes" rows="2" placeholder="Catatan perubahan OCR">{{ old('correction_notes') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Simpan Koreksi OCR
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Approve Modal -->
                            <div class="modal fade" id="approveModal{{ $validation->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('dashboard.review.validate', $case->id) }}">
                                            @csrf
                                            <input type="hidden" name="validation_id" value="{{ $validation->id }}">
                                            <input type="hidden" name="action" value="approve">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-check-circle"></i> Approve Validasi OCR
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Anda yakin ingin approve validasi OCR ini?</p>
                                                <p class="text-muted small mb-3">
                                                    Data OCR akan dianggap valid dan dapat digunakan untuk proses selanjutnya.
                                                </p>
                                                <div class="mb-3">
                                                    <label for="approveNotes{{ $validation->id }}" class="form-label">Catatan (opsional)</label>
                                                    <textarea class="form-control" id="approveNotes{{ $validation->id }}" name="notes" rows="3" placeholder="Tambahkan catatan jika diperlukan"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Ya, Approve
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal{{ $validation->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('dashboard.review.validate', $case->id) }}">
                                            @csrf
                                            <input type="hidden" name="validation_id" value="{{ $validation->id }}">
                                            <input type="hidden" name="action" value="reject">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-times-circle"></i> Reject Validasi OCR
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Anda yakin ingin reject validasi OCR ini?</p>
                                                <p class="text-muted small mb-3">
                                                    Kasus akan dikembalikan untuk verifikasi ulang dokumen.
                                                </p>
                                                <div class="mb-3">
                                                    <label for="rejectNotes{{ $validation->id }}" class="form-label">Alasan Reject <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" id="rejectNotes{{ $validation->id }}" name="notes" rows="3" placeholder="Jelaskan alasan reject" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Ya, Reject
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Request Correction Modal -->
                            <div class="modal fade" id="correctionModal{{ $validation->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('dashboard.review.validate', $case->id) }}">
                                            @csrf
                                            <input type="hidden" name="validation_id" value="{{ $validation->id }}">
                                            <input type="hidden" name="action" value="request_correction">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-edit"></i> Request Correction
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Minta koreksi data kepada PA Assistant atau Pengaju Publik?</p>
                                                <p class="text-muted small mb-3">
                                                    Notifikasi akan dikirim untuk melakukan perbaikan data.
                                                </p>
                                                <div class="mb-3">
                                                    <label for="correctionNotes{{ $validation->id }}" class="form-label">Catatan Koreksi <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" id="correctionNotes{{ $validation->id }}" name="notes" rows="3" placeholder="Jelaskan data yang perlu dikoreksi" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-edit"></i> Kirim Request
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Highlight mismatched fields
    const mismatchedRows = document.querySelectorAll('tr.table-danger');
    mismatchedRows.forEach(row => {
        row.style.transition = 'background-color 0.3s';
    });
});
</script>
@endsection
