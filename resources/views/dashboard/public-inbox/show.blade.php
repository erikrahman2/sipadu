@extends('layouts.admin')

@section('title', 'Detail Pengajuan Publik')

@section('content')
<div class="max-w-5xl mx-auto">

  {{-- Header dengan nama user --}}
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Detail Pengajuan</h1>
    <div class="flex items-center gap-2">
      <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
        {{ substr(auth()->user()->name, 0, 1) }}
      </div>
      <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
    </div>
  </div>

  {{-- Breadcrumb --}}
  <nav class="text-sm text-gray-500 mb-5">
    <span class="text-gray-700">Kotak Masuk</span>
    <span class="mx-2">/</span>
    <span class="font-mono text-gray-900 font-medium">{{ $submission->tracking_token }}</span>
  </nav>

  {{-- Notifikasi --}}
  @if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm flex items-center gap-2">
      <i class="fas fa-check-circle"></i>
      <span>{{ session('success') }}</span>
    </div>
  @endif
  @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- Token & Status Card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
    <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Token Tracking</div>
    <div class="font-mono text-lg font-bold text-blue-700 mb-3">{{ $submission->tracking_token }}</div>

    @php
      $statusColors = [
        'PENDING' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Menunggu Verifikasi'],
        'REVIEWING' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Sedang Ditinjau'],
        'WAITING_OCR' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'label' => 'Menunggu OCR'],
        'APPROVED' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Disetujui'],
        'REJECTED' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Ditolak'],
        'COMPLETED' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Selesai'],
      ];
      $status = $statusColors[$submission->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $submission->status];
    @endphp

    <span class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold {{ $status['bg'] }} {{ $status['text'] }}">
      {{ $status['label'] }}
    </span>

    <div class="mt-4 flex items-center gap-2 text-sm text-gray-600">
      <i class="far fa-clock text-gray-400"></i>
      <span>Diajukan</span>
      <span class="text-gray-800 font-medium">{{ $submission->created_at->translatedFormat('d M Y, H:i') }}</span>
    </div>
  </div>

  {{-- Notifikasi WhatsApp --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-800 text-base">Notifikasi WhatsApp</h3>
      @php 
        $waColors = [
          'sent' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800'],
          'delivered' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800'],
          'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800'],
          'pending' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
        ];
        $waStatus = $waColors[$submission->wa_status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
      @endphp
      <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $waStatus['bg'] }} {{ $waStatus['text'] }}">
        {{ strtoupper($submission->wa_status ?? 'PENDING') }}
      </span>
    </div>

    <div class="text-sm text-gray-600 mb-2">
      <span class="text-gray-500">Ke:</span> 
      <span class="font-mono font-medium text-gray-800">{{ $submission->phone_wa }}</span>
    </div>

    @if($submission->wa_error)
      <div class="text-xs text-red-600 bg-red-50 rounded-lg p-2 mb-3">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        {{ $submission->wa_error }}
      </div>
    @endif

    <form method="POST" action="{{ route('dashboard.public-inbox.resend_wa', $submission->id) }}">
      @csrf
      <button type="submit"
        class="w-full py-2.5 text-sm font-medium bg-green-600 text-white rounded-xl hover:bg-green-700 transition flex items-center justify-center gap-2">
        <i class="fab fa-whatsapp"></i>
        <span>Kirim Ulang WA</span>
      </button>
    </form>
  </div>

  {{-- Data Pemohon --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-blue-50 to-blue-100/50 px-6 py-4 border-b border-blue-200">
      <h3 class="font-semibold text-gray-800 text-base">Data Pemohon</h3>
    </div>
    <div class="p-6">
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">NIK</dt>
          <dd class="font-mono text-base font-semibold text-gray-900">{{ $submission->nik }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nama Lengkap</dt>
          <dd class="text-base font-medium text-gray-900">{{ $submission->petitioner_name }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nomor WA</dt>
          <dd class="font-mono text-base text-gray-900">{{ $submission->phone_wa }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">IP Address</dt>
          <dd class="font-mono text-sm text-gray-500">{{ $submission->ip_address ?? '-' }}</dd>
        </div>
      </dl>
    </div>
  </div>

  {{-- Data Perceraian --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-purple-50 to-purple-100/50 px-6 py-4 border-b border-purple-200">
      <h3 class="font-semibold text-gray-800 text-base">Data Perceraian</h3>
    </div>
    <div class="p-6">
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nama Mantan Pasangan</dt>
          <dd class="text-base text-gray-900">{{ $submission->respondent_name ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">NIK Mantan Pasangan</dt>
          <dd class="font-mono text-base text-gray-900">{{ $submission->respondent_nik ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Tanggal Cerai</dt>
          <dd class="text-base text-gray-900">{{ $submission->divorce_date?->translatedFormat('d F Y') ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nomor Putusan PA</dt>
          <dd class="text-base text-gray-900">{{ $submission->verdict_number ?? '-' }}</dd>
        </div>
        @if($submission->notes)
        <div class="col-span-2">
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Catatan</dt>
          <dd class="text-sm text-gray-700 bg-gray-50 rounded-lg p-4 whitespace-pre-wrap leading-relaxed">{{ $submission->notes }}</dd>
        </div>
        @endif
      </dl>
    </div>
  </div>

  {{-- Dokumen Diunggah --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-emerald-50 to-emerald-100/50 px-6 py-4 border-b border-emerald-200">
      <h3 class="font-semibold text-gray-800 text-base">Dokumen Diunggah ({{ $submission->documents->count() }})</h3>
    </div>
    <ul class="divide-y divide-gray-100">
      @forelse($submission->documents as $doc)
        <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl {{ str_contains($doc->mime_type, 'pdf') ? 'bg-red-100' : 'bg-blue-100' }} flex items-center justify-center flex-shrink-0">
              <i class="fas fa-{{ str_contains($doc->mime_type, 'pdf') ? 'file-pdf text-red-500' : 'image text-blue-500' }} text-lg"></i>
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900 mb-0.5">
                {{ \App\Models\PublicSubmissionDocument::$typeLabels[$doc->document_type] ?? $doc->document_type }}
              </div>
              <div class="text-xs text-gray-500">
                {{ $doc->original_filename }} · 
                <span class="text-gray-400">{{ $doc->humanFileSize() }}</span>
              </div>
            </div>
          </div>
          <span class="text-sm text-gray-500">{{ $doc->created_at->translatedFormat('d M Y') }}</span>
        </li>
      @empty
        <li class="px-6 py-8 text-center text-gray-400 text-sm">
          <i class="far fa-folder-open text-3xl mb-2 opacity-30"></i>
          <div>Tidak ada dokumen</div>
        </li>
      @endforelse
    </ul>
  </div>

</div>

@endsection
