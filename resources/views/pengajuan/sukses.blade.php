@extends('layouts.app')

@section('title', 'Pengajuan Berhasil Diterima')

@section('content')
<div class="max-w-xl mx-auto py-16 px-4 text-center">

  {{-- Icon sukses --}}
  <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 mb-6">
    <svg class="w-12 h-12 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  </div>

  <h1 class="text-3xl font-extrabold text-gray-900 mb-3">Pengajuan Diterima!</h1>
  <p class="text-gray-500 mb-8">
    Pengajuan Anda telah berhasil kami terima dan sedang dalam antrean verifikasi.
    Token pelacakan telah dikirim ke WhatsApp Anda.
  </p>

  {{-- Token box --}}
  <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 mb-8">
    <p class="text-sm text-gray-500 mb-2">Token Pelacakan Anda</p>
    <div class="flex items-center justify-center gap-3">
      <code class="text-xl font-mono font-bold text-blue-700 tracking-widest" id="tokenDisplay">
        {{ $submission->tracking_token }}
      </code>
      <button onclick="copyToken()" title="Salin"
        class="p-2 text-gray-400 hover:text-blue-600 transition" id="copyBtn">
        <i class="fas fa-copy"></i>
      </button>
    </div>
    <p class="text-xs text-gray-400 mt-3">
      Simpan token ini — Anda tidak memerlukan password untuk memantau status pengajuan.
    </p>
  </div>

  {{-- Status WA --}}
  @if($submission->wa_status === 'sent')
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm mb-6">
      <i class="fab fa-whatsapp mr-1 text-base"></i>
      Token telah dikirim ke WhatsApp Anda.
    </div>
  @elseif($submission->wa_status === 'failed')
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-yellow-700 text-sm mb-6">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      Pengiriman WhatsApp gagal. Simpan token di atas secara manual.
    </div>
  @else
    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-blue-600 text-sm mb-6">
      <i class="fas fa-clock mr-1"></i>
      Notifikasi WhatsApp sedang diproses...
    </div>
  @endif

  {{-- Detail ringkasan --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 text-left p-5 mb-8">
    <h3 class="font-semibold text-gray-700 mb-3">Ringkasan Pengajuan</h3>
    <dl class="space-y-2 text-sm">
      <div class="flex justify-between">
        <dt class="text-gray-500">Nama Pemohon</dt>
        <dd class="font-medium text-gray-800">{{ $submission->petitioner_name }}</dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-gray-500">Status</dt>
        <dd>
          <span class="px-2 py-0.5 rounded-full text-xs font-semibold
            bg-{{ $submission->statusColor() }}-100 text-{{ $submission->statusColor() }}-800">
            {{ $submission->statusLabel() }}
          </span>
        </dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-gray-500">Dokumen Diunggah</dt>
        <dd class="font-medium text-gray-800">{{ $submission->documents->count() }} file</dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-gray-500">Waktu Pengajuan</dt>
        <dd class="font-medium text-gray-800">{{ $submission->created_at->translatedFormat('d F Y, H:i') }}</dd>
      </div>
    </dl>
  </div>

  {{-- CTA --}}
  <div class="flex flex-col sm:flex-row gap-3 justify-center">
    <a href="{{ route('public.tracking.token', $submission->tracking_token) }}"
      class="px-6 py-3 bg-blue-700 text-white font-semibold rounded-xl shadow hover:bg-blue-800 transition">
      <i class="fas fa-search mr-1"></i> Lacak Status Sekarang
    </a>
    <a href="{{ route('home') }}"
      class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
      Kembali ke Beranda
    </a>
  </div>

</div>
@endsection

@push('scripts')
<script>
function copyToken() {
  const token = document.getElementById('tokenDisplay').textContent.trim();
  navigator.clipboard.writeText(token).then(() => {
    const btn = document.getElementById('copyBtn');
    btn.innerHTML = '<i class="fas fa-check text-emerald-500"></i>';
    setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
  });
}
</script>
@endpush
