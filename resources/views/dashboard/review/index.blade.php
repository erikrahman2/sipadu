@extends('layouts.admin')

@section('title', 'Review Kasus - PA Management')
@section('page-title', 'Review Kasus PA')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-gray-700">Dashboard</span>
  <span class="mx-2">/</span>
  <i class="fas fa-microscope text-primary"></i>
  <span class="text-gray-800 font-medium">Review PA</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Summary Stats --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    {{-- Total Review --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm font-medium">Menunggu Review</p>
          @php
            $reviewCount = \App\Models\CaseModel::where('status', 'PA_REVIEW')->count();
          @endphp
          <p class="text-3xl font-bold text-blue-600 mt-2">{{ $reviewCount }}</p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
          <i class="fas fa-hourglass-start text-2xl text-blue-600"></i>
        </div>
      </div>
    </div>

    {{-- Match --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm font-medium">OCR Match</p>
          <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['match'] ?? 0 }}</p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
          <i class="fas fa-check-circle text-2xl text-green-600"></i>
        </div>
      </div>
    </div>

    {{-- Partial Match --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm font-medium">Partial Match</p>
          <p class="text-3xl font-bold text-amber-600 mt-2">{{ $stats['partial_match'] ?? 0 }}</p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
          <i class="fas fa-exclamation-circle text-2xl text-amber-600"></i>
        </div>
      </div>
    </div>

    {{-- Mismatch --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm font-medium">OCR Mismatch</p>
          <p class="text-3xl font-bold text-red-600 mt-2">{{ $stats['mismatch'] ?? 0 }}</p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
          <i class="fas fa-times-circle text-2xl text-red-600"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- Filter Bar --}}
  <form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-xs text-gray-500 mb-1">Cari NIK / Nama</label>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="3174010101900001..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    
    <div>
      <label class="block text-xs text-gray-500 mb-1">Status OCR</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">Semua Status</option>
        <option value="MATCH" {{ request('status') === 'MATCH' ? 'selected' : '' }}>Match</option>
        <option value="PARTIAL_MATCH" {{ request('status') === 'PARTIAL_MATCH' ? 'selected' : '' }}>Partial Match</option>
        <option value="MISMATCH" {{ request('status') === 'MISMATCH' ? 'selected' : '' }}>Mismatch</option>
        <option value="MANUAL_REVIEW" {{ request('status') === 'MANUAL_REVIEW' ? 'selected' : '' }}>Manual Review</option>
      </select>
    </div>

    <div class="flex gap-2 ml-auto">
      <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
        <i class="fas fa-search mr-1"></i> Filter
      </button>
      <a href="{{ route('dashboard.review.cases') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
        Reset
      </a>
    </div>
  </form>

  {{-- Cases Table --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i class="fas fa-microscope text-primary text-xl"></i>
        <h3 class="font-semibold text-gray-800">Kasus Menunggu Review PA</h3>
        <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-2 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
          {{ $cases->total() }}
        </span>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-500 text-left border-b border-gray-100">
            <th class="px-6 py-3 font-medium">No. Kasus</th>
            <th class="px-6 py-3 font-medium">Pemohon</th>
            <th class="px-6 py-3 font-medium">Pasangan</th>
            <th class="px-6 py-3 font-medium">Status OCR</th>
            <th class="px-6 py-3 font-medium">Match Score</th>
            <th class="px-6 py-3 font-medium">Dikirim Pada</th>
            <th class="px-6 py-3 font-medium text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($cases as $case)
            @php
              $ocrValidation = $case->ocrValidations->first();
              $matchScore = $ocrValidation?->overall_match_score ?? '-';
              $validationStatus = $ocrValidation?->validation_status ?? 'UNKNOWN';
              
              $statusColor = match($validationStatus) {
                'MATCH' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'fa-check'],
                'PARTIAL_MATCH' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => 'fa-exclamation'],
                'MISMATCH' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'fa-times'],
                'MANUAL_REVIEW' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'fa-eye'],
                default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => 'fa-question'],
              };
            @endphp
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <span class="font-semibold text-gray-800">{{ $case->case_number }}</span>
                <br>
                <span class="text-xs text-gray-400">{{ Str::limit($case->tracking_token, 20) }}</span>
              </td>
              <td class="px-6 py-4">
                <p class="font-medium text-gray-800">{{ $case->petitioner_name }}</p>
                <p class="text-xs text-gray-500">{{ $case->petitioner_nik }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="font-medium text-gray-800">{{ $case->spouse_name }}</p>
                <p class="text-xs text-gray-500">{{ $case->spouse_nik }}</p>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor['bg'] }} {{ $statusColor['text'] }}">
                  <i class="fas {{ $statusColor['icon'] }}"></i> {{ str_replace('_', ' ', $validationStatus) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary rounded-full h-2 transition-all" style="width: {{ $matchScore }}%"></div>
                  </div>
                  <span class="text-sm font-bold text-gray-800">{{ is_numeric($matchScore) ? round($matchScore) : '-' }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600">
                {{ $case->submitted_at?->format('d/m/Y H:i') ?? '—' }}
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <a href="{{ route('dashboard.review.show', $case->id) }}" 
                     class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition" 
                     title="Lihat Detail & Review">
                    <i class="fas fa-eye text-sm"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                Tidak ada kasus yang perlu di-review
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($cases->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm">
      <div class="text-gray-600">
        Menampilkan <span class="font-semibold">{{ $cases->firstItem() }}</span> hingga <span class="font-semibold">{{ $cases->lastItem() }}</span> dari <span class="font-semibold">{{ $cases->total() }}</span> kasus
      </div>
      <div class="flex gap-2">
        {{ $cases->links('pagination::simple-bootstrap-4') }}
      </div>
    </div>
    @endif
  </div>

  {{-- Workflow Information --}}
  <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
    <div class="flex items-start gap-4">
      <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-info-circle text-primary"></i>
      </div>
      <div>
        <h4 class="font-semibold text-blue-900 mb-2">Alur Review PA Management</h4>
        <ol class="space-y-1 text-sm text-blue-800">
          <li class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-bold">1</span>
            Periksa hasil OCR dan kecocokan data
          </li>
          <li class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-bold">2</span>
            Lihat detail kasus dan validasi manual jika diperlukan
          </li>
          <li class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-bold">3</span>
            Setujui (kirim ke Disdukcapil) atau tolak dengan alasan
          </li>
          <li class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-bold">4</span>
            Disdukcapil Staff akan melakukan validasi akhir
          </li>
        </ol>
      </div>
    </div>
  </div>

</div>
@endsection

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
