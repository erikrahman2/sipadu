@extends('layouts.admin')

@section('title', 'Log Akses')
@section('page-title', 'Log Akses')

@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Log Akses</h1>
      <p class="text-gray-500 text-sm mt-1">Rekaman seluruh request HTTP yang masuk ke sistem</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 items-center">
      <input type="text" name="path" value="{{ request('path') }}"
        placeholder="Filter path..."
        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
      <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
        <option value="">Semua status</option>
        <option value="ok"    @selected(request('status')==='ok')   >2xx OK</option>
        <option value="error" @selected(request('status')==='error')>4xx/5xx Error</option>
        <option value="slow"  @selected(request('status')==='slow') >Lambat (&gt;1s)</option>
      </select>
      <button type="submit"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
        Filter
      </button>
      @if(request()->hasAny(['path','status']))
        <a href="{{ route('dashboard.admin.logs') }}"
          class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
          Reset
        </a>
      @endif
    </form>
  </div>

  {{-- Summary bar --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white border border-gray-100 rounded-xl p-4">
      <div class="text-2xl font-bold text-gray-900">{{ number_format($total ?? 0) }}</div>
      <div class="text-xs text-gray-500 mt-1">Total request</div>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-4">
      <div class="text-2xl font-bold text-green-600">{{ number_format($successCount ?? 0) }}</div>
      <div class="text-xs text-gray-500 mt-1">2xx sukses</div>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-4">
      <div class="text-2xl font-bold text-red-500">{{ number_format($errorCount ?? 0) }}</div>
      <div class="text-xs text-gray-500 mt-1">4xx/5xx error</div>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-4">
      <div class="text-2xl font-bold text-yellow-500">{{ number_format($avgResponseMs ?? 0) }} ms</div>
      <div class="text-xs text-gray-500 mt-1">Rata-rata respons</div>
    </div>
  </div>

  {{-- Table --}}
  <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengguna</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-64">Path</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Respons</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @forelse($logs as $log)
          <tr class="hover:bg-gray-50/50 transition-colors">
            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
              {{ $log->created_at?->format('d/m/y H:i:s') }}
            </td>
            <td class="px-4 py-3">
              @if($log->user)
                <div class="font-medium text-gray-800 truncate max-w-[120px]">{{ $log->user->name }}</div>
                <div class="text-xs text-gray-400">{{ $log->user->getRoleNames()->first() }}</div>
              @else
                <span class="text-gray-400 text-xs">— Anonim —</span>
              @endif
            </td>
            <td class="px-4 py-3">
              @php
                $methodColors = [
                  'GET'    => 'bg-blue-100 text-blue-700',
                  'POST'   => 'bg-green-100 text-green-700',
                  'PATCH'  => 'bg-yellow-100 text-yellow-700',
                  'PUT'    => 'bg-orange-100 text-orange-700',
                  'DELETE' => 'bg-red-100 text-red-700',
                ];
              @endphp
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $methodColors[$log->method] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $log->method }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="text-gray-700 font-mono text-xs truncate max-w-[240px] block" title="{{ $log->path }}">
                {{ $log->path }}
              </span>
              @if($log->query_string)
                <span class="text-gray-400 font-mono text-xs truncate block max-w-[240px]">?{{ $log->query_string }}</span>
              @endif
            </td>
            <td class="px-4 py-3 text-center">
              @php
                $statusClass = match(true) {
                  $log->status_code >= 500 => 'bg-red-100 text-red-700',
                  $log->status_code >= 400 => 'bg-orange-100 text-orange-700',
                  $log->status_code >= 300 => 'bg-yellow-100 text-yellow-700',
                  default                  => 'bg-green-100 text-green-700',
                };
              @endphp
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $statusClass }}">
                {{ $log->status_code }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              @php $slow = $log->response_time_ms > 1000; @endphp
              <span class="font-mono text-xs {{ $slow ? 'text-red-500 font-semibold' : 'text-gray-600' }}">
                {{ number_format($log->response_time_ms) }} ms
                @if($slow)<span class="ml-1">⚠</span>@endif
              </span>
            </td>
            <td class="px-4 py-3 text-gray-400 font-mono text-xs whitespace-nowrap">
              {{ $log->ip_address }}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
              <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Belum ada log akses
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
    <div class="px-4 py-4 border-t border-gray-100">
      {{ $logs->withQueryString()->links() }}
    </div>
    @endif
  </div>

</div>
@endsection
