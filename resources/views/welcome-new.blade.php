@extends('layouts.public')

@section('title', $sSeo->title ?: 'SiPadu - Sistem Pembaruan Dokumen Pasca Perceraian')

@push('styles')
<style>
  /* ── Animations ───────────────────────────────────── */
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

  /* ── Stat number ──────────────────────────────────── */
  .stat-item {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 0.5rem 0;
    overflow: hidden;
  }
  .stat-inner {
    width: 100%;
  }
  .stat-number {
    font-size: clamp(1.25rem, 4vw, 4rem);
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -0.03em;
    display: block;
    word-break: break-word;
    overflow-wrap: anywhere;
  }
  .stat-label {
    font-size: clamp(0.6rem, 1.8vw, 0.95rem);
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.6);
    margin-top: 0.25rem;
    font-weight: 400;
    display: block;
    overflow-wrap: anywhere;
    word-break: break-all;
  }

  /* ── Scroll-triggered fade ────────────────────────── */
  .observe-fade {
    will-change: opacity, transform;
  }
</style>
@endpush

@section('content')

{{-- SEO Meta Description --}}
<meta name="description" content="{{ $sSeo->content ?: 'Sistem terintegrasi pembaruan dokumen kependudukan pasca perceraian.' }}">

{{-- ===================================================================
    SECTION 1 — HERO (two columns: text left, collage right)
    =================================================================== --}}
<section class="h-[70vh] flex items-center relative overflow-hidden" style="background: url('{{ asset('assets-hero.jpg') }}') center/cover no-repeat; position: relative;">
    {{-- Dark green gradient overlay --}}
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(13,31,8,.45),rgba(13,31,8,.65));"></div>
    <div class="w-[80%] mx-auto relative z-10">

      <div class="max-w-2xl">
        <h1 class="text-3xl sm:text-4xl md:text-[2.75rem] lg:text-5xl leading-[1.15] font-medium tracking-tight text-white mb-4">
          {{ $sHero->title ?: 'Menjamin Kepastian Hukum, Dokumen Menjadi Kunci' }}
        </h1>

        <p class="text-base md:text-lg text-cream/70 mb-8 leading-relaxed">
          {{ $sHero->subtitle ?: 'Sistem terintegrasi pembaruan dokumen kependudukan pasca perceraian antara Pengadilan Agama dan Dinas Kependudukan.' }}
        </p>

        <div class="flex flex-wrap gap-3">
          <a href="{{ $sHero->cta_url ?: route('public.submit.create') }}"
             class="px-6 py-3 bg-white text-brand font-medium text-sm rounded-full shadow-sm hover:opacity-90 transition inline-flex items-center gap-2">
            <i class="fas fa-file-alt"></i> {{ $sHero->cta_label ?: 'Mulai Pengajuan' }}
          </a>
          <a href="{{ $sHero->secondary_cta_url ?: route('services') }}"
             class="px-6 py-3 bg-white/10 backdrop-blur-sm text-white border border-white/25 font-medium text-sm rounded-full hover:bg-white/20 transition inline-flex items-center gap-2">
            Layanan <i class="fas fa-arrow-right text-xs"></i>
          </a>
        </div>
      </div>

    </div>
</section>


{{-- ===================================================================
    SECTION 2 — TRUST LOGOS STRIP
    =================================================================== --}}
<section class="border-y border-brown/10 bg-cream py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4 opacity-50">
      <span class="text-sm font-semibold text-brown/50">Kemenag RI</span>
      <span class="text-sm font-semibold text-brown/50">Kemendagri</span>
      <span class="text-sm font-semibold text-brown/50">PA</span>
      <span class="text-sm font-semibold text-brown/50">Disdukcapil</span>
      <span class="text-sm font-semibold text-brown/50">Mahkamah Agung</span>
    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 3 — SPLIT: Image left, Timeline right (institution & ecosystem)
    =================================================================== --}}
