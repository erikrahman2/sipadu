@extends('layouts.admin')

@section('title', 'Detail Kasus')
@section('page-title', 'Detail Kasus')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.disdukcapil.index') }}" class="hover:text-primary">Validasi</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Detail</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Header Card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-primary to-primary-dark px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white">Detail Kasus</h1>
          <p class="text-blue-200 text-sm mt-1">{{ $case->case_number }}</p>
        </div>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-white/20 text-white">
          <i class="fas fa-clock mr-1"></i> {{ $case->status }}
        </span>
      </div>
    </div>
  </div>

  {{-- Action Buttons --}}
  <div class="flex gap-3">
    <a href="{{ route('dashboard.disdukcapil.process.show', $case->id) }}"
       class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-5 rounded-xl transition shadow-sm">
      <i class="fas fa-check-circle"></i>
      Validasi & Upload BAST
    </a>
    <a href="{{ route('dashboard.disdukcapil.index') }}"
       class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 px-5 rounded-xl transition">
      <i class="fas fa-arrow-left"></i>
      Kembali
    </a>
  </div>

  {{-- Case Info Grid --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Pemohon --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-amber-50 px-6 py-3 border-b border-amber-100">
        <h3 class="font-semibold text-amber-800 flex items-center gap-2">
          <i class="fas fa-user"></i> Informasi Pemohon
        </h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">Nama</span>
          <span class="font-medium text-gray-900 text-right">{{ $case->petitioner_name ?? '-' }}</span>
        </div>
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">NIK</span>
          <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ $case->petitioner_nik ?? '-' }}</code>
        </div>
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">Alamat</span>
          <span class="font-medium text-gray-900 text-right text-sm max-w-xs">{{ $case->petitioner_alamat ?? '-' }}</span>
        </div>
        <div class="flex justify-between items-start">
          <span class="text-sm text-gray-500">Kontak</span>
          <span class="font-medium text-gray-900">{{ $case->petitioner_phone ?? '-' }}</span>
        </div>
      </div>
    </div>

    {{-- Pasangan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-teal-50 px-6 py-3 border-b border-teal-100">
        <h3 class="font-semibold text-teal-800 flex items-center gap-2">
          <i class="fas fa-users"></i> Informasi Pasangan
        </h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">Nama</span>
          <span class="font-medium text-gray-900 text-right">{{ $case->spouse_name ?? '-' }}</span>
        </div>
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">NIK</span>
          <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ $case->spouse_nik ?? '-' }}</code>
        </div>
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">Alamat</span>
          <span class="font-medium text-gray-900 text-right text-sm max-w-xs">{{ $case->spouse_alamat ?? '-' }}</span>
        </div>
        <div class="flex justify-between items-start">
          <span class="text-sm text-gray-500">Kecamatan</span>
          <span class="font-medium text-gray-900">{{ $case->spouse_kecamatan ?? '-' }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Permohonan Details --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-orange-50 px-6 py-3 border-b border-orange-100">
      <h3 class="font-semibold text-orange-800 flex items-center gap-2">
        <i class="fas fa-file-alt"></i> Informasi Permohonan
      </h3>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <span class="text-sm text-gray-500 block mb-1">Tanggal Perceraian</span>
          <span class="font-semibold text-gray-900">{{ $case->divorce_date?->format('d M Y') ?? '-' }}</span>
        </div>
        <div>
          <span class="text-sm text-gray-500 block mb-1">Nomor Putusan</span>
          <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $case->verdict_number ?? '-' }}</code>
        </div>
        <div>
          <span class="text-sm text-gray-500 block mb-1">Institusi</span>
          <span class="font-medium text-gray-900">{{ $case->institution?->name ?? '-' }}</span>
        </div>
      </div>
      @if($case->notes)
      <div class="mt-4 pt-4 border-t border-gray-100">
        <span class="text-sm text-gray-500 block mb-1">Catatan</span>
        <p class="text-gray-900">{{ $case->notes }}</p>
      </div>
      @endif
    </div>
  </div>

  {{-- OCR Validation Results --}}
  @if($case->ocrValidations && $case->ocrValidations->count() > 0)
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-indigo-50 px-6 py-3 border-b border-indigo-100">
      <h3 class="font-semibold text-indigo-800 flex items-center gap-2">
        <i class="fas fa-robot"></i> Hasil Validasi OCR
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dokumen</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama (OCR)</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Confidence</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach($case->ocrValidations as $validation)
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">{{ $validation->document->document_type ?? '-' }}</td>
            <td class="px-6 py-4 text-gray-600">{{ $validation->ocr_nama ?? '-' }}</td>
            <td class="px-6 py-4">
              @if($validation->review_action === 'approve')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                  <i class="fas fa-check mr-1"></i> APPROVED
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                  {{ strtoupper($validation->review_action ?? 'PENDING') }}
                </span>
              @endif
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                  <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ round(($validation->ocrResult->overall_confidence ?? $validation->overall_match_score ?? 0) * 100) }}%"></div>
                </div>
                <span class="text-xs text-gray-600">{{ round(($validation->ocrResult->overall_confidence ?? $validation->overall_match_score ?? 0) * 100, 1) }}%</span>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- Dokumen Uploaded --}}
  @php
      $uploadedDocs = $case->documents ?? collect();
  @endphp

  @if($uploadedDocs->count() > 0)
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-purple-50 px-6 py-3 border-b border-purple-100">
      <h3 class="font-semibold text-purple-800 flex items-center gap-2">
        <i class="fas fa-folder-open"></i> Dokumen yang Diupload
        <span class="ml-auto text-xs bg-purple-200 text-purple-800 px-2 py-0.5 rounded-full">{{ $uploadedDocs->count() }} file</span>
      </h3>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($uploadedDocs as $doc)
        <div class="border border-gray-200 rounded-xl p-4 hover:border-primary hover:shadow-md transition">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-file-alt text-gray-400"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-900 text-sm truncate" title="{{ $doc->original_name ?? 'Document' }}">
                {{ $doc->original_name ?? 'Document' }}
              </p>
              <p class="text-xs text-gray-500 mt-0.5">{{ $doc->document_type ?? '-' }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ number_format(($doc->size_bytes ?? 0) / 1024, 1) }} KB</p>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
            <a href="{{ route('dashboard.disdukcapil.document.download', ['id' => $case->id, 'docId' => $doc->id]) }}"
               class="inline-flex items-center gap-1 text-primary hover:text-primary-dark text-sm font-medium transition">
              <i class="fas fa-download"></i> Download
            </a>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

</div>
@endsection
