@extends('layouts.app')

@section('title', 'Statistik Validasi OCR')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Statistik Validasi OCR</h1>
            <p class="text-muted">Overview hasil validasi OCR terhadap data manual input</p>
        </div>
        <div>
            <a href="{{ route('dashboard.review.cases') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">TOTAL VALIDASI</p>
                            <h2 class="mb-0">{{ $stats['total'] }}</h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-clipboard-check fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">SUCCESS (≥90%)</p>
                            <h2 class="mb-0">{{ $stats['success'] ?? 0 }}</h2>
                            <small class="text-success">{{ $stats['total'] > 0 ? number_format(($stats['success'] / $stats['total']) * 100, 1) : 0 }}%</small>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
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
                            <p class="text-muted mb-1 small">PARTIAL (70-89%)</p>
                            <h2 class="mb-0">{{ $stats['partial'] ?? 0 }}</h2>
                            <small class="text-warning">{{ $stats['total'] > 0 ? number_format(($stats['partial'] / $stats['total']) * 100, 1) : 0 }}%</small>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
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
                            <p class="text-muted mb-1 small">FAILED (&lt;70%)</p>
                            <h2 class="mb-0">{{ $stats['failed'] ?? 0 }}</h2>
                            <small class="text-danger">{{ $stats['total'] > 0 ? number_format(($stats['failed'] / $stats['total']) * 100, 1) : 0 }}%</small>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-times-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Validation Status Distribution -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Distribusi Status Validasi</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Review Status -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Status Review</h5>
                </div>
                <div class="card-body">
                    <canvas id="reviewChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Status Validasi Detail</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                SUCCESS
                            </span>
                            <span class="badge bg-success rounded-pill">{{ $stats['success'] ?? 0 }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                PARTIAL
                            </span>
                            <span class="badge bg-warning rounded-pill">{{ $stats['partial'] ?? 0 }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-times-circle text-danger me-2"></i>
                                FAILED
                            </span>
                            <span class="badge bg-danger rounded-pill">{{ $stats['failed'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Status Review</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-clock text-warning me-2"></i>
                                Pending Review
                            </span>
                            <span class="badge bg-warning rounded-pill">{{ $stats['pending_review'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-check text-success me-2"></i>
                                Approved
                            </span>
                            <span class="badge bg-success rounded-pill">{{ $stats['approved'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-times text-danger me-2"></i>
                                Rejected
                            </span>
                            <span class="badge bg-danger rounded-pill">{{ $stats['rejected'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="fas fa-edit text-info me-2"></i>
                                Need Correction
                            </span>
                            <span class="badge bg-info rounded-pill">{{ $stats['need_correction'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Rata-rata Match Score</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h1 class="display-3 mb-0">{{ number_format($stats['avg_score'], 1) }}%</h1>
                        <p class="text-muted">Average Match Score</p>
                    </div>
                    <div class="progress mb-2" style="height: 30px;">
                        <div class="progress-bar {{ $stats['avg_score'] >= 95 ? 'bg-success' : ($stats['avg_score'] >= 80 ? 'bg-warning' : 'bg-danger') }}" 
                             role="progressbar" 
                             style="width: {{ $stats['avg_score'] }}%"
                             aria-valuenow="{{ $stats['avg_score'] }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span class="fw-bold">{{ number_format($stats['avg_score'], 1) }}%</span>
                        </div>
                    </div>
                    <small class="text-muted">Dari {{ $stats['total'] }} validasi</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Field-Specific Statistics -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Statistik Per Field</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Field</th>
                            <th class="text-center">Total Compared</th>
                            <th class="text-center">Match (≥95%)</th>
                            <th class="text-center">Partial (80-94%)</th>
                            <th class="text-center">Mismatch (<80%)</th>
                            <th class="text-center">Avg Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $fieldLabels = [
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
                        @endphp
                        @foreach($fieldLabels as $fieldKey => $fieldLabel)
                            @php
                                $fieldStat = $stats['by_field'][$fieldKey] ?? [
                                    'total' => 0,
                                    'match' => 0,
                                    'partial' => 0,
                                    'mismatch' => 0,
                                    'avg_score' => 0
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $fieldLabel }}</strong>
                                    @if($fieldKey === 'nik')
                                        <span class="badge bg-danger ms-1">CRITICAL</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $fieldStat['total'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $fieldStat['match'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning">{{ $fieldStat['partial'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $fieldStat['mismatch'] }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="progress me-2" style="height: 20px; width: 100px;">
                                            <div class="progress-bar {{ $fieldStat['avg_score'] >= 95 ? 'bg-success' : ($fieldStat['avg_score'] >= 80 ? 'bg-warning' : 'bg-danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $fieldStat['avg_score'] }}%">
                                            </div>
                                        </div>
                                        <small><strong>{{ number_format($fieldStat['avg_score'], 1) }}%</strong></small>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Aktivitas Review Terbaru</h5>
        </div>
        <div class="card-body">
            @if($recentReviews->isEmpty())
                <p class="text-muted text-center py-3 mb-0">Belum ada aktivitas review</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Reviewer</th>
                                <th>No. Kasus</th>
                                <th>Aksi</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentReviews as $review)
                                <tr>
                                    <td>
                                        <small>{{ $review->reviewed_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td>{{ $review->reviewer->name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('dashboard.review.show', $review->case_id) }}">
                                            {{ $review->case->case_number ?? '-' }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($review->review_action === 'approve')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($review->review_action === 'reject')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-warning">{{ ucfirst($review->review_action) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $review->review_notes ? Str::limit($review->review_notes, 50) : '-' }}
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Validation Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['SUCCESS (≥90%)', 'PARTIAL (70-89%)', 'FAILED (<70%)'],
            datasets: [{
                data: [
                    {{ $stats['match'] }},
                    {{ $stats['partial_match'] }},
                    {{ $stats['mismatch'] }}
                ],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = {{ $stats['total'] }};
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Review Status Chart
    const reviewCtx = document.getElementById('reviewChart').getContext('2d');
    new Chart(reviewCtx, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Approved', 'Rejected', 'Need Correction'],
            datasets: [{
                label: 'Jumlah',
                data: [
                    {{ $stats['pending_review'] }},
                    {{ $stats['approved'] }},
                    {{ $stats['rejected'] }},
                    {{ $stats['need_correction'] }}
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(13, 202, 240, 0.8)'
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endsection
