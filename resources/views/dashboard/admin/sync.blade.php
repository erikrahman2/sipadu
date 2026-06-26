@extends('layouts.admin')

@section('title', 'Graph Sync Status')
@section('page-title', 'Graph Sync Status')

@section('content')
<div class="space-y-6">
  <h1 class="text-xl font-bold text-stone-800"><i class="fas fa-sync mr-2 text-amber-700"></i>Graph Sync Status</h1>

  {{-- Stats --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach([
      ['label'=>'Pending',    'value'=>$stats['pending'],    'color'=>'yellow'],
      ['label'=>'Processing', 'value'=>$stats['processing'], 'color'=>'blue'],
      ['label'=>'Success',    'value'=>$stats['success'],    'color'=>'green'],
      ['label'=>'Failed',     'value'=>$stats['failed'],     'color'=>'red'],
    ] as $card)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
      <div class="w-10 h-10 rounded-xl bg-{{ $card['color'] }}-100 flex items-center justify-center">
        <i class="fas fa-circle text-{{ $card['color'] }}-400"></i>
      </div>
      <div>
        <p class="text-2xl font-bold">{{ $card['value'] }}</p>
        <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Trigger --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6" x-data="syncPanel()">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="font-semibold text-gray-700">Manual Sync Trigger</h2>
        <p class="text-sm text-gray-400">Picu sinkronisasi Neo4j secara manual</p>
      </div>
      <button @click="triggerSync()"
              :disabled="loading"
              class="bg-primary text-white rounded-xl px-5 py-2 text-sm font-medium hover:bg-primary-dark transition disabled:opacity-60 flex items-center gap-2">
        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
        <i class="fas fa-sync" x-show="!loading"></i>
        Sync Sekarang
      </button>
    </div>
    <div x-show="message" x-text="message" class="mt-3 text-sm bg-blue-50 text-blue-700 px-4 py-2 rounded-xl"></div>
  </div>

  {{-- Recent Sync Log --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <div class="px-6 py-4 border-b">
      <h2 class="font-semibold text-gray-700"><i class="fas fa-list mr-2 text-gray-400"></i>Log Sync Terbaru</h2>
    </div>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-500 text-left">
          <th class="px-4 py-3">ID</th>
          <th class="px-4 py-3">Operasi</th>
          <th class="px-4 py-3">Label/Rel</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Durasi</th>
          <th class="px-4 py-3">Waktu</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($recent as $log)
        <tr>
          <td class="px-4 py-3 text-gray-400 text-xs">{{ $log->id }}</td>
          <td class="px-4 py-3 font-mono text-xs">{{ $log->operation }}</td>
          <td class="px-4 py-3">{{ $log->label_or_rel }}</td>
          <td class="px-4 py-3">
            @if($log->success)
              <span class="text-green-600"><i class="fas fa-check mr-1"></i>OK</span>
            @else
              <span class="text-red-500"><i class="fas fa-times mr-1"></i>FAIL</span>
            @endif
          </td>
          <td class="px-4 py-3 text-xs">{{ $log->duration_ms }}ms</td>
          <td class="px-4 py-3 text-xs text-gray-400">{{ $log->created_at->format('d/m H:i:s') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center py-8 text-gray-300">Belum ada sync log.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@push('scripts')
<script>
function syncPanel() {
  return {
    loading: false,
    message: '',
    async triggerSync() {
      this.loading = true;
      this.message = '';
      try {
        const res = await fetch('/api/v1/sync/graph', {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await res.json();
        this.message = data.message || 'Sync dijadwalkan.';
      } catch(e) {
        this.message = 'Gagal memicu sync.';
      } finally {
        this.loading = false;
      }
    }
  };
}
</script>
@endpush
@endsection
