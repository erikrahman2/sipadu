@extends('layouts.public')

@section('title', 'Tentang SiPadu - Sistem Pembaruan Dokumen Pasca Perceraian')

@push('styles')
<style>
  .fade-up {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s cubic-bezier(.22,.61,.36,1),
                transform 0.7s cubic-bezier(.22,.61,.36,1);
  }
  .fade-up.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .fade-up-delay-1 { transition-delay: 0.1s; }
  .fade-up-delay-2 { transition-delay: 0.2s; }
  .fade-up-delay-3 { transition-delay: 0.3s; }
  .observe-fade {
    will-change: opacity, transform;
  }
</style>
@endpush

@section('content')

{{-- Hero Banner --}}
<div class="relative overflow-hidden bg-[#F7F4EB]">
  <div class="absolute inset-0">
    <div class="gradient-blur-1 bg-[#0D1F08] top-[-200px] right-[-200px]"></div>
    <div class="gradient-blur-1 bg-[#0891b2] bottom-[-200px] left-[-200px]"></div>
  </div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
    <div class="text-center max-w-4xl mx-auto">
      <h1 class="text-4xl md:text-5xl font-extrabold text-[#31110F] leading-tight mb-6 observe-fade fade-up">
        Kami merombak arsitektur
        <br><em class="italic">pembaruan dokumen kependudukan.</em>
      </h1>
      <p class="text-lg md:text-xl text-[#31110F]/70 observe-fade fade-up fade-up-delay-1">
        Menghubungkan Pengadilan Agama dan Dinas Kependudukan untuk proses yang lebih cepat, transparan, dan tanpa harus bolak-balik kantor.
      </p>
    </div>
  </div>
</div>

{{-- About SiPadu Section --}}
@php
  $aboutSections = $sections ?? collect();
  $tentangSection = $aboutSections->firstWhere('section_key', 'tentang_sipadu');
  $visiMisiSection = $aboutSections->firstWhere('section_key', 'visi_misi');
  $fiturSection = $aboutSections->firstWhere('section_key', 'fitur_keunggulan');
  $institusiSection = $aboutSections->firstWhere('section_key', 'institusi_pendukung');
@endphp