<section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-[#F7F4EB]">
  <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 md:gap-16 items-start">

    {{-- Left: Large portrait image --}}
    <div class="observe-fade fade-up">
      <div class="rounded-2xl overflow-hidden bg-[#D5CEA3] relative" style="aspect-ratio:4/5;">
        <div class="absolute inset-0 flex items-center justify-center">
          <svg class="w-24 h-24 text-[#D5CEA3]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-4.5z"/>
          </svg>
        </div>
        <img src="{{ asset('assets/asset disdukcapil.jpeg') }}"
             alt="Kolaborasi Dua Institusi"
             class="w-full h-full object-cover absolute inset-0">
      </div>
    </div>

    {{-- Right: Timeline / Steps --}}
    <div class="observe-fade fade-up" style="transition-delay:0.1s;">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-[#8E8E93]/70 mb-4">Dua Institusi, Satu Ekosistem</p>
      <h2 class="text-2xl md:text-3xl font-medium text-[#31110F] leading-tight mb-10">Bagaimana SiPadu menghubungkan kami</h2>

      <div class="relative">
        {{-- Vertical line --}}
        <div class="absolute left-0 top-0 bottom-0 w-px bg-[#8E8E93]/15"></div>

        {{-- Step 1: Pengadilan Agama Painan --}}
        <div class="group relative pl-6 pb-10">
          <div class="absolute left-[9px] top-0.5 w-2.5 h-2.5 rounded-full bg-[#31110F]/25 group-hover:bg-[#31110F]/50 transition-colors"></div>
          <span class="text-[11px] font-semibold uppercase tracking-widest text-[#8E8E93]/80 mb-1 block">Tentang Kami</span>
          <h3 class="text-lg font-semibold text-[#31110F] leading-tight mb-2">Pengadilan Agama Painan</h3>
          <p class="text-sm text-[#31110F]/65 leading-relaxed">
            Berdiri sejak 1957, lembaga peradilan yang memberikan kepastian hukum dalam perkara perceraian. Gedung baru dibangun 2006–2007 dan beroperasi sejak 2007, berlokasi di Jalan Dr. Moh. Hatta, Painan.
          </p>
        </div>

        <hr class="border-[#8E8E93]/10 mx-6">

        {{-- Step 2: Dinas Kependudukan --}}
        <div class="group relative pl-6 pb-10">
          <div class="absolute left-[9px] top-0.5 w-2.5 h-2.5 rounded-full bg-[#31110F]/25 group-hover:bg-[#31110F]/50 transition-colors"></div>
          <span class="text-[11px] font-semibold uppercase tracking-widest text-[#8E8E93]/80 mb-1 block">Tentang Kami</span>
          <h3 class="text-lg font-semibold text-[#31110F] leading-tight mb-2">Dinas Kependudukan dan Pencatatan Sipil</h3>
          <p class="text-sm text-[#31110F]/65 leading-relaxed">
            Menyediakan layanan administrasi kependudukan untuk seluruh kabupaten — menerbitkan KK, KTP-el, Akta, dan berbagai dokumen kependudukan melalui sistem digital modern.
          </p>
        </div>

        <hr class="border-[#8E8E93]/10 mx-6">

        {{-- Step 3: SiPadu --}}
        <div class="group relative pl-6 pb-10">
          <div class="absolute left-[9px] top-0.5 w-2.5 h-2.5 rounded-full bg-[#31110F]/25 group-hover:bg-[#31110F]/50 transition-colors"></div>
          <span class="text-[11px] font-semibold uppercase tracking-widest text-[#8E8E93]/80 mb-1 block">Kerja Sama</span>
          <h3 class="text-lg font-semibold text-[#31110F] leading-tight mb-2">SiPadu — Satu Sistem, Tanpa Batas</h3>
          <p class="text-sm text-[#31110F]/65 leading-relaxed">
            Menghapus proses manual transmisi putusan perceraian. Sinkronisasi data otomatis dan real-time — tanpa bolak-balik ke kantor.
          </p>
        </div>

        <hr class="border-[#8E8E93]/10 mx-6">

        {{-- Step 4: Call to Action --}}
        <div class="group relative pl-6 pb-4">
          <div class="absolute left-[9px] top-0.5 w-2.5 h-2.5 rounded-full bg-[#31110F]/25 group-hover:bg-[#31110F]/50 transition-colors"></div>
          <span class="text-[11px] font-semibold uppercase tracking-widest text-[#8E8E93]/80 mb-1 block">Mulai Sekarang</span>
          <h3 class="text-lg font-semibold text-[#31110F] leading-tight mb-2">Buat Pengajuan Pertama Anda</h3>
          <p class="text-sm text-[#31110F]/65 leading-relaxed">
            Gunakan platform SiPadu untuk memproses pembaruan dokumen kependudukan pasca perceraian — terintegrasi, transparan, dan dapat dilacak.
          </p>
        </div>
      </div>

      {{-- CTA Button --}}
      <div class="mt-8 ml-6">
        <a href="{{ route('public.submit.create') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-[#31110F] hover:text-[#31110F]/70 transition-colors border-b border-[#31110F]/20 hover:border-[#31110F]/50 pb-0.5">
          Mulai Pengajuan <i class="fas fa-arrow-right text-xs ml-1"></i>
        </a>
      </div>
    </div>

  </div>
</section>


{{-- ===================================================================
    SECTION 4 — PROCESS TIMELINE (dark section)
    =================================================================== --}}
<section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-brand text-cream relative overflow-hidden">
  <div class="absolute top-0 right-0 w-96 h-96 bg-green-sm/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
  <div class="max-w-6xl mx-auto relative z-10">
    <div class="text-center mb-16">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-cream/40 mb-4">{{ $sProses->title ?: 'Proses' }}</p>
      <h2 class="text-2xl md:text-4xl font-medium">
        {{ $sProses->subtitle ?: 'Bagaimana cara kerjanya?' }}
      </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

      {{-- Step 1 --}}
      <div class="text-center observe-fade fade-up">
        <div class="w-16 h-16 rounded-full bg-cream/10 flex items-center justify-center mx-auto mb-5 border border-cream/15">
          <svg class="w-7 h-7 text-cream/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-sm font-semibold uppercase tracking-wider mb-2">1. Ajukan</h3>
        <p class="text-sm text-cream/50 leading-relaxed">
          Isi formulir online dan unggah dokumen putusan perceraian serta data pendukung.
        </p>
      </div>

      {{-- Step 2 --}}
      <div class="text-center observe-fade fade-up fade-up-delay-1">
        <div class="w-16 h-16 rounded-full bg-cream/10 flex items-center justify-center mx-auto mb-5 border border-cream/15">
          <svg class="w-7 h-7 text-cream/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <h3 class="text-sm font-semibold uppercase tracking-wider mb-2">2. Verifikasi</h3>
        <p class="text-sm text-cream/50 leading-relaxed">
          Petugas PA Painan memverifikasi kelengkapan dokumen putusan perceraian.
        </p>
      </div>

      {{-- Step 3 --}}
      <div class="text-center observe-fade fade-up fade-up-delay-2">
        <div class="w-16 h-16 rounded-full bg-cream/10 flex items-center justify-center mx-auto mb-5 border border-cream/15">
          <svg class="w-7 h-7 text-cream/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 6l3 3m0 0l-3 3m3-3H9"/></svg>
        </div>
        <h3 class="text-sm font-semibold uppercase tracking-wider mb-2">3. Sinkronisasi</h3>
        <p class="text-sm text-cream/50 leading-relaxed">
          Data diverifikasi lalu disinkronkan ke Disdukcapil Pessel untuk pembaruan dokumen.
        </p>
      </div>

      {{-- Step 4 --}}
      <div class="text-center observe-fade fade-up fade-up-delay-3">
        <div class="w-16 h-16 rounded-full bg-cream/10 flex items-center justify-center mx-auto mb-5 border border-cream/15">
          <svg class="w-7 h-7 text-cream/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-sm font-semibold uppercase tracking-wider mb-2">4. Selesai</h3>
        <p class="text-sm text-cream/50 leading-relaxed">
          Dokumen diperbarui. Notifikasi dikirim melalui WhatsApp dan dapat diunduh.
        </p>
      </div>

    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 5 — LAYANAN SUMMARY CARDS (interactive accordion)
    =================================================================== --}}
