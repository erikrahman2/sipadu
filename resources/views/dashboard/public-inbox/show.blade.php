@extends('layouts.admin')

@section('title', 'Detail Pengajuan Publik')

@section('content')
<div class="max-w-5xl mx-auto">

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

  {{-- Data Pasangan (Grid 2 Kolom) --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Data Suami --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-blue-50 to-blue-100/50 px-6 py-4 border-b border-blue-200">
        <h3 class="font-semibold text-gray-800 text-base">Data Suami</h3>
      </div>
      <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">NIK</dt>
            <dd class="font-mono text-base font-semibold text-gray-900">{{ $submission->nik ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nama Lengkap</dt>
            <dd class="text-base font-medium text-gray-900">{{ $submission->petitioner_name ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nomor Telepon</dt>
            <dd class="font-mono text-base text-gray-900">{{ $submission->phone_wa ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Alamat</dt>
            <dd class="text-base text-gray-900">{{ $submission->alamat ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">RT/RW</dt>
            <dd class="font-mono text-base text-gray-900">{{ $submission->rt_rw ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Kelurahan</dt>
            <dd class="text-base text-gray-900">{{ $submission->kelurahan ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Kecamatan</dt>
            <dd class="text-base text-gray-900">{{ $submission->kecamatan ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">No. Kartu Keluarga</dt>
            <dd class="font-mono text-base text-gray-900">{{ $submission->no_kk ?? '-' }}</dd>
          </div>
        </dl>
      </div>
    </div>

    {{-- Data Istri --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-teal-50 to-teal-100/50 px-6 py-4 border-b border-teal-200">
        <h3 class="font-semibold text-gray-800 text-base">Data Istri</h3>
      </div>
      <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">NIK</dt>
            <dd class="font-mono text-base font-semibold text-gray-900">{{ $submission->respondent_nik ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nama Lengkap</dt>
            <dd class="text-base font-medium text-gray-900">{{ $submission->respondent_name ?? '-' }}</dd>
          </div>
        </dl>
      </div>
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
    <div class="p-6">
      @forelse($submission->documents as $doc)
        @if($loop->first)
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @endif
            <div class="group relative bg-gray-50 rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition">
              <a href="{{ $doc->file_url }}" target="_blank" class="block aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
                @if(str_contains($doc->mime_type, 'image'))
                  <img src="{{ $doc->file_url }}" alt="{{ $doc->original_filename }}" class="w-full h-full object-cover">
                @else
                  <div class="flex flex-col items-center justify-center w-full h-full bg-gradient-to-br from-gray-100 to-gray-200">
                    <i class="fas fa-file-pdf text-4xl text-red-500 mb-2"></i>
                    <span class="text-xs text-gray-600">PDF</span>
                  </div>
                @endif
              </a>
              <div class="p-3 bg-white">
                <div class="text-xs font-medium text-emerald-700 mb-1">
                  {{ \App\Models\PublicSubmissionDocument::$typeLabels[$doc->document_type] ?? $doc->document_type }}
                </div>
                <div class="text-xs text-gray-700 font-medium mb-1 truncate">{{ $doc->original_filename }}</div>
                <div class="text-xs text-gray-500">{{ $doc->humanFileSize() }}</div>
              </div>
            </div>
        @if($loop->last)
          </div>
        @endif
      @empty
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
          <i class="far fa-folder-open text-4xl mb-3 opacity-30"></i>
          <div class="text-sm">Tidak ada dokumen</div>
        </div>
      @endforelse
    </div>
  </div>

</div>

@endsection