{{-- Tentang SiPadu --}}
@if($tentangSection)
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-[#F7F4EB]">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div class="observe-fade fade-up">
        <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Tentang Kami</span>
        <h2 class="text-3xl md:text-4xl font-extrabold text-[#31110F] leading-tight mb-6">
          SiPadu adalah jembatan antara<br>instansi pemerintah dan masyarakat.
        </h2>
        <div class="text-[#31110F]/80 text-base leading-relaxed">
          {!! $tentangSection->content !!}
        </div>
      </div>
      <div class="relative observe-fade fade-up">
        <div class="bg-[#0D1F08] rounded-3xl p-8 text-[#F7F4EB]">
          <div class="text-6xl font-extrabold mb-2">2</div>
          <div class="text-lg font-medium mb-4">Instansi Terhubung</div>
          <div class="space-y-3 text-sm text-[#FFF0C4]">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <span>Pengadilan Agama — Verifikasi & Putusan</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <span>Disdukcapil — Validasi & Penerbitan</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endif

{{-- Visi & Misi --}}
@if($visiMisiSection)
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-14 observe-fade fade-up">
      <h2 class="text-3xl md:text-4xl font-extrabold text-[#31110F]">{{ $visiMisiSection->title }}</h2>
    </div>
    <div class="grid md:grid-cols-2 gap-8">
      {{-- Visi Card --}}
      <div class="bg-[#F7F4EB] rounded-2xl p-8 observe-fade fade-up">
        <div class="w-12 h-12 rounded-xl bg-[#0D1F08] flex items-center justify-center mb-6">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </div>
        <h3 class="text-xl font-bold text-[#31110F] mb-3">Visi</h3>
        <p class="text-[#31110F]/80 leading-relaxed">
          {!! Str::of($visiMisiSection->content)->before('<strong>Misi:') !!}
        </p>
      </div>
      {{-- Misi Card --}}
      <div class="bg-[#0D1F08] rounded-2xl p-8 text-[#F7F4EB] observe-fade fade-up fade-up-delay-1">
        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center mb-6">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="text-xl font-bold mb-3">Misi</h3>
        <p class="text-[#FFF0C4] leading-relaxed">
          {!! Str::of($visiMisiSection->content)->after('<strong>Misi:</strong> ') !!}
        </p>
      </div>
    </div>
  </div>
</section>
@endif

{{-- Fitur & Keunggulan --}}
@if($fiturSection)
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-[#F7F4EB]">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-14">
      <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Keunggulan</span>
      <h2 class="text-3xl md:text-4xl font-extrabold text-[#31110F]">{{ $fiturSection->title }}</h2>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      {{-- Feature 1: Pengajuan Tanpa Akun --}}
      <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow observe-fade fade-up">
        <div class="w-12 h-12 rounded-xl bg-[#0D1F08] flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <h3 class="text-base font-bold text-[#31110F] mb-2">Tanpa Akun</h3>
        <p class="text-sm text-[#31110F]/70 leading-relaxed">Pengajuan langsung tanpa perlu registrasi. Cukup NIK dan nomor WhatsApp.</p>
      </div>
      {{-- Feature 2: OCR Otomatis --}}
      <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow observe-fade fade-up fade-up-delay-1">
        <div class="w-12 h-12 rounded-xl bg-[#0D1F08] flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-base font-bold text-[#31110F] mb-2">OCR Otomatis</h3>
        <p class="text-sm text-[#31110F]/70 leading-relaxed">Ekstraksi data dari dokumen secara otomatis dengan teknologi OCR.</p>
      </div>
      {{-- Feature 3: Verifikasi Berlapis --}}
      <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow observe-fade fade-up fade-up-delay-2">
        <div class="w-12 h-12 rounded-xl bg-[#0D1F08] flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <h3 class="text-base font-bold text-[#31110F] mb-2">Verifikasi Berlapis</h3>
        <p class="text-sm text-[#31110F]/70 leading-relaxed">Setiap dokumen diverifikasi oleh PA dan Disdukcapil untuk kepastian hukum.</p>
      </div>
      {{-- Feature 4: Pelacakan Real-Time --}}
      <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow observe-fade fade-up fade-up-delay-3">
        <div class="w-12 h-12 rounded-xl bg-[#0D1F08] flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-base font-bold text-[#31110F] mb-2">Pelacakan Real-Time</h3>
        <p class="text-sm text-[#31110F]/70 leading-relaxed">Lacak status pengajuan kapan saja melalui token yang dikirim via WhatsApp.</p>
      </div>
    </div>
  </div>
</section>
@endif

{{-- Institusi Pendukung --}}
@if($institusiSection)
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-2 gap-12 items-start">
      <div class="observe-fade fade-up">
        <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Kolaborasi</span>
        <h2 class="text-3xl md:text-4xl font-extrabold text-[#31110F] leading-tight mb-6">
          Dipercaya oleh dua institusi pemerintah.
        </h2>
        <p class="text-[#31110F]/80 text-base leading-relaxed">
          {!! $institusiSection->content !!}
        </p>
      </div>
      <div class="space-y-6">
        {{-- Card Institusi 1 --}}
        <div class="bg-[#F7F4EB] rounded-2xl p-6 observe-fade fade-up">
          <div class="flex items-center gap-4 mb-3">
            <div class="w-10 h-10 rounded-lg bg-[#0D1F08] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 class="text-lg font-bold text-[#31110F]">Pengadilan Agama</h3>
          </div>
          <p class="text-sm text-[#31110F]/70 pl-14 leading-relaxed">Menerbitkan putusan dan surat keterangan perceraian, mengelola kasus, dan verifikasi awal dokumen.</p>
        </div>
        {{-- Card Institusi 2 --}}
        <div class="bg-[#0D1F08] rounded-2xl p-6 text-[#F7F4EB] observe-fade fade-up fade-up-delay-1">
          <div class="flex items-center gap-4 mb-3">
            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3 class="text-lg font-bold">Disdukcapil</h3>
          </div>
          <p class="text-sm text-[#FFF0C4]/80 pl-14 leading-relaxed">Melakukan validasi data kependudukan, pembaruan data di sistem PIK, dan penerbitan dokumen resmi.</p>
        </div>
      </div>
    </div>
  </div>
</section>
@endif

@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var els = document.querySelectorAll('.observe-fade.fade-up');
    if (!els.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -40px 0px'
    });

    els.forEach(function (el) {
      observer.observe(el);
    });
  });
</script>
@endpush
