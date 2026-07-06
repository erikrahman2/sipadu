@extends('layouts.admin')

@section('title', 'Daftar Validasi')
@section('page-title', 'Daftar Validasi')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Validasi</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-clipboard-check text-primary"></i>
        Daftar Kasus Menunggu Validasi
      </h1>
      <p class="text-gray-500 text-sm mt-1">Kasus yang perlu divalidasi oleh Disdukcapil</p>
    </div>
    <a href="{{ route('dashboard.disdukcapil.archive') }}"
       class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-xl transition">
      <i class="fas fa-archive"></i>
      Lihat Arsip
    </a>
  </div>

  {{-- Alert Messages --}}
  @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-4">
      <p class="text-emerald-800 flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
      </p>
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4">
      <p class="text-red-800 flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
      </p>
    </div>
  @endif

  {{-- Cases Table --}}
  @if($cases->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No. Kasus</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pemohon</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pasangan</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Diajukan</th>
              <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($cases as $case)
              <tr class="hover:bg-amber-50/40 transition">
                <td class="px-6 py-4">
                  <span class="font-semibold text-primary">{{ $case->case_number }}</span>
                  @if($case->source_type === 'public')
                    <br>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 mt-1">
                      <i class="fas fa-globe text-xs"></i> Publik
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4">
                  <p class="font-medium text-gray-900">{{ $case->petitioner_name }}</p>
                  <p class="text-xs text-gray-500">{{ $case->petitioner_nik }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="font-medium text-gray-900">{{ $case->spouse_name }}</p>
                  <p class="text-xs text-gray-500">{{ $case->spouse_nik }}</p>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                    <i class="fas fa-clock mr-1"></i> {{ $case->status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ $case->submitted_at?->format('d/m/Y H:i') ?? '-' }}
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('dashboard.disdukcapil.show', $case->id) }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition"
                       title="Lihat Detail">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('dashboard.disdukcapil.process.show', $case->id) }}"
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-600 hover:text-white transition"
                       title="Proses Validasi">
                      <i class="fas fa-check"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($cases->hasPages())
        <div class="bg-gray-50 border-t border-gray-100 px-6 py-4">
          {{ $cases->links() }}
        </div>
      @endif
    </div>
  @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
      <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <i class="fas fa-inbox text-3xl text-gray-300"></i>
      </div>
      <p class="text-gray-600 text-lg font-medium">Tidak ada kasus yang menunggu validasi</p>
      <p class="text-gray-400 text-sm mt-1">Semua kasus sudah diproses</p>
    </div>
  @endif

</div>
@endsection
