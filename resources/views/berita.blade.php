@extends('layouts.public')

@section('title', 'Berita & Pengumuman - SiPadu')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════════
    HEADER SECTION
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="gradient-dark text-white py-16 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-5xl md:text-6xl font-bold">Blog & Insights</h1>
    <p class="text-gray-400 text-lg mt-2">Tips, trik, dan panduan terkini seputar pembaruan dokumen</p>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    FEATURED ARTICLE
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="px-4 sm:px-6 lg:px-8 py-12">
  <div class="max-w-7xl mx-auto">
    <article class="gradient-dark text-white rounded-2xl overflow-hidden shadow-2xl">
      <div class="grid lg:grid-cols-2 min-h-96">
        
        {{-- Featured Image --}}
        <div class="bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center p-8">
          <div class="space-y-4 text-center">
            <i class="fas fa-rocket text-6xl mb-4"></i>
            <div>
              <div class="text-xs font-semibold uppercase tracking-widest text-blue-200 mb-2">FEATURED</div>
              <p class="text-sm text-gray-200">Peluncuran Sistem SiPadu v1.0</p>
            </div>
          </div>
        </div>

        {{-- Featured Content --}}
        <div class="p-8 md:p-12 flex flex-col justify-between">
          <div>
            <div class="flex items-center gap-3 mb-4">
              <span class="inline-block px-3 py-1 bg-orange-500 text-white text-xs font-semibold rounded-full">Pengumuman</span>
              <span class="text-gray-400 text-sm">24 Februari 2026</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold mb-4 leading-tight">
              Peluncuran Sistem SiPadu Versi 1.0
            </h2>
            <p class="text-gray-300 leading-relaxed">
              Platform terintegrasi pembaruan dokumen kependudukan pasca perceraian telah resmi diluncurkan dengan fitur OCR otomatis, verifikasi berlapis, dan pelacakan real-time.
            </p>
          </div>
          <a href="#" class="inline-flex items-center gap-2 mt-8 text-white font-semibold hover:gap-3 transition-all">
            Read more <i class="fas fa-arrow-right"></i>
          </a>
        </div>

      </div>
    </article>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    RECENT ARTICLES
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="px-4 sm:px-6 lg:px-8 py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h2 class="text-4xl font-bold text-gray-900 mb-12">Recent articles</h2>

    {{-- Articles Grid --}}
    <div class="grid md:grid-cols-3 gap-8 mb-12">
      
      {{-- Article Card 1 --}}
      <article class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all group cursor-pointer">
        <div class="bg-gradient-to-br from-purple-400 to-pink-400 h-48 flex items-center justify-center relative overflow-hidden">
          <div class="text-center">
            <i class="fas fa-briefcase text-5xl text-white opacity-70"></i>
          </div>
          <div class="absolute top-4 left-4 flex items-center gap-1 text-xs font-semibold text-white bg-black/40 backdrop-blur-sm px-2 py-1 rounded">
            <i class="fas fa-circle text-purple-300"></i> <span>INSIGHTS</span>
          </div>
        </div>
        <div class="p-6">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            SEPTEMBER 24, 2025 · INSIGHTS
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-brand transition-colors">
            Bahasa Komunikasi dalam Perubahan Organisasi
          </h3>
          <p class="text-gray-600 text-sm leading-relaxed mb-4">
            Memahami cara yang jelas dan berkesan menjelaskan perubahan organisasi kepada semua stakeholder untuk hasil optimal.
          </p>
          <a href="#" class="text-brand font-semibold text-sm hover:gap-1 inline-flex items-center gap-0 transition-all">
            Read more <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </article>

      {{-- Article Card 2 --}}
      <article class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all group cursor-pointer">
        <div class="bg-gradient-to-br from-orange-400 to-red-400 h-48 flex items-center justify-center relative overflow-hidden">
          <div class="text-center">
            <i class="fas fa-handshake text-5xl text-white opacity-70"></i>
          </div>
          <div class="absolute top-4 left-4 flex items-center gap-1 text-xs font-semibold text-white bg-black/40 backdrop-blur-sm px-2 py-1 rounded">
            <i class="fas fa-circle text-red-300"></i> <span>INSIGHTS</span>
          </div>
        </div>
        <div class="p-6">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            SEPTEMBER 24, 2025 · INSIGHTS
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-brand transition-colors">
            Struktur Sebelum Gaya: Perspektif Konsultan
          </h3>
          <p class="text-gray-600 text-sm leading-relaxed mb-4">
            Mengapa struktur organisasi yang baik harus menjadi prioritas sebelum mengembangkan gaya komunikasi perusahaan.
          </p>
          <a href="#" class="text-brand font-semibold text-sm hover:gap-1 inline-flex items-center gap-0 transition-all">
            Read more <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </article>

      {{-- Article Card 3 --}}
      <article class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all group cursor-pointer">
        <div class="bg-gradient-to-br from-yellow-400 to-amber-400 h-48 flex items-center justify-center relative overflow-hidden">
          <div class="text-center">
            <i class="fas fa-chart-line text-5xl text-white opacity-70"></i>
          </div>
          <div class="absolute top-4 left-4 flex items-center gap-1 text-xs font-semibold text-white bg-black/40 backdrop-blur-sm px-2 py-1 rounded">
            <i class="fas fa-circle text-yellow-300"></i> <span>INSIGHTS</span>
          </div>
        </div>
        <div class="p-6">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            SEPTEMBER 24, 2025 · INSIGHTS
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-brand transition-colors">
            Restrukturisasi Tanpa Kehilangan Kepercayaan
          </h3>
          <p class="text-gray-600 text-sm leading-relaxed mb-4">
            Strategi untuk melakukan restrukturisasi organisasi sambil mempertahankan kepercayaan karyawan dan stakeholder.
          </p>
          <a href="#" class="text-brand font-semibold text-sm hover:gap-1 inline-flex items-center gap-0 transition-all">
            Read more <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </article>

    </div>

    {{-- Second Row --}}
    <div class="grid md:grid-cols-2 gap-8">
      
      {{-- Article Card 4 --}}
      <article class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all group cursor-pointer">
        <div class="bg-gradient-to-br from-green-400 to-emerald-400 h-40 flex items-center justify-center relative overflow-hidden">
          <div class="text-center">
            <i class="fas fa-book text-5xl text-white opacity-70"></i>
          </div>
          <div class="absolute top-4 left-4 flex items-center gap-1 text-xs font-semibold text-white bg-black/40 backdrop-blur-sm px-2 py-1 rounded">
            <i class="fas fa-circle text-green-300"></i> <span>INSIGHTS</span>
          </div>
        </div>
        <div class="p-6">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            SEPTEMBER 24, 2025 · INSIGHTS
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-brand transition-colors">
            Dari Krisis hingga Kredibilitas: Mendukung Perusahaan Tech
          </h3>
          <p class="text-gray-600 text-sm leading-relaxed mb-4">
            Bagaimana komunikasi strategis dapat membantu perusahaan teknologi untuk bangkit dari krisis dan membangun kembali kredibilitas.
          </p>
          <a href="#" class="text-brand font-semibold text-sm hover:gap-1 inline-flex items-center gap-0 transition-all">
            Read more <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </article>

      {{-- Article Card 5 --}}
      <article class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all group cursor-pointer">
        <div class="bg-gradient-to-br from-indigo-400 to-blue-400 h-40 flex items-center justify-center relative overflow-hidden">
          <div class="text-center">
            <i class="fas fa-star text-5xl text-white opacity-70"></i>
          </div>
          <div class="absolute top-4 left-4 flex items-center gap-1 text-xs font-semibold text-white bg-black/40 backdrop-blur-sm px-2 py-1 rounded">
            <i class="fas fa-circle text-blue-300"></i> <span>INSIGHTS</span>
          </div>
        </div>
        <div class="p-6">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            SEPTEMBER 24, 2025 · INSIGHTS
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-brand transition-colors">
            Reputasi Dibangun dalam Hal-hal Kecil
          </h3>
          <p class="text-gray-600 text-sm leading-relaxed mb-4">
            Detail kecil dalam komunikasi dan tindakan yang konsisten adalah kunci untuk membangun reputasi perusahaan yang kuat dan terpercaya.
          </p>
          <a href="#" class="text-brand font-semibold text-sm hover:gap-1 inline-flex items-center gap-0 transition-all">
            Read more <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </article>

    </div>

    {{-- Load More Button --}}
    <div class="mt-12 text-center">
      <button class="px-8 py-3 border-2 border-gray-300 text-gray-900 font-semibold rounded-lg hover:border-brand hover:text-brand transition-colors inline-flex items-center gap-2">
        <i class="fas fa-chevron-right"></i> Lihat Lebih Banyak
      </button>
    </div>

  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    NEWSLETTER SUBSCRIPTION
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="gradient-dark text-white py-16 px-4 sm:px-6 lg:px-8">
  <div class="max-w-2xl mx-auto text-center">
    <h2 class="text-3xl md:text-4xl font-bold mb-4">Tetap Update dengan Insight Terbaru</h2>
    <p class="text-gray-400 mb-8 text-lg">
      Dapatkan tips, panduan, dan berita terbaru langsung di email Anda. Jangan lewatkan update penting lainnya.
    </p>
    <form class="flex flex-col sm:flex-row gap-3">
      <input type="email" placeholder="Email Anda" class="flex-1 px-4 py-3 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand">
      <button type="submit" class="px-6 py-3 bg-brand text-white font-semibold rounded-lg hover:bg-brand-dark transition-colors">
        Subscribe
      </button>
    </form>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    CTA SECTIONS
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="px-4 sm:px-6 lg:px-8 py-20">
  <div class="max-w-7xl mx-auto">
    <h2 class="text-4xl font-bold text-gray-900 mb-12">Bagaimana Kami Memulai</h2>

    <div class="grid lg:grid-cols-2 gap-8">
      
      {{-- CTA 1: Konsultasi --}}
      <div class="gradient-dark text-white p-12 rounded-xl flex flex-col justify-between min-h-80">
        <div>
          <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center mb-6">
            <i class="fas fa-message text-xl"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4">Konsultasi Gratis</h3>
          <p class="text-gray-300 leading-relaxed">
            Belum puas dengan solusi yang ada? Mari kita diskusikan kebutuhan spesifik Anda dan temukan cara terbaik untuk memproses dokumen Anda.
          </p>
        </div>
        <a href="#" class="mt-8 inline-flex items-center gap-2 text-white font-semibold hover:gap-3 transition-all">
          Booking a consultation <i class="fas fa-arrow-right"></i>
        </a>
      </div>

      {{-- CTA 2: Layanan --}}
      <div class="bg-gradient-to-br from-rose-400 to-orange-400 text-white p-12 rounded-xl flex flex-col justify-between min-h-80">
        <div>
          <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center mb-6">
            <i class="fas fa-cog text-xl"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4">Jelajahi Layanan Kami</h3>
          <p class="text-white/90 leading-relaxed">
            Temukan berbagai layanan yang kami sediakan untuk memenuhi kebutuhan pembaruan dokumen kependudukan Anda dengan mudah dan cepat.
          </p>
        </div>
        <a href="#" class="mt-8 inline-flex items-center gap-2 text-white font-semibold hover:gap-3 transition-all">
          Lihat layanan <i class="fas fa-arrow-right"></i>
        </a>
      </div>

    </div>
  </div>
</section>

@endsection