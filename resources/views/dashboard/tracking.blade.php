@extends('layouts.admin')

@section('title', 'Tracking Kasus')
@section('page-title', 'Tracking Kasus')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="trackingForm()">

  {{-- Search --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
    <h1 class="text-xl font-bold text-gray-800 mb-2">
      <i class="fas fa-search mr-2 text-purple-500"></i>Tracking Dokumen
    </h1>
    <p class="text-sm text-gray-500 mb-6">Masukkan token tracking untuk melihat status permohonan.</p>

    <div class="flex gap-3">
      <input type="text" x-model="token" placeholder="Contoh: TRKxxx..."
             @keydown.enter="search()"
             class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 font-mono" />
      <button @click="search()" :disabled="!token || loading"
              class="bg-purple-500 text-white rounded-xl px-6 py-3 font-medium hover:bg-purple-600 transition disabled:opacity-60 flex items-center gap-2">
        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
        <i class="fas fa-search" x-show="!loading"></i>
        Cari
      </button>
    </div>

    <div x-show="error" x-text="error" class="mt-3 text-sm text-red-500 bg-red-50 px-4 py-2 rounded-xl"></div>
  </div>

  {{-- Result --}}
  <div x-show="result" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">

    {{-- Status Header --}}
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs text-gray-400 mb-1">No. Kasus</p>
        <p class="font-bold text-gray-800 font-mono" x-text="result?.case_number"></p>
      </div>
      <div class="text-right">
        <span class="px-3 py-1 rounded-full text-sm font-semibold"
              :class="{
                'bg-gray-100 text-gray-600': result?.status === 'DRAFT',
                'bg-blue-100 text-blue-700': result?.status === 'SUBMITTED',
                'bg-yellow-100 text-yellow-700': ['OCR_PROCESSED','PA_REVIEW','DISDUKCAPIL_VALIDATION'].includes(result?.status),
                'bg-green-100 text-green-700': result?.status === 'COMPLETED',
                'bg-red-100 text-red-700': result?.status === 'REJECTED',
                'bg-purple-100 text-purple-700': result?.status === 'ARCHIVED',
              }"
              x-text="result?.status_label">
        </span>
      </div>
    </div>

    {{-- Info Grid --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
      <div class="bg-gray-50 rounded-xl p-3">
        <p class="text-xs text-gray-400">Institusi</p>
        <p class="font-medium" x-text="result?.institution || '-'"></p>
      </div>
      <div class="bg-gray-50 rounded-xl p-3">
        <p class="text-xs text-gray-400">Tanggal Submit</p>
        <p class="font-medium" x-text="result?.submitted_at || '-'"></p>
      </div>
      <div class="bg-gray-50 rounded-xl p-3">
        <p class="text-xs text-gray-400">Tanggal Selesai</p>
        <p class="font-medium" x-text="result?.completed_at || '-'"></p>
      </div>
    </div>

    {{-- Timeline --}}
    <div>
      <h3 class="font-semibold text-gray-700 mb-4"><i class="fas fa-history mr-2 text-purple-400"></i>Riwayat Status</h3>
      <ol class="relative border-l border-purple-200 space-y-4 ml-3">
        <template x-for="(step, i) in result?.timeline || []" :key="i">
          <li class="ml-4">
            <span class="absolute -left-1.5 mt-1 w-3 h-3 bg-purple-400 rounded-full border-2 border-white"></span>
            <p class="text-xs text-gray-400" x-text="step.date"></p>
            <p class="text-sm font-medium">
              <span class="text-gray-400" x-text="step.from"></span>
              <i class="fas fa-arrow-right text-xs mx-1 text-gray-300"></i>
              <span class="text-purple-600 font-semibold" x-text="step.to"></span>
            </p>
          </li>
        </template>
      </ol>
    </div>

    {{-- Step Progress --}}
    <div>
      <h3 class="font-semibold text-gray-700 mb-3"><i class="fas fa-tasks mr-2 text-blue-400"></i>Progress</h3>
      @php
        $steps = ['DRAFT','SUBMITTED','OCR_PROCESSED','PA_REVIEW','DISDUKCAPIL_VALIDATION','COMPLETED'];
      @endphp
      <div class="flex items-center">
        @foreach($steps as $i => $step)
        <div class="flex flex-col items-center flex-1">
          <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
               :class="isReached('{{ $step }}') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'">
            {{ $i + 1 }}
          </div>
          <p class="text-[10px] text-gray-400 text-center mt-1 leading-tight">{{ config("workflow.states.$step") }}</p>
        </div>
        @if(!$loop->last)
        <div class="flex-1 h-0.5 bg-gray-200 mb-4"
             :class="isReachedAfter('{{ $step }}') ? 'bg-green-400' : ''"></div>
        @endif
        @endforeach
      </div>
    </div>

  </div>
</div>

@push('scripts')
<script>
const STATES = ['DRAFT','SUBMITTED','OCR_PROCESSED','PA_REVIEW','DISDUKCAPIL_VALIDATION','COMPLETED','ARCHIVED','REJECTED'];

function trackingForm() {
  return {
    token: new URLSearchParams(location.search).get('token') || '',
    loading: false,
    error: '',
    result: null,

    async search() {
      if (!this.token.trim()) return;
      this.loading = true;
      this.error = '';
      this.result = null;

      try {
        const res = await fetch(`/api/v1/tracking/${this.token.trim()}`);
        const data = await res.json();
        if (!res.ok) { this.error = data.message || 'Token tidak ditemukan.'; }
        else { this.result = data; }
      } catch(e) {
        this.error = 'Gagal menghubungi server.';
      } finally {
        this.loading = false;
      }
    },

    isReached(state) {
      if (!this.result) return false;
      const cur = STATES.indexOf(this.result.status);
      const chk = STATES.indexOf(state);
      return cur >= chk;
    },

    isReachedAfter(state) {
      if (!this.result) return false;
      const cur = STATES.indexOf(this.result.status);
      const chk = STATES.indexOf(state);
      return cur > chk;
    },

    init() {
      if (this.token) this.search();
    }
  };
}
</script>
@endpush
@endsection
