@extends('layouts.public')

@section('title', 'Pengajuan Berhasil Diterima')

@section('content')
<div class="max-w-xl mx-auto py-16 px-4 text-center">
  <h1 class="text-3xl  text-[#31110F] mb-3">Pengajuan Diterima!</h1>
  <p class="text-[#31110F] mb-8">
    Pengajuan Anda telah berhasil kami terima dan sedang dalam antrean verifikasi.
    Token pelacakan telah dikirim ke WhatsApp Anda.
  </p>

  {{-- Token box --}}
  <div class="bg-[#F7F4EB] rounded-2xl shadow-md border border-gray-100 p-6 mb-8">
      <p class="text-sm text-[#31110F] mb-2">Token Pelacakan Anda</p>
    <div class="flex items-center justify-center gap-3">
      <code class="text-xl font-mono  text-[#31110F] tracking-widest" id="tokenDisplay">
        {{ $submission->tracking_token }}
      </code>
      <button onclick="copyToken()" title="Salin"
        class="p-2 text-[#31110F] hover:text-[#31110F] transition" id="copyBtn">
        Salin
      </button>
    </div>
    <p class="text-xs text-[#31110F] mt-3">
      Simpan token ini — Anda tidak memerlukan password untuk memantau status pengajuan.
    </p>
  </div>

  {{-- Status WA --}}
  @if($submission->wa_status === 'sent')
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-[#31110F] text-sm mb-6">
      Token telah dikirim ke WhatsApp Anda.
    </div>
  @elseif($submission->wa_status === 'failed')
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-[#31110F] text-sm mb-6">
      Pengiriman WhatsApp gagal. Simpan token di atas secara manual.
    </div>
  @else
    <div class="bg-brand-light/10 border border-brand/30 rounded-xl px-4 py-3 text-[#31110F] text-sm mb-6">
      Notifikasi WhatsApp sedang diproses...
    </div>
  @endif

  {{-- Detail ringkasan --}}
  <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 text-left p-5 mb-8">
    <h3 class=" text-[#31110F] mb-3">Ringkasan Pengajuan</h3>
    <dl class="space-y-2 text-sm">
      <div class="flex justify-between">
        <dt class="text-[#31110F]">Nama Pemohon</dt>
        <dd class=" text-[#31110F]">{{ $submission->petitioner_name }}</dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-[#31110F]">Status</dt>
        <dd>
          <span class="px-2 py-0.5 rounded-full text-xs  text-[#31110F]
            bg-{{ $submission->statusColor() }}-100">
            {{ $submission->statusLabel() }}
          </span>
        </dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-[#31110F]">Dokumen Diunggah</dt>
        <dd class=" text-[#31110F]">{{ $submission->documents->count() }} file</dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-[#31110F]">Waktu Pengajuan</dt>
        <dd class=" text-[#31110F]">{{ $submission->created_at->translatedFormat('d F Y, H:i') }}</dd>
      </div>
    </dl>
  </div>

  {{-- CTA --}}
  <div class="flex flex-col sm:flex-row gap-3 justify-center">
    <a href="{{ route('public.tracking.token', $submission->tracking_token) }}"
      class="px-6 py-3 bg-brand text-[#F7F4EB]  rounded-xl shadow hover:opacity-90 transition">
      Lacak Status Sekarang
    </a>
    <a href="{{ route('home') }}"
      class="px-6 py-3 border border-gray-300 text-[#31110F]  rounded-xl hover:bg-gray-50 transition">
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
    btn.innerHTML = 'Disalin';
    setTimeout(() => { btn.innerHTML = 'Salin'; }, 2000);
  });
}
</script>
@endpush
