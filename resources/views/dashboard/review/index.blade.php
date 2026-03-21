@extends('layouts.app')

@section('title', 'Validasi OCR - Daftar Kasus')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Validasi OCR - Daftar Kasus</h1>
            <p class="text-muted">Review dan validasi hasil OCR terhadap data inputan manual</p>
        </div>
        <div>
            <a href="{{ route('dashboard.review.statistics') }}" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar"></i> Statistik
            </a>
        </div>
    </div>

    <!-- Filter Status -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.review.cases') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status Validasi</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="MATCH" {{ request('status') == 'MATCH' ? 'selected' : '' }}>Match</option>
                        <option value="PARTIAL_MATCH" {{ request('status') == 'PARTIAL_MATCH' ? 'selected' : '' }}>Partial Match</option>
                        <option value="MANUAL_REVIEW" {{ request('status') == 'MANUAL_REVIEW' ? 'selected' : '' }}>Manual Review</option>
                        <option value="MISMATCH" {{ request('status') == 'MISMATCH' ? 'selected' : '' }}>Mismatch</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="reviewed" class="form-label">Status Review</label>
                    <select name="reviewed" id="reviewed" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('reviewed') == 'pending' ? 'selected' : '' }}>Belum Review</option>
                        <option value="done" {{ request('reviewed') == 'done' ? 'selected' : '' }}>Sudah Review</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari NIK/Nama</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Masukkan NIK atau Nama" value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">MATCH</p>
                            <h4 class="mb-0">{{ $stats['match'] ?? 0 }}</h4>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">PARTIAL MATCH</p>
                            <h4 class="mb-0">{{ $stats['partial_match'] ?? 0 }}</h4>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">MANUAL REVIEW</p>
                            <h4 class="mb-0">{{ $stats['manual_review'] ?? 0 }}</h4>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">MISMATCH</p>
                            <h4 class="mb-0">{{ $stats['mismatch'] ?? 0 }}</h4>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cases List -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($cases->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Tidak ada kasus dengan validasi OCR</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No. Kasus</th>
                                <th>NIK</th>
                                <th>Nama Pemohon</th>
                                <th>Jenis Dokumen</th>
                                <th>Tanggal OCR</th>
                                <th>Status Validasi</th>
                                <th>Match Score</th>
                                <th>Status Review</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cases as $case)
                                @php
                                    $validation = $case->ocrValidations->first();
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $case->case_number }}</strong>
                                    </td>
                                    <td>
                                        <code>{{ $case->petitioner_nik ?? $case->publicSubmission->nik ?? '-' }}</code>
                                    </td>
                                    <td>{{ $case->petitioner_name ?? $case->publicSubmission->nama_lengkap ?? '-' }}</td>
                                    <td>
                                        @if($validation && $validation->document)
                                            <span class="badge bg-secondary">
                                                {{ strtoupper(str_replace('_', ' ', $validation->document->document_type)) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($validation)
                                            <small>{{ $validation->created_at->format('d M Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($validation)
                                            <span class="badge {{ $validation->getStatusBadgeClass() }}">
                                                {{ $validation->getStatusLabel() }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark">No Validation</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($validation)
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 20px; width: 80px;">
                                                    <div class="progress-bar {{ $validation->overall_match_score >= 95 ? 'bg-success' : ($validation->overall_match_score >= 80 ? 'bg-warning' : 'bg-danger') }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $validation->overall_match_score }}%"
                                                         aria-valuenow="{{ $validation->overall_match_score }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small><strong>{{ number_format($validation->overall_match_score, 1) }}%</strong></small>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($validation)
                                            @if($validation->is_reviewed)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> {{ ucfirst($validation->review_action) }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('dashboard.review.show', $case->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $cases->firstItem() }} - {{ $cases->lastItem() }} dari {{ $cases->total() }} kasus
                    </div>
                    <div>
                        {{ $cases->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.badge { display: inline-block; }
</style>
@endpush

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on select change
    const statusSelect = document.getElementById('status');
    const reviewedSelect = document.getElementById('reviewed');
    
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    if (reviewedSelect) {
        reviewedSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});
</script>
@endsection