@php
  $categories = [
    [
      'key'       => 'pembaruan_dokumen',
      'title'     => 'Pembaruan Dokumen Kependudukan',
      'icon'      => 'fas fa-file-alt',
      'summary'   => 'Perbarui status perkawinan pada KTP-el, Kartu Keluarga, dan dokumen kependudukan lainnya setelah perceraian.',
      'open_icon' => 'fas fa-chevron-up',
      'close_icon'=> 'fas fa-chevron-down',
      'options'   => [
        [ 'title' => 'Pencatatan Perceraian',         'desc' => 'Pencatatan resmi atas putusan perceraian yang ditetapkan Pengadilan Agama Painan.' ],
        [ 'title' => 'Penerbitan Akta Perceraian',    'desc' => 'Akta perceraian dari PA Painan disalurkan digital ke Disdukcapil Pessel untuk pemutakhiran data.' ],
        [ 'title' => 'Pengurusan KK Baru',            'desc' => 'Pembuatan Kartu Keluarga baru sesuai kondisi status perkawinan pasca perceraian.' ],
        [ 'title' => 'Perbarui KTP-el',               'desc' => 'Status perkawinan pada KTP-el diperbarui menjadi "Cerai" tanpa perlu datang ke kantor.' ],
        [ 'title' => 'Ikhtisar Putusan',              'desc' => 'Ringkasan putusan perceraian sebagai dasar hukum perubahan dokumen kependudukan.' ],
      ],
    ],
    [
      'key'       => 'pencatatan_data',
      'title'     => 'Pencatatan & Verifikasi Data',
      'icon'      => 'fas fa-user-check',
      'summary'   => 'Verifikasi berlapis oleh PA Painan dan Disdukcapil Pessel untuk memastikan data kependudukan akurat dan sah.',
      'open_icon' => 'fas fa-chevron-up',
      'close_icon'=> 'fas fa-chevron-down',
      'options'   => [
        [ 'title' => 'Verifikasi Dokumen PA',         'desc' => 'Petugas PA Painan memverifikasi kelengkapan putusan dan surat keterangan perceraian.' ],
        [ 'title' => 'Sinkronisasi Data',             'desc' => 'Data yang sudah diverifikasi PA langsung tersinkronisasi ke database Disdukcapil Pessel.' ],
        [ 'title' => 'Validasi Kependudukan',         'desc' => 'Disdukcapil memvalidasi identitas warga (NIK, nama, alamat) sesuai data resmi.' ],
        [ 'title' => 'Verifikasi Berkas Fisik',       'desc' => 'Berkas fisik dapat diverifikasi ulang jika diperlukan untuk keperluan hukum.' ],
      ],
    ],
    [
      'key'       => 'layanan_lain',
      'title'     => 'Layanan Pendukung Lainnya',
      'icon'      => 'fas fa-concierge-bell',
      'summary'   => 'Berbagai layanan tambahan untuk mendukung kemudahan akses dokumen kependudukan di Kabupaten Pesisir Selatan.',
      'open_icon' => 'fas fa-chevron-up',
      'close_icon'=> 'fas fa-chevron-down',
      'options'   => [
        [ 'title' => 'Pelacakan Online',              'desc' => 'Lacak posisi pengajuan dari mana pun dengan token yang dikirim via WhatsApp.' ],
        [ 'title' => 'Konsultasi Prosedur',           'desc' => 'Tanya jawab langsung tentang dokumen dan prosedur yang dibutuhkan melalui CS.' ],
        [ 'title' => 'Pengaduan Masyarakat',          'desc' => 'Sampaikan keluhan atau masukan atas pelayanan PA Painan dan Disdukcapil Pessel.' ],
        [ 'title' => 'Unduh Dokumen',                 'desc' => 'Download surat keterangan, ikhtisar, dan dokumen lainnya secara langsung.' ],
      ],
    ],
  ];
