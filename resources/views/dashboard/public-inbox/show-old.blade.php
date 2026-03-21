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

      {{-- WA Status --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-700 text-sm">Notifikasi WhatsApp</h3>
          @php $wc = ['sent'=>'emerald','delivered'=>'emerald','failed'=>'red','pending'=>'gray'][$submission->wa_status] ?? 'gray'; @endphp
          <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $wc }}-100 text-{{ $wc }}-800">
            {{ strtoupper($submission->wa_status) }}
          </span>
        </div>
        <div class="text-sm text-gray-600 mb-3">Ke: <span class="font-mono">{{ $submission->phone_wa }}</span></div>
        @if($submission->wa_error)
          <div class="text-xs text-red-600 mb-3">Error: {{ $submission->wa_error }}</div>
        @endif
        @if($submission->wa_sent_at)
          <div class="text-xs text-gray-400 mb-3">Dikirim: {{ $submission->wa_sent_at->translatedFormat('d M Y, H:i') }}</div>
        @endif
        <form method="POST" action="{{ route('dashboard.public-inbox.resend_wa', $submission->id) }}">
          @csrf
          <button type="submit"
            class="w-full py-2 text-sm bg-green-600 text-white rounded-xl hover:bg-green-700 transition">
            <i class="fab fa-whatsapp mr-1"></i> Kirim Ulang WA
          </button>
        </form>
      </div>

      {{-- Aksi Proses --}}
      @if(!in_array($submission->status, ['APPROVED','COMPLETED','REJECTED']))
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
        <h3 class="font-semibold text-gray-700 text-sm mb-1">Tindakan</h3>

        @if($submission->status === 'PENDING')
          <form method="POST" action="{{ route('dashboard.public-inbox.review', $submission->id) }}">
            @csrf
            <button type="submit"
              class="w-full py-2 text-sm bg-blue-700 text-white rounded-xl hover:bg-blue-800 transition">
              <i class="fas fa-eye mr-1"></i> Mulai Tinjau
            </button>
          </form>
        @endif

        {{-- Setujui & Buat Kasus --}}
        <button onclick="document.getElementById('approveModal').classList.remove('hidden')"
          class="w-full py-2 text-sm bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
          <i class="fas fa-check mr-1"></i> Setujui & Buat Kasus
        </button>

        {{-- Tolak --}}
        <button onclick="document.getElementById('rejectModal').classList.remove('hidden')"
          class="w-full py-2 text-sm border border-red-300 text-red-600 rounded-xl hover:bg-red-50 transition">
          <i class="fas fa-times mr-1"></i> Tolak Pengajuan
        </button>
      </div>
      @endif

    </div>

    {{-- ── Panel Kanan: Data Lengkap ───────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-5">

      {{-- Data Pemohon --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800">Data Pemohon</h3>
        </div>
        <dl class="p-5 grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">NIK</dt>
            <dd class="font-mono font-semibold text-gray-900">{{ $submission->nik }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">Nama Lengkap</dt>
            <dd class="font-medium text-gray-900">{{ $submission->petitioner_name }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">Nomor WA</dt>
            <dd class="font-mono text-gray-900">{{ $submission->phone_wa }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">IP Address</dt>
            <dd class="font-mono text-gray-500 text-xs">{{ $submission->ip_address ?? '-' }}</dd>
          </div>
        </dl>
      </div>

      {{-- Data Perceraian --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800">Data Perceraian</h3>
        </div>
        <dl class="p-5 grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">Nama Mantan Pasangan</dt>
            <dd class="text-gray-900">{{ $submission->respondent_name ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">NIK Mantan Pasangan</dt>
            <dd class="font-mono text-gray-900">{{ $submission->respondent_nik ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">Tanggal Cerai</dt>
            <dd class="text-gray-900">{{ $submission->divorce_date?->translatedFormat('d F Y') ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs uppercase mb-1">Nomor Putusan PA</dt>
            <dd class="text-gray-900">{{ $submission->verdict_number ?? '-' }}</dd>
          </div>
          @if($submission->notes)
          <div class="col-span-2">
            <dt class="text-gray-500 text-xs uppercase mb-1">Catatan</dt>
            <dd class="text-gray-900 whitespace-pre-wrap text-sm bg-gray-50 rounded-lg p-3">{{ $submission->notes }}</dd>
          </div>
          @endif
        </dl>
      </div>

      {{-- Dokumen --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-800">Dokumen Diunggah ({{ $submission->documents->count() }})</h3>
        </div>
        <ul class="divide-y divide-gray-50">
          @forelse($submission->documents as $doc)
            <li class="px-5 py-3 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                  <i class="fas fa-{{ str_contains($doc->mime_type, 'pdf') ? 'file-pdf text-red-500' : 'image text-blue-500' }} text-sm"></i>
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-800">
                    {{ \App\Models\PublicSubmissionDocument::$typeLabels[$doc->document_type] ?? $doc->document_type }}
                  </div>
                  <div class="text-xs text-gray-400">{{ $doc->original_filename }} · {{ $doc->humanFileSize() }}</div>
                </div>
              </div>
              {{-- Download link (perlu route tersendiri jika ingin download) --}}
              <span class="text-xs text-gray-400">{{ $doc->created_at->translatedFormat('d M Y') }}</span>
            </li>
          @empty
            <li class="px-5 py-6 text-center text-gray-400 text-sm">Tidak ada dokumen.</li>
          @endforelse
        </ul>
      </div>

    </div>
  </div>
</div>

{{-- ── Modal: Setujui ──────────────────────────────────────────────────────── --}}
<div id="approveModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Setujui & Buat Kasus Resmi</h3>
    <form method="POST" action="{{ route('dashboard.public-inbox.approve', $submission->id) }}">
      @csrf
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Institusi Penerima <span class="text-red-500">*</span></label>
        <select name="institution_id" required
          class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">— Pilih institusi —</option>
          @foreach($institutions as $inst)
            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
        <textarea name="notes" rows="2"
          class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Catatan untuk kasus..."></textarea>
      </div>
      <div class="flex gap-3">
        <button type="submit" class="flex-1 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition">Setujui</button>
        <button type="button" onclick="document.getElementById('approveModal').classList.add('hidden')"
          class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition">Batal</button>
      </div>
    </form>
  </div>
</div>

{{-- ── Modal: Tolak ────────────────────────────────────────────────────────── --}}
<div id="rejectModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Tolak Pengajuan</h3>
    <form method="POST" action="{{ route('dashboard.public-inbox.reject', $submission->id) }}">
      @csrf
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
        <textarea name="reject_reason" rows="3" required
          class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-400"
          placeholder="Contoh: Dokumen KTP tidak jelas / NIK tidak sesuai..."></textarea>
      </div>
      <div class="flex gap-3">
        <button type="submit" class="flex-1 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 transition">Tolak</button>
        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
          class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition">Batal</button>
      </div>
    </form>
  </div>
</div>
@endsection
