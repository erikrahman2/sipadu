@extends('layouts.admin')

@section('title', 'Detail Arsip Kasus')
@section('page-title', 'Detail Arsip')

@section('breadcrumb')
  <a href="{{ route('dashboard.staff.arsip') }}" class="hover:text-gray-600">Arsip</a>
  <i class="fas fa-chevron-right text-[10px]"></i>
  <span class="text-gray-600">{{ Str::limit($case->case_number, 30) }}</span>
@endsection

@section('content')
<div class="space-y-4">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-bold text-gray-800">{{ $case->case_number }}</h2>
      <p class="text-xs text-gray-500 mt-0.5">Token: <code class="font-mono text-gray-600">{{ $case->tracking_token }}</code></p>
    </div>
    <div class="flex gap-2">
      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold
        @if($case->status === 'COMPLETED') bg-green-100 text-green-700
        @elseif($case->status === 'ARCHIVED') bg-slate-200 text-slate-700
        @else bg-gray-100 text-gray-700 @endif">
        <i class="fas fa-{{ $case->status === 'COMPLETED' ? 'check-circle' : 'archive' }} text-[10px]"></i>
        {{ $case->status }}
      </span>
      @if($case->source_type === 'public')
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">
          <i class="fas fa-globe text-[10px]"></i> Publik
        </span>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Kolom Kiri: Informasi Kasus --}}
    <div class="lg:col-span-2 space-y-4">

      {{-- Informasi Pemohon & Pasangan --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-700 mb-3">
          <i class="fas fa-users mr-1 text-primary"></i> Pemohon & Pasangan
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pemohon</p>
            <p class="text-sm font-medium text-gray-800">{{ $case->petitioner_name ?? '—' }}</p>
            <p class="text-xs text-gray-500">NIK: {{ $case->petitioner_nik ?? '—' }}</p>
            <p class="text-xs text-gray-500">Alamat: {{ $case->petitioner_address ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pasangan</p>
            <p class="text-sm font-medium text-gray-800">{{ $case->spouse_name ?? '—' }}</p>
            <p class="text-xs text-gray-500">NIK: {{ $case->spouse_nik ?? '—' }}</p>
            <p class="text-xs text-gray-500">Alamat: {{ $case->spouse_address ?? '—' }}</p>
          </div>
        </div>
      </div>

      {{-- Detail Perkara --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-700 mb-3">
          <i class="fas fa-file-lines mr-1 text-primary"></i> Detail Perkara
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Perkara</p>
            <p class="text-sm text-gray-800">{{ $case->case_type ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tgl Putusan</p>
            <p class="text-sm text-gray-800">{{ optional($case->divorce_date)->format('d F Y') ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tgl Permohonan</p>
            <p class="text-sm text-gray-800">{{ optional($case->created_at)->format('d F Y') ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tgl Selesai</p>
            <p class="text-sm text-gray-800">{{ $case->completed_at ? $case->completed_at->format('d F Y H:i') : '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Badan / Unit</p>
            <p class="text-sm text-gray-800">{{ $case->institution->name ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Petugas PA</p>
            <p class="text-sm text-gray-800">{{ $case->assignedPaUser->name ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Petugas Disdukcapil</p>
            <p class="text-sm text-gray-800">{{ $case->assignedDisdukcapilUser->name ?? '—' }}</p>
          </div>
        </div>
      </div>

      {{-- Dokumen --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-700 mb-3">
          <i class="fas fa-paperclip mr-1 text-primary"></i> Dokumen
        </h3>
        @if($case->documents->count() > 0)
          <div class="space-y-2">
            @foreach($case->documents as $doc)
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file text-primary text-sm"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $doc->file_name }}</p>
                    <p class="text-xs text-gray-400">{{ $doc->file_type ?? '—' }} &bull; {{ number_format($doc->file_size ?? 0) }} B</p>
                  </div>
                </div>
                <a href="{{ route('dashboard.staff.arsip.download', [$case->id, $doc->id]) }}"
                   class="text-primary hover:underline text-xs font-medium flex-shrink-0">
                  <i class="fas fa-download mr-1"></i>Unduh
                </a>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-sm text-gray-400">Belum ada dokumen.</p>
        @endif
      </div>

    </div>

    {{-- Kolom Kanan: Timeline --}}
    <div class="space-y-4">

      {{-- Transisi / Timeline --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-700 mb-3">
          <i class="fas fa-timeline mr-1 text-primary"></i> Timeline
        </h3>
        @if($case->transitions->count() > 0)
          <div class="space-y-3">
            @foreach($case->transitions->reverse() as $t)
              <div class="flex gap-2">
                <div class="flex flex-col items-center">
                  <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-arrow-right text-primary text-[10px]"></i>
                  </div>
                  @if(!$loop->last)
                    <div class="w-px h-full bg-gray-200"></div>
                  @endif
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-medium text-gray-800">{{ $t->from_status }} &rarr; {{ $t->to_status }}</p>
                  <p class="text-[10px] text-gray-400">{{ $t->actor ? $t->actor->name : 'System' }} &bull; {{ $t->created_at->format('d/m/Y H:i') }}</p>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-sm text-gray-400">Belum ada log transisi.</p>
        @endif
      </div>

      {{-- Kembali ke Arsip --}}
      <a href="{{ route('dashboard.staff.arsip') }}"
         class="block text-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition">
        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Arsip
      </a>

    </div>

  </div>
</div>
@endsection