@endphp
<section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-cream">
  <div class="max-w-6xl mx-auto">
    <div class="text-center mb-14">
      <h2 class="text-2xl md:text-4xl font-medium mb-4">
        {{ $sFitur->title ?: 'Layanan Kami' }}
      </h2>
      <p class="text-brown/50 text-sm md:text-base max-w-xl mx-auto">
        {{ $sFitur->subtitle ?: 'Lihat detail layanan yang tersedia melalui kerja sama Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan.' }}
      </p>
    </div>

    <div class="space-y-4">
      @foreach($categories as $catIndex => $cat)
      <div class="bg-white rounded-2xl border border-brown/10 shadow-sm overflow-hidden observe-fade fade-up {{ $catIndex > 0 ? 'fade-up-delay-' . min($catIndex, 3) : '' }}"
           data-category="{{ $cat['key'] }}">
        <button type="button"
                class="category-toggle w-full flex items-start gap-4 p-6 md:p-8 text-left hover:bg-brown/5 transition-colors"
                aria-expanded="false">
          <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center shrink-0 mt-0.5">
            <i class="{{ $cat['icon'] }} text-brand text-lg"></i>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-base md:text-lg font-semibold text-brown mb-1">{{ $cat['title'] }}</h3>
            <p class="text-sm text-brown/50 leading-relaxed">{{ $cat['summary'] }}</p>
          </div>
          <i class="fas fa-chevron-down text-brown/30 text-sm category-icon"></i>
        </button>
        <div class="category-content hidden border-t border-brown/10">
          <div class="p-6 md:p-8">
            <div class="grid md:grid-cols-2 gap-4">
              @foreach($cat['options'] as $optIndex => $opt)
              <div class="option-item rounded-xl bg-[#F7F4EB] p-5">
                <div class="flex items-start gap-3">
                  <div class="w-7 h-7 bg-brand/10 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fas fa-file-alt text-brand text-xs"></i>
                  </div>
                  <div>
                    <h4 class="text-sm font-semibold text-brown mb-1">{{ $opt['title'] }}</h4>
                    <p class="text-xs text-brown/60 leading-relaxed">{{ $opt['desc'] }}</p>
                  </div>
                </div>
              </div>
              @endforeach
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
              <a href="{{ route('public.submit.create') }}"
                 class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand text-white text-sm font-medium rounded-full hover:opacity-90 transition">
                <i class="fas fa-plus-circle"></i> Ajukan Sekarang
              </a>
              <a href="{{ route('tracking.public') }}"
                 class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-brand border border-brown/20 text-sm font-medium rounded-full hover:bg-brown/5 transition">
                <i class="fas fa-search"></i> Lacak Pengajuan
              </a>
              <a href="{{ route('tentang') }}"
                 class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-brown/70 border border-brown/10 text-sm font-medium rounded-full hover:bg-brown/5 transition">
                Tentang SiPadu
              </a>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 6 — STATS BANNER
    =================================================================== --}}
