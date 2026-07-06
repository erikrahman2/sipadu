@extends('layouts.admin')

@section('title', 'Arsip Validasi')
@section('page-title', 'Arsip Validasi')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.disdukcapil.index') }}" class="hover:text-primary">Validasi</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Arsip</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-archive text-primary"></i>
        Arsip Dokumen
      </h1>
      <p class="text-gray-500 text-sm mt-1">Kasus yang sudah diproses dan diarsipkan</p>
    </div>
    <a href="{{ route('dashboard.disdukcapil.index') }}"
       class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-xl transition">
      <i class="fas fa-arrow-left"></i>
      Kembali
    </a>
  </div>

  {{-- Stats Cards --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">Total Diarsipkan</p>
          <p class="text-3xl font-bold text-gray-900 mt-1">{{ $cases->total() }}</p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center">
          <i class="fas fa-archive text-xl text-indigo-600"></i>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">Bulan Ini</p>
          <p class="text-3xl font-bold text-emerald-600 mt-1">
            {{ $cases->where('updated_at', '>=', now()->startOfMonth())->count() }}
          </p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
          <i class="fas fa-calendar-check text-xl text-emerald-600"></i>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500">Dengan BAST</p>
          <p class="text-3xl font-bold text-amber-600 mt-1">
            {{ $cases->whereNotNull('documents')->count() }}
          </p>
        </div>
        <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
          <i class="fas fa-file-alt text-xl text-amber-600"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- Cases Table --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No. Kasus</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pemohon</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pasangan</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tgl Perceraian</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dokumen</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($cases as $case)
          <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4">
              <span class="font-semibold text-gray-900">{{ $case->case_number }}</span>
              <br>
              <span class="text-xs text-gray-400">{{ $case->tracking_token }}</span>
            </td>
            <td class="px-6 py-4">
              <p class="font-medium text-gray-900">{{ $case->petitioner_name }}</p>
              <p class="text-xs text-gray-500">{{ $case->petitioner_nik }}</p>
            </td>
            <td class="px-6 py-4">
              <p class="font-medium text-gray-900">{{ $case->spouse_name }}</p>
              <p class="text-xs text-gray-500">{{ $case->spouse_nik }}</p>
            </td>
            <td class="px-6 py-4 text-gray-600">
              {{ $case->divorce_date?->format('d/m/Y') ?? '-' }}
            </td>
            <td class="px-6 py-4">
              @php
                $bastCount = $case->documents->where('document_type', 'BAST')->count();
                $digitalCount = $case->documents->where('document_type', 'DIGITAL_COPY')->count();
              @endphp
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 mr-1">
                BAST: {{ $bastCount }}
              </span>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Digital: {{ $digitalCount }}
              </span>
            </td>
            <td class="px-6 py-4">
              <a href="{{ route('dashboard.disdukcapil.show', $case->id) }}"
                 class="inline-flex items-center gap-1 text-primary hover:text-primary-dark font-medium text-sm transition">
                <i class="fas fa-eye"></i> Detail
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="px-6 py-12 text-center">
              <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-archive text-3xl text-gray-300"></i>
              </div>
              <p class="text-gray-400 font-medium">Belum ada arsip dokumen</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($cases->hasPages())
    <div class="bg-gray-50 border-t border-gray-100 px-6 py-4">
      {{ $cases->links() }}
    </div>
    @endif
  </div>

</div>
@endsection
