@extends('layouts.admin')

@section('title', 'Hasil OCR')
@section('page-title', 'Hasil OCR')

<!-- DEBUG: File served at {{ now()->timestamp }} - FLEXBOX LAYOUT ACTIVE -->

@section('content')
<div class="w-full space-y-6">

  {{-- Header --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-microscope mr-2 text-indigo-500"></i>Hasil OCR
      </h1>
      <p class="text-sm text-gray-500 mt-1">{{ $document->original_name }}</p>
    </div>
    @if($document->ocrResult)
    <div class="text-right">
      <p class="text-2xl font-bold {{ $document->ocrResult->overall_confidence >= 0.85 ? 'text-green-500' : ($document->ocrResult->overall_confidence >= 0.70 ? 'text-yellow-500' : 'text-red-500') }}">
        {{ round($document->ocrResult->overall_confidence * 100) }}%
      </p>
      <p class="text-xs text-gray-400">Confidence</p>
    </div>
    @endif
  </div>

  @if(!$document->ocrResult)
  <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-8 text-center">
    <i class="fas fa-clock text-4xl text-yellow-400 mb-3 block"></i>
    <p class="text-yellow-700 font-medium">Hasil OCR belum tersedia.</p>
    <p class="text-sm text-yellow-600 mt-1">Dokumen mungkin masih dalam antrian pemrosesan.</p>
  </div>
  @else
  @php $ocr = $document->ocrResult; @endphp

  {{-- OCR Status --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-4 mb-6">
      <span class="px-3 py-1 rounded-full text-sm font-semibold
        {{ $ocr->ocr_status === 'SUCCESS' ? 'bg-green-100 text-green-700' :
           ($ocr->ocr_status === 'PARTIAL' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
        {{ $ocr->ocr_status }}
      </span>
      <span class="text-sm text-gray-500">
        <i class="fas fa-clock mr-1"></i>{{ $ocr->processing_time_ms }}ms
      </span>
      <span class="text-sm text-gray-500">
        Engine: {{ $ocr->engine_version ?? 'Tesseract 5.x' }}
      </span>
    </div>

    {{-- Extracted Fields --}}
    <h3 class="font-semibold text-gray-700 mb-4">Data Terekstraksi</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse" style="table-layout: fixed;">
        <thead>
          <tr class="bg-gray-100 border-b border-gray-300">
            <th class="text-left px-3 py-2 font-semibold text-gray-700" style="width: 20%;">Field</th>
            <th class="text-left px-3 py-2 font-semibold text-gray-700" style="width: 25%;">Manual</th>
            <th class="text-left px-3 py-2 font-semibold text-gray-700" style="width: 40%;">OCR</th>
            <th class="text-center px-3 py-2 font-semibold text-gray-700" style="width: 15%;">%</th>
          </tr>
        </thead>
        <tbody>
          @foreach([
            ['label' => 'NIK', 'value' => $ocr->nik, 'conf_key' => 'nik'],
            ['label' => 'No. KK', 'value' => $ocr->no_kk, 'conf_key' => 'kk'],
            ['label' => 'Nama', 'value' => $ocr->nama, 'conf_key' => 'nama'],
            ['label' => 'Tgl Lahir', 'value' => $ocr->tgl_lahir, 'conf_key' => 'tgl_lahir'],
            ['label' => 'Tempat Lahir', 'value' => $ocr->tempat_lahir, 'conf_key' => null],
            ['label' => 'Jenis Kelamin', 'value' => $ocr->jenis_kelamin, 'conf_key' => null],
            ['label' => 'Alamat', 'value' => $ocr->alamat, 'conf_key' => null],
            ['label' => 'RT/RW', 'value' => $ocr->rt_rw, 'conf_key' => null],
            ['label' => 'Kelurahan', 'value' => $ocr->kelurahan, 'conf_key' => null],
            ['label' => 'Kecamatan', 'value' => $ocr->kecamatan, 'conf_key' => null],
            ['label' => 'Kabupaten', 'value' => $ocr->kabupaten, 'conf_key' => null],
          ] as $field)
          @php
            $conf = $field['conf_key'] && isset($ocr->confidence_scores[$field['conf_key']]) 
              ? $ocr->confidence_scores[$field['conf_key']] 
              : null;
            $confText = $conf ? round($conf * 100) . '%' : '—';
            $confColor = $conf >= 0.85 ? 'text-green-600' : ($conf >= 0.70 ? 'text-yellow-600' : 'text-red-600');
            $bgColor = $conf ? ($conf >= 0.85 ? 'bg-green-50' : ($conf >= 0.70 ? 'bg-yellow-50' : 'bg-red-50')) : 'bg-gray-50';
          @endphp
          <tr class="{{ $bgColor }} border-b border-gray-200">
            <td class="px-3 py-3 font-medium text-gray-800 break-words">{{ $field['label'] }}</td>
            <td class="px-3 py-3 text-gray-600 break-words">—</td>
            <td class="px-3 py-3 font-mono text-gray-800 break-all text-xs">{{ $field['value'] ?? '—' }}</td>
            <td class="px-3 py-3 text-center font-bold {{ $confColor }}">{{ $confText }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Confidence Visual --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-700 mb-4">Confidence Score</h3>
    @foreach($ocr->confidence_scores ?? [] as $field => $score)
    <div class="mb-3">
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600 capitalize">{{ $field }}</span>
        <span class="font-bold {{ $score >= 0.85 ? 'text-green-500' : ($score >= 0.70 ? 'text-yellow-500' : 'text-red-500') }}">
          {{ round($score * 100) }}%
        </span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all {{ $score >= 0.85 ? 'bg-green-400' : ($score >= 0.70 ? 'bg-yellow-400' : 'bg-red-400') }}"
             style="width: {{ round($score * 100) }}%"></div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- JSON Output --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-700 mb-3">
      <i class="fas fa-code mr-2 text-gray-400"></i>JSON Output
    </h3>
    <pre class="bg-gray-900 text-green-400 rounded-xl p-4 text-xs overflow-x-auto">{{ json_encode($ocr->toValidatedArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
  </div>
  @endif

</div>
@endsection