@php
  // Statistik diparse dari CMS `statistik` section.
  // Format di field `subtitle`: tiap baris "value|label" (pisahan "|").
  // Jika kosong, gunakan default fallback.
  $rawStats = trim((string) ($sStatistik->subtitle ?? ''));
  $statItems = [];
  if ($rawStats !== '') {
      foreach (preg_split('/\r\n|\r|\n/', $rawStats) as $line) {
          $line = trim($line);
          if ($line === '' || !str_contains($line, '|')) continue;
          [$val, $lbl] = array_map('trim', explode('|', $line, 2));
          if ($val !== '' && $lbl !== '') {
              $statItems[] = ['value' => $val, 'label' => $lbl];
          }
      }
  }
  if (empty($statItems)) {
      $statItems = [
          ['value' => '200+', 'label' => 'Kasus Diproses'],
          ['value' => '98%',  'label' => 'Tingkat Akurasi'],
          ['value' => '24/7', 'label' => 'Layanan Aktif'],
          ['value' => '2.5d', 'label' => 'Waktu Pemrosesan Rata-rata'],
      ];
  }
@endphp
<section class="relative py-12 sm:py-16 md:py-20 lg:py-24 px-4 sm:px-6 lg:px-8 overflow-hidden">
  <div class="absolute inset-0">
    <img src="{{ asset('assets-hero.jpg') }}"
         alt="Background"
         class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-brand/80 mix-blend-multiply"></div>
    <div class="absolute inset-0 bg-black/10"></div>
  </div>
  <div class="max-w-6xl mx-auto relative z-10">
    <div class="grid grid-cols-2 lg:grid-cols-{{ count($statItems) >= 4 ? '4' : (count($statItems) ?: 4) }} gap-x-6 gap-y-6 lg:gap-y-0">
      @foreach($statItems as $idx => $stat)
      <div class="stat-item observe-fade fade-up {{ $idx > 0 ? 'fade-up-delay-' . min($idx, 3) : '' }}">
        <div class="stat-inner"><div class="stat-number text-cream">{{ $stat['value'] }}</div><p class="stat-label">{{ $stat['label'] }}</p></div>
      </div>
      @endforeach
    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 7 — TESTIMONIALS (dark)
    =================================================================== --}}
