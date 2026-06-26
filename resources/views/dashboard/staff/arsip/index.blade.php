@extends('layouts.admin')

@section('title', 'Arsip & Serah-Terima Kasus')
@section('page-title', 'Arsip & Serah-Terima Kasus')

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-archive mr-2 text-green-500"></i>Arsip & Serah-Terima
      </h1>
      <p class="text-xs text-gray-500 mt-1">
        Daftar kasus yang sudah selesai (COMPLETED) dan diarsipkan (ARCHIVED).
      </p>
    </div>
    <div class="flex items-center gap-2 text-xs">
      <span class="px-3 py-1.5 rounded-full bg-green-100 text-green-700 font-semibold">
        <i class="fas fa-check-circle mr-1"></i> Selesai: {{ $counts['completed'] ?? 0 }}
      </span>
      <span class="px-3 py-1.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
        <i class="fas fa-archive mr-1"></i> Diarsipkan: {{ $counts['archived'] ?? 0 }}
      </span>
    </div>
  </div>

  <form method="get" action="{{ route('dashboard.staff.arsip') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col md:flex-row gap-3 md:items-end">
    <div class="flex-1">
      <label class="text-xs font-semibold text-gray-600">Cari</label>
      <input type="text" name="q" value="{{ request('q') }}" placeholder="No kasus / nama pemohon / pasangan / token…"
             class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-green-300 focus:border-green-400 outline-none">
    </div>
    <div>
      <label class="text-xs font-semibold text-gray-600">Tahun Selesai</label>
      <select name="year" class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-green-300 focus:border-green-400 outline-none">
        <option value="">Semua Tahun</option>
        @foreach($years as $y)
          <option value="{{ $y }}" @selected((string)$currentYear === (string)$y)>{{ $y }}</option>
        @endforeach
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg">
        <i class="fas fa-search mr-1"></i> Filter
      </button>
      @if(request('q') || request('year'))
        <a href="{{ route('dashboard.staff.arsip') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg">
          <i class="fas fa-undo mr-1"></i> Reset
        </a>
      @endif
    </div>
  </form>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-500 text-left">
          <th class="px-4 py-3">No. Kasus</th>
          <th class="px-4 py-3">Pemohon</th>
          <th class="px-4 py-3">Pasangan</th>
          <th class="px-4 py-3">Tgl Putusan</th>
          <th class="px-4 py-3">Tgl Selesai</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($arsipItems as $item)
        <tr class="hover:bg-green-50/40 transition">
          <td class="px-4 py-3">
            <p class="font-semibold text-gray-800 text-xs">{{ $item->case_number }}</p>
            <p class="text-xs text-gray-400 font-mono">{{ Str::limit($item->tracking_token, 18) }}</p>
            @if($item->source_type === 'public')
              <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 mt-1">
                <i class="fas fa-globe text-[9px]"></i> Publik
              </span>
            @endif
          </td>
          <td class="px-4 py-3">
            <p class="font-medium text-gray-800 text-xs">{{ $item->petitioner_name }}</p>
            <p class="text-xs text-gray-400">NIK: {{ $item->petitioner_nik ?? '—' }}</p>
          </td>
          <td class="px-4 py-3">
            <p class="font-medium text-gray-800 text-xs">{{ $item->spouse_name }}</p>
            <p class="text-xs text-gray-400">NIK: {{ $item->spouse_nik ?? '—' }}</p>
          </td>
          <td class="px-4 py-3 text-gray-500 text-xs">{{ optional($item->divorce_date)->format('d/m/Y') ?? '—' }}</td>
          <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->completed_at ? $item->completed_at->format('d/m/Y H:i') : '—' }}</td>
          <td class="px-4 py-3">
            @if($item->status === 'COMPLETED')
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700">
                <i class="fas fa-check-circle text-[10px]"></i> Selesai
              </span>
            @elseif($item->status === 'ARCHIVED')
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-700">
                <i class="fas fa-archive text-[10px]"></i> Diarsipkan
              </span>
            @else
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700">
                {{ $item->status }}
              </span>
            @endif
          </td>
          <td class="px-4 py-3">
            <a href="{{ route('dashboard.cases.show', $item->id) }}"
               class="text-green-600 hover:underline text-xs font-medium">
              <i class="fas fa-eye mr-1"></i>Lihat
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center py-8 text-gray-300">Belum ada arsip.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="px-4 py-3 border-t">{{ $arsipItems->links() }}</div>
  </div>
</div>
@endsection
