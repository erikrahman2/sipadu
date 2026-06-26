@extends('layouts.admin')

@section('title', 'Aktivitas Terbaru')
@section('page-title', 'Aktivitas Terbaru')

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-bolt mr-2 text-amber-500"></i>Aktivitas Terbaru
      </h1>
      <p class="text-xs text-gray-500 mt-1">
        Daftar aktivitas terbaru dari sistem — kasus, transisi, dan arsip.
      </p>
    </div>
    <span class="px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 font-semibold text-xs">
      <i class="fas fa-clock mr-1"></i> 30 hari terakhir
    </span>
  </div>

  <form method="get" action="{{ route('dashboard.staff.aktivitas') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col md:flex-row gap-3 md:items-end">
    <div class="flex-1">
      <label class="text-xs font-semibold text-gray-600">Cari</label>
      <input type="text" name="q" value="{{ request('q') }}" placeholder="No kasus / nama / NIK / token…"
             class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none">
    </div>
    <div>
      <label class="text-xs font-semibold text-gray-600">Tipe Aktivitas</label>
      <select name="type" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none">
        <option value="">Semua Tipe</option>
        @foreach($types as $key => $label)
          <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg">
        <i class="fas fa-search mr-1"></i> Filter
      </button>
      @if(request('q') || request('type'))
        <a href="{{ route('dashboard.staff.aktivitas') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg">
          <i class="fas fa-undo mr-1"></i> Reset
        </a>
      @endif
    </div>
  </form>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <p class="text-xs text-gray-500 font-semibold">Total Aktivitas</p>
      <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <p class="text-xs text-gray-500 font-semibold">Kasus Baru</p>
      <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['created'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <p class="text-xs text-gray-500 font-semibold">Transisi</p>
      <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['transition'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <p class="text-xs text-gray-500 font-semibold">Selesai</p>
      <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['completed'] ?? 0 }}</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-700">
        <i class="fas fa-list-ul mr-1 text-amber-500"></i> Timeline
      </h3>
      <span class="text-xs text-gray-400">{{ $activities->total() }} aktivitas</span>
    </div>
    <div class="divide-y divide-gray-100">
      @forelse($activities as $item)
        <div class="px-4 py-3 hover:bg-amber-50/30 transition">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center
              @switch($item['type'])
                @case('created') bg-blue-100 text-blue-600 @break
                @case('transition') bg-amber-100 text-amber-600 @break
                @case('completed') bg-green-100 text-green-600 @break
                @case('archived') bg-slate-200 text-slate-600 @break
                @default bg-gray-100 text-gray-600
              @endswitch">
              @switch($item['type'])
                @case('created') <i class="fas fa-plus text-xs"></i> @break
                @case('transition') <i class="fas fa-exchange-alt text-xs"></i> @break
                @case('completed') <i class="fas fa-check text-xs"></i> @break
                @case('archived') <i class="fas fa-archive text-xs"></i> @break
                @default <i class="fas fa-circle text-xs"></i>
              @endswitch
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-medium text-gray-800">{{ $item['title'] }}</p>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                  @switch($item['type'])
                    @case('created') bg-blue-100 text-blue-700 @break
                    @case('transition') bg-amber-100 text-amber-700 @break
                    @case('completed') bg-green-100 text-green-700 @break
                    @case('archived') bg-slate-200 text-slate-700 @break
                    @default bg-gray-100 text-gray-700
                  @endswitch">
                  {{ $item['type_label'] }}
                </span>
              </div>
              @if($item['description'])
                <p class="text-xs text-gray-500 mt-0.5">{{ $item['description'] }}</p>
              @endif
              <div class="flex items-center gap-3 mt-1.5 text-[11px] text-gray-400">
                <span><i class="far fa-clock mr-0.5"></i> {{ $item['at']->diffForHumans() }}</span>
                <span>{{ $item['at']->format('d/m/Y H:i') }}</span>
              </div>
            </div>
            @if($item['case_id'])
              <a href="{{ route('dashboard.cases.show', $item['case_id']) }}"
                 class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex-shrink-0">
                <i class="fas fa-external-link-alt"></i> Detail
              </a>
            @endif
          </div>
        </div>
      @empty
        <div class="text-center py-10 text-gray-300">
          <i class="fas fa-inbox text-3xl mb-2"></i>
          <p class="text-xs">Tidak ada aktivitas dalam 30 hari terakhir.</p>
        </div>
      @endforelse
    </div>
    <div class="px-4 py-3 border-t border-gray-100">{{ $activities->links() }}</div>
  </div>
</div>
@endsection