<section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-brand-dk text-cream">
  <div class="max-w-6xl mx-auto">
    <h2 class="text-2xl md:text-4xl font-medium mb-12">
      Apa kata mereka
    </h2>

    <div class="grid md:grid-cols-2 gap-6">

      {{-- Testimonial 1 --}}
      <div class="bg-white/5 border border-cream/10 rounded-2xl p-7 observe-fade fade-up">
        <blockquote class="text-sm text-cream/70 leading-relaxed mb-5">
          "Kerja sama SiPadu antara Pengadilan Agama Painan dan Disdukcapil Pessel sangat membantu kami. Sebelumnya proses transmisi putusan pengadilan ke Disdukcapil memakan waktu berminggu-minggu, sekarang hanya dalam hitungan hari."
        </blockquote>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-green-sm/30 flex items-center justify-center text-sm font-semibold text-cream">HM</div>
          <div>
            <p class="text-sm font-medium">H. Mukhtar, S.Ag., M.Ag.</p>
            <p class="text-xs text-cream/40">Ketua Pengadilan Agama Painan</p>
          </div>
        </div>
      </div>

      {{-- Testimonial 2 --}}
      <div class="bg-white/5 border border-cream/10 rounded-2xl p-7 observe-fade fade-up fade-up-delay-1">
        <blockquote class="text-sm text-cream/70 leading-relaxed mb-5">
          "Sebagai warga Pessel yang baru bercerai, saya bisa mengurus pembaruan KTP dan KK tanpa harus bolak-balik ke Painan atau Punnajawa. Sangat memudahkan!"
        </blockquote>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-coral/30 flex items-center justify-center text-sm font-semibold text-cream">SR</div>
          <div>
            <p class="text-sm font-medium">Siti Rahmawati</p>
            <p class="text-xs text-cream/40">Warga, Kabupaten Pesisir Selatan</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 8 — BLOG / BERITA
    =================================================================== --}}
