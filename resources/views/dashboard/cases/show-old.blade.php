@extends('layouts.admin')

@section('title', 'Detail Kasus ' . $case->case_number)
@section('page-title', 'Detail Kasus')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.cases') }}" class="hover:text-primary">Kasus</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">{{ $case->case_number }}</span>
@endsection

@section('content')
<div class="space-y-6" x-data="caseDetail({{ $case->id }})">

  {{-- Header Card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-800">{{ $case->case_number }}</h1>
        <p class="text-sm text-gray-500 mt-1">
          Token: <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $case->tracking_token }}</span>
        </p>
      </div>
      <div class="flex items-center gap-3">
        @include('components.status-badge', ['status' => $case->status, 'size' => 'lg'])

        {{-- PA Review --}}
        @if($case->status === 'PA_REVIEW' && auth()->user()->hasAnyRole(['pa_management','pa_staff']))
        <div class="flex gap-2">
          <button @click="review('approve')"
                  class="bg-green-500 text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-green-600 transition">
            <i class="fas fa-check mr-1"></i>Setujui
          </button>
          <button @click="showRejectModal = true"
                  class="bg-red-500 text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-red-600 transition">
            <i class="fas fa-times mr-1"></i>Tolak
          </button>
        </div>
        @endif

        {{-- Disdukcapil Validate --}}
        @if($case->status === 'DISDUKCAPIL_VALIDATION' && auth()->user()->hasRole('disdukcapil_staff'))
        <div class="flex gap-2">
          <button @click="validate('validate')"
                  class="bg-green-500 text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-green-600 transition">
            <i class="fas fa-stamp mr-1"></i>Validasi
          </button>
          <button @click="showRejectModal = true"
                  class="bg-red-500 text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-red-600 transition">
            <i class="fas fa-times mr-1"></i>Tolak
          </button>
        </div>
        @endif

        {{-- Submit --}}
        @if($case->status === 'DRAFT' && auth()->user()->id === $case->submitter_id)
        <button @click="submitCase()"
                class="bg-primary text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-primary-dark transition">
          <i class="fas fa-paper-plane mr-1"></i>Submit
        </button>
        @endif
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- Case Info --}}
    <div class="md:col-span-2 space-y-6">

      {{-- Details --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4"><i class="fas fa-info-circle mr-2 text-primary"></i>Informasi Kasus</h2>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
          <div><dt class="text-gray-500">Pemohon</dt><dd class="font-medium">{{ $case->submitter?->name }}</dd></div>
          <div><dt class="text-gray-500">Institusi</dt><dd class="font-medium">{{ $case->institution?->name }}</dd></div>
          <div><dt class="text-gray-500">Nama Pasangan</dt><dd class="font-medium">{{ $case->spouse_name ?? '-' }}</dd></div>
          <div><dt class="text-gray-500">NIK Pasangan</dt><dd class="font-mono text-xs">{{ $case->spouse_nik ?? '-' }}</dd></div>
          <div><dt class="text-gray-500">Tanggal Cerai</dt><dd>{{ $case->divorce_date?->format('d/m/Y') ?? '-' }}</dd></div>
          <div><dt class="text-gray-500">No. Putusan</dt><dd>{{ $case->verdict_number ?? '-' }}</dd></div>
          <div><dt class="text-gray-500">Dibuat</dt><dd>{{ $case->created_at->format('d/m/Y H:i') }}</dd></div>
          <div><dt class="text-gray-500">Selesai</dt><dd>{{ $case->completed_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
        </dl>
      </div>

      {{-- Documents --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-800"><i class="fas fa-file mr-2 text-green-500"></i>Dokumen</h2>
          @if(in_array($case->status, ['DRAFT','SUBMITTED']) && auth()->user()->hasAnyRole(['pa_assistant','pa_staff']))
          <a href="{{ route('dashboard.upload') }}" class="text-sm text-primary hover:underline">
            <i class="fas fa-upload mr-1"></i>Upload
          </a>
          @endif
        </div>
        <div class="space-y-3">
          @forelse($case->documents as $doc)
          <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
            <div class="flex items-center gap-3">
              <i class="fas {{ $doc->mime_type === 'application/pdf' ? 'fa-file-pdf text-red-400' : 'fa-file-image text-blue-400' }} text-lg"></i>
              <div>
                <p class="text-sm font-medium text-gray-800">{{ $doc->original_name }}</p>
                <p class="text-xs text-gray-400">{{ $doc->document_type }} · {{ number_format($doc->size_bytes / 1024, 1) }} KB</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              @include('components.status-badge', ['status' => $doc->status, 'size' => 'xs'])
              @if($doc->ocrResult)
              <a href="{{ route('dashboard.ocr.result', $doc->id) }}" class="text-xs text-primary hover:underline">
                <i class="fas fa-microscope mr-1"></i>OCR
              </a>
              @endif
              <a href="{{ route('dashboard.ocr.result', $doc->id) }}" class="text-xs text-gray-500 hover:text-primary">
                <i class="fas fa-download"></i>
              </a>
            </div>
          </div>
          @empty
          <p class="text-center text-gray-400 py-6 text-sm">Belum ada dokumen diupload.</p>
          @endforelse
        </div>
      </div>

    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <h2 class="font-semibold text-gray-800 mb-4"><i class="fas fa-history mr-2 text-purple-500"></i>Timeline</h2>
      <ol class="relative border-l border-gray-200 space-y-4 ml-3">
        @foreach($case->transitions as $t)
        <li class="ml-4">
          <span class="absolute -left-1.5 mt-1 w-3 h-3 bg-primary rounded-full border-2 border-white"></span>
          <div>
            <p class="text-xs text-gray-500">{{ $t->created_at->format('d/m/Y H:i') }}</p>
            <p class="text-sm font-medium">
              <span class="text-gray-400">{{ $t->from_state }}</span>
              <i class="fas fa-arrow-right text-xs mx-1 text-gray-300"></i>
              <span class="text-primary">{{ $t->to_state }}</span>
            </p>
            @if($t->reason)
            <p class="text-xs text-gray-400 mt-1 italic">"{{ $t->reason }}"</p>
            @endif
            <p class="text-xs text-gray-400">oleh {{ $t->actor?->name }}</p>
          </div>
        </li>
        @endforeach
        @if($case->transitions->isEmpty())
        <li class="ml-4 text-sm text-gray-400">Belum ada aktivitas.</li>
        @endif
      </ol>
    </div>
  </div>

  {{-- Reject Modal --}}
  <div x-show="showRejectModal" 
       x-cloak
       style="display: none"
       @click.self="showRejectModal = false"
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
      <h3 class="font-bold text-gray-800 mb-4">Alasan Penolakan</h3>
      <textarea x-model="rejectReason" rows="3" placeholder="Jelaskan alasan penolakan..."
                class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
      <div class="flex gap-3 mt-4">
        <button @click="rejectCase()" class="bg-red-500 text-white rounded-xl px-4 py-2 text-sm font-medium flex-1">Konfirmasi Tolak</button>
        <button @click="showRejectModal = false" class="border border-gray-200 text-gray-600 rounded-xl px-4 py-2 text-sm">Batal</button>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
function caseDetail(caseId) {
  return {
    caseId,
    showRejectModal: false,
    rejectReason: '',

    async submitCase() {
      const res = await this.apiCall('POST', `/api/v1/review/submit/${caseId}`);
      if (res.ok) location.reload();
    },

    async review(decision) {
      const res = await this.apiCall('POST', '/api/v1/review/pa', { case_id: caseId, decision });
      if (res.ok) location.reload();
    },

    async validate(decision) {
      const res = await this.apiCall('POST', '/api/v1/review/disdukcapil', { case_id: caseId, decision });
      if (res.ok) location.reload();
    },

    async rejectCase() {
      const endpoint = {{ in_array($case->status, ['PA_REVIEW']) ? 'true' : 'false' }}
        ? '/api/v1/review/pa'
        : '/api/v1/review/disdukcapil';
      const body = { case_id: caseId, decision: 'reject', notes: this.rejectReason };
      const res = await this.apiCall('POST', endpoint, body);
      if (res.ok) location.reload();
    },

    async apiCall(method, url, body = null) {
      const opts = {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
      };
      if (body) opts.body = JSON.stringify(body);
      return await fetch(url, opts);
    }
  };
}
</script>
@endpush
@endsection