@php $latestPosts = $blogPosts->take(3); @endphp
@if($latestPosts->isNotEmpty())
<section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-cream">
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-10">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25">{{ $sBlog->title ?: 'Berita' }}</p>
      <a href="{{ route('berita') }}" class="text-xs text-brand font-semibold inline-flex items-center gap-1 hover:gap-2 transition-all">
        Lihat Semua <i class="fas fa-arrow-right text-[10px]"></i>
      </a>
    </div>

    <h2 class="text-2xl md:text-4xl font-medium mb-12">
      {{ $sBlog->subtitle ?: 'Berita & Pengumuman Terbaru' }}
    </h2>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($latestPosts as $post)
      <article class="bg-white rounded-2xl overflow-hidden card-lift group">
        @if($post->cover_image)
          <div class="h-44 overflow-hidden">
            <img src="{{ asset('storage/' . $post->cover_image) }}"
                 alt="{{ $post->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
          </div>
        @else
          <div class="h-44 bg-brand-dk/5 flex items-center justify-center">
            <svg class="w-10 h-10 text-brand/15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
        @endif
        <div class="p-6">
          <div class="text-[10px] font-semibold uppercase tracking-wider text-brown/25 mb-3">
            {{ $post->published_at ? $post->published_at->format('d M Y') : 'Draft' }}
          </div>
          <h3 class="text-sm font-semibold text-brown mb-2 group-hover:text-brand transition-colors leading-snug">
            <a href="{{ route('berita.detail', $post->slug) }}">{{ $post->title }}</a>
          </h3>
          @if($post->excerpt)
          <p class="text-xs text-brown/50 leading-relaxed mb-4">{{ Str::limit($post->excerpt, 120) }}</p>
          @endif
          <a href="{{ route('berita.detail', $post->slug) }}"
             class="text-xs font-semibold text-brand inline-flex items-center gap-1 group-hover:gap-2 transition-all">
            Baca Selengkapnya <i class="fas fa-arrow-right text-[10px]"></i>
          </a>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif


{{-- ===================================================================
    SECTION 9 — FINAL CTA
    =================================================================== --}}
<section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-brand relative overflow-hidden">
  <div class="absolute inset-0 opacity-20">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-green-sm/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-accent/15 rounded-full blur-3xl"></div>
  </div>

  <div class="max-w-6xl mx-auto relative z-10">
    <div class="text-center mb-6">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-white/20 mb-3">Mulai Sekarang</p>
      <h2 class="text-2xl md:text-4xl font-medium text-cream leading-tight">
        {{ $sCta->title ?: 'Siap untuk memulai?' }}
      </h2>
    </div>
    <p class="text-sm text-cream/40 leading-relaxed mb-10 max-w-2xl mx-auto text-center">
      {{ $sCta->subtitle ?: 'Gunakan platform SiPadu untuk memproses pembaruan dokumen kependudukan pasca perceraian — terintegrasi, transparan, dan dapat dilacak.' }}
    </p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="{{ $sCta->cta_url ?: route('public.submit.create') }}"
         class="px-8 py-3 bg-cream text-brand font-medium text-sm rounded-full hover:opacity-90 transition shadow-lg">
        {{ $sCta->cta_label ?: 'Buat Pengajuan Sekarang' }}
      </a>
      <a href="{{ route('auth.login') }}"
         class="px-8 py-3 border border-cream/25 text-cream font-medium text-sm rounded-full hover:bg-cream/5 transition">
        Masuk ke Akun
      </a>
    </div>
  </div>
</section>

@endsection

@push('scripts')
<script>
  // ── Intersection Observer: fade-up on scroll ──────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    const els = document.querySelectorAll('.observe-fade.fade-up');
    if (!els.length) return;

    const observer = new IntersectionObserver(function (entries) {
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

    /* ── Category accordion toggle ─────────────────────────────── */
    document.querySelectorAll('.category-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var content = this.nextElementSibling;
        var icon    = this.querySelector('.category-icon');
        var isOpen  = !content.classList.contains('hidden');

        // Close all other open categories
        document.querySelectorAll('.category-toggle[aria-expanded="true"]').forEach(function (otherBtn) {
          if (otherBtn !== btn) {
            var otherContent = otherBtn.nextElementSibling;
            var otherIcon    = otherBtn.querySelector('.category-icon');
            otherContent.classList.add('hidden');
            otherIcon.classList.remove('fa-chevron-up');
            otherIcon.classList.add('fa-chevron-down');
            otherBtn.setAttribute('aria-expanded', 'false');
          }
        });

        // Toggle current
        if (isOpen) {
          content.classList.add('hidden');
          icon.classList.remove('fa-chevron-up');
          icon.classList.add('fa-chevron-down');
          this.setAttribute('aria-expanded', 'false');
        } else {
          content.classList.remove('hidden');
          icon.classList.remove('fa-chevron-down');
          icon.classList.add('fa-chevron-up');
          this.setAttribute('aria-expanded', 'true');
        }
      });
    });
  });
</script>
@endpush
