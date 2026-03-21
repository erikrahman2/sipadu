@extends('layouts.public')

@section('title', 'Beranda - SiPadu')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════════
    HERO SECTION
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="gradient-hero text-white py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <div class="max-w-3xl">
      <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
        Ketika Kepastian Hukum,
        <span class="text-cyan-300">Dokumen Menjadi Kunci</span>
      </h1>
      <p class="text-blue-100 text-lg md:text-xl leading-relaxed mb-8 max-w-2xl">
        Sistem terintegrasi pembaruan dokumen kependudukan pasca perceraian antara Pengadilan Agama dan Dinas Kependudukan. Proses digital, transparan, dan terpercaya.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="{{ route('public.submit.create') }}"
          class="px-8 py-4 bg-white text-brand font-bold rounded-lg hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
          <i class="fas fa-file-alt"></i> Mulai Pengajuan
        </a>
        <a href="{{ route('tracking.public') }}"
          class="px-8 py-4 border-2 border-white text-white font-bold rounded-lg hover:bg-white/10 transition-colors inline-flex items-center gap-2">
          <i class="fas fa-search"></i> Lacak Pengajuan
        </a>
      </div>
    </div>
    
    {{-- Hero Image Placeholder --}}
    <div class="mt-16 grid md:grid-cols-2 gap-8">
      <div class="bg-white/10 rounded-lg h-64 md:h-96 flex items-center justify-center backdrop-blur-sm">
        <div class="text-center">
          <i class="fas fa-file-check text-6xl text-cyan-300 mb-4"></i>
          <p class="text-blue-100">Dokumen Digital Terintegrasi</p>
        </div>
      </div>
      <div class="bg-white/10 rounded-lg h-64 md:h-96 flex items-center justify-center backdrop-blur-sm">
        <div class="text-center">
          <i class="fas fa-chart-line text-6xl text-cyan-300 mb-4"></i>
          <p class="text-blue-100">Proses Transparan & Real-Time</p>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    PROSES & METODOLOGI
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <div class="grid lg:grid-cols-2 gap-16 items-center">
      
      {{-- Left Content --}}
      <div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8 leading-tight">
          Kami Membantu Mempercepat Pembaruan Dokumen Anda dengan Sistem yang Terstruktur, Jelas, dan Terpercaya
        </h2>
        
        <div class="space-y-8">
          {{-- Process Step 1 --}}
          <div class="flex gap-6">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-brand text-white font-bold">
                1
              </div>
              <div class="h-16 w-1 bg-gray-200 mx-auto mt-2"></div>
            </div>
            <div class="flex-1 pt-1">
              <h4 class="text-sm font-semibold text-brand uppercase tracking-wider">MINGGU 1</h4>
              <h3 class="text-lg font-bold text-gray-900 mb-2">Pengajuan & Validasi</h3>
              <p class="text-gray-600">Anda dapat langsung mengajukan dokumen melalui platform tanpa perlu datang ke kantor. Sistem akan melakukan validasi awal NIK dan data pribadi Anda.</p>
            </div>
          </div>

          {{-- Process Step 2 --}}
          <div class="flex gap-6">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-brand text-white font-bold">
                2
              </div>
              <div class="h-16 w-1 bg-gray-200 mx-auto mt-2"></div>
            </div>
            <div class="flex-1 pt-1">
              <h4 class="text-sm font-semibold text-brand uppercase tracking-wider">MINGGU 1-2</h4>
              <h3 class="text-lg font-bold text-gray-900 mb-2">Ekstraksi & OCR</h3>
              <p class="text-gray-600">Sistem otomatis mengekstraksi data dari dokumen Anda menggunakan teknologi OCR terkini. Hasil ekstraksi akan diverifikasi untuk akurasi maksimal.</p>
            </div>
          </div>

          {{-- Process Step 3 --}}
          <div class="flex gap-6">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-brand text-white font-bold">
                3
              </div>
              <div class="h-16 w-1 bg-gray-200 mx-auto mt-2"></div>
            </div>
            <div class="flex-1 pt-1">
              <h4 class="text-sm font-semibold text-brand uppercase tracking-wider">MINGGU 2-3</h4>
              <h3 class="text-lg font-bold text-gray-900 mb-2">Verifikasi PA & Disdukcapil</h3>
              <p class="text-gray-600">Dokumen Anda diverifikasi oleh Pengadilan Agama dan Disdukcapil secara berlapis untuk memastikan keabsahan dan kelengkapan data.</p>
            </div>
          </div>

          {{-- Process Step 4 --}}
          <div class="flex gap-6">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-green-600 text-white font-bold">
                ✓
              </div>
            </div>
            <div class="flex-1 pt-1">
              <h4 class="text-sm font-semibold text-green-600 uppercase tracking-wider">MINGGU 3+</h4>
              <h3 class="text-lg font-bold text-gray-900 mb-2">Penerbitan & Update</h3>
              <p class="text-gray-600">Dokumen Anda berhasil diperbarui di sistem kependudukan. Anda akan menerima notifikasi lengkap via WhatsApp dan dapat mengunduh dokumen digital.</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Right Image/Illustration --}}
      <div class="relative">
        <div class="bg-gradient-to-br from-blue-100 to-cyan-100 rounded-2xl p-12 flex items-center justify-center h-96 shadow-lg">
          <div class="text-center">
            <i class="fas fa-people-arrows text-6xl text-brand mb-6"></i>
            <div class="space-y-2">
              <p class="text-lg font-semibold text-gray-900">Kolaborasi Antara</p>
              <p class="text-gray-600">Pengadilan Agama & Disdukcapil</p>
            </div>
            <div class="mt-8 space-y-2">
              <div class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg shadow">
                <i class="fas fa-balance-scale text-brand"></i>
                <span class="text-sm font-medium text-gray-700">Pengadilan Agama</span>
              </div>
              <div class="block">↓</div>
              <div class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg shadow">
                <i class="fas fa-id-card text-brand"></i>
                <span class="text-sm font-medium text-gray-700">Disdukcapil</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    FITUR UNGGULAN
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="section-title">Fitur Unggulan</h2>
      <p class="section-subtitle mx-auto">
        Kami membantu mempercepat proses pembaruan dokumen dengan teknologi terkini dan sistem yang mudah digunakan.
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
      {{-- Feature 1 --}}
      <div class="card-hover bg-white p-8 rounded-lg shadow-sm hover:shadow-xl transition-all">
        <div class="w-14 h-14 rounded-lg bg-brand/10 flex items-center justify-center mb-4">
          <i class="fas fa-camera text-2xl text-brand"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">OCR Otomatis</h3>
        <p class="text-gray-600 leading-relaxed">
          Sistem ekstraksi teks otomatis dari dokumen menggunakan teknologi OCR terdepan untuk akurasi tinggi.
        </p>
      </div>

      {{-- Feature 2 --}}
      <div class="card-hover bg-white p-8 rounded-lg shadow-sm hover:shadow-xl transition-all">
        <div class="w-14 h-14 rounded-lg bg-brand/10 flex items-center justify-center mb-4">
          <i class="fas fa-shield-alt text-2xl text-brand"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Verifikasi Ganda</h3>
        <p class="text-gray-600 leading-relaxed">
          Setiap dokumen melalui verifikasi berlapis oleh Pengadilan Agama dan Disdukcapil untuk kepastian hukum.
        </p>
      </div>

      {{-- Feature 3 --}}
      <div class="card-hover bg-white p-8 rounded-lg shadow-sm hover:shadow-xl transition-all">
        <div class="w-14 h-14 rounded-lg bg-brand/10 flex items-center justify-center mb-4">
          <i class="fas fa-map-marker-alt text-2xl text-brand"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Pelacakan Real-Time</h3>
        <p class="text-gray-600 leading-relaxed">
          Lacak status pengajuan kapan saja melalui token unik yang dikirim langsung ke nomor WhatsApp Anda.
        </p>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    ALUR KERJA
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="section-title">Alur Kerja Sistem</h2>
      <p class="section-subtitle mx-auto">
        Proses yang dirancang untuk memudahkan Anda dari awal hingga akhir.
      </p>
    </div>

    <div class="grid md:grid-cols-4 gap-6">
      {{-- Step 1 --}}
      <div class="relative">
        <div class="text-center">
          <div class="w-16 h-16 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xl mx-auto mb-4 relative z-10">
            1
          </div>
          <h4 class="font-bold text-gray-900 mb-2">Buat Akun</h4>
          <p class="text-sm text-gray-600">Daftar dengan NIK dan data diri Anda</p>
        </div>
        <div class="absolute top-8 left-1/2 w-full h-1 bg-gray-200 -z-0 md:-translate-x-1/2" style="width: calc(100% + 1.5rem); left: 50%;"></div>
      </div>

      {{-- Step 2 --}}
      <div class="relative">
        <div class="text-center">
          <div class="w-16 h-16 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xl mx-auto mb-4 relative z-10">
            2
          </div>
          <h4 class="font-bold text-gray-900 mb-2">Upload Dokumen</h4>
          <p class="text-sm text-gray-600">Unggah dokumen perceraian dan identitas</p>
        </div>
        <div class="absolute top-8 left-1/2 w-full h-1 bg-gray-200 -z-0 md:-translate-x-1/2" style="width: calc(100% + 1.5rem); left: 50%;"></div>
      </div>

      {{-- Step 3 --}}
      <div class="relative">
        <div class="text-center">
          <div class="w-16 h-16 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xl mx-auto mb-4 relative z-10">
            3
          </div>
          <h4 class="font-bold text-gray-900 mb-2">Verifikasi</h4>
          <p class="text-sm text-gray-600">Tim kami memverifikasi dokumen Anda</p>
        </div>
        <div class="absolute top-8 left-1/2 w-full h-1 bg-gray-200 -z-0 md:-translate-x-1/2" style="width: calc(100% + 1.5rem); left: 50%;"></div>
      </div>

      {{-- Step 4 --}}
      <div>
        <div class="text-center">
          <div class="w-16 h-16 rounded-full bg-green-600 text-white flex items-center justify-center font-bold text-xl mx-auto mb-4">
            ✓
          </div>
          <h4 class="font-bold text-gray-900 mb-2">Selesai</h4>
          <p class="text-sm text-gray-600">Dokumen berhasil diperbarui</p>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    PENGGUNA SISTEM
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="section-title">Untuk Siapa?</h2>
      <p class="section-subtitle mx-auto">
        Sistem ini dirancang untuk melayani berbagai pengguna sesuai dengan peran dan kebutuhannya.
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
      {{-- User Type 1 --}}
      <div class="card-hover bg-white p-8 rounded-lg shadow-sm">
        <div class="w-20 h-20 rounded-lg bg-blue-100 flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-users text-4xl text-brand"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-gray-900 mb-3">Masyarakat Umum</h3>
        <p class="text-gray-600 text-center leading-relaxed mb-4">
          Pengajuan pembaruan dokumen pasca perceraian tanpa perlu akun atau membawa dokumen fisik ke kantor.
        </p>
        <div class="text-center">
          <a href="{{ route('public.submit.create') }}" class="inline-block text-brand font-semibold hover:text-brand-dark transition-colors">
            Ajukan Sekarang <i class="fas fa-arrow-right ml-2"></i>
          </a>
        </div>
      </div>

      {{-- User Type 2 --}}
      <div class="card-hover bg-white p-8 rounded-lg shadow-sm">
        <div class="w-20 h-20 rounded-lg bg-green-100 flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-balance-scale text-4xl text-green-600"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-gray-900 mb-3">Pengadilan Agama</h3>
        <p class="text-gray-600 text-center leading-relaxed mb-4">
          Kelola kasus perceraian, verifikasi dokumen, dan sinkronisasi data dengan Disdukcapil secara real-time.
        </p>
        <div class="text-center">
          <a href="{{ route('auth.login') }}" class="inline-block text-green-600 font-semibold hover:text-green-700 transition-colors">
            Masuk Sistem <i class="fas fa-arrow-right ml-2"></i>
          </a>
        </div>
      </div>

      {{-- User Type 3 --}}
      <div class="card-hover bg-white p-8 rounded-lg shadow-sm">
        <div class="w-20 h-20 rounded-lg bg-purple-100 flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-id-card text-4xl text-purple-600"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-gray-900 mb-3">Disdukcapil</h3>
        <p class="text-gray-600 text-center leading-relaxed mb-4">
          Validasi dokumen, verifikasi data kependudukan, dan lakukan pembaruan pada sistem kependudukan terpadu.
        </p>
        <div class="text-center">
          <a href="{{ route('auth.login') }}" class="inline-block text-purple-600 font-semibold hover:text-purple-700 transition-colors">
            Masuk Sistem <i class="fas fa-arrow-right ml-2"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    STATISTIK
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <div class="grid md:grid-cols-4 gap-8 mb-16">
      <div class="text-center">
        <p class="text-4xl md:text-5xl font-bold text-brand mb-2">1,200+</p>
        <p class="text-gray-600">Kasus Diproses</p>
      </div>
      <div class="text-center">
        <p class="text-4xl md:text-5xl font-bold text-brand mb-2">98%</p>
        <p class="text-gray-600">Tingkat Akurasi</p>
      </div>
      <div class="text-center">
        <p class="text-4xl md:text-5xl font-bold text-brand mb-2">24/7</p>
        <p class="text-gray-600">Layanan Pelacakan</p>
      </div>
      <div class="text-center">
        <p class="text-4xl md:text-5xl font-bold text-brand mb-2">2.5d</p>
        <p class="text-gray-600">Waktu Pemrosesan Rata-rata</p>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    TEKNOLOGI
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="section-title">Teknologi Terdepan</h2>
      <p class="section-subtitle mx-auto">
        Dibangun dengan stack teknologi modern dan scalable untuk performa optimal.
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
      <div class="bg-white p-8 rounded-lg shadow-sm text-center">
        <i class="fab fa-laravel text-5xl text-red-600 mb-4"></i>
        <h4 class="font-bold text-gray-900">Laravel 11</h4>
        <p class="text-sm text-gray-600 mt-2">Backend framework modern & robust</p>
      </div>
      <div class="bg-white p-8 rounded-lg shadow-sm text-center">
        <i class="fas fa-database text-5xl text-blue-600 mb-4"></i>
        <h4 class="font-bold text-gray-900">MySQL & Neo4j</h4>
        <p class="text-sm text-gray-600 mt-2">Database relasional & graph</p>
      </div>
      <div class="bg-white p-8 rounded-lg shadow-sm text-center">
        <i class="fas fa-brain text-5xl text-purple-600 mb-4"></i>
        <h4 class="font-bold text-gray-900">Python OCR</h4>
        <p class="text-sm text-gray-600 mt-2">Ekstraksi teks otomatis cerdas</p>
      </div>
    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
    CTA FOOTER
    ═══════════════════════════════════════════════════════════════════════════ --}}
<section class="gradient-hero text-white py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="text-3xl md:text-4xl font-bold mb-6">Siap Mulai?</h2>
    <p class="text-blue-100 text-lg mb-10 leading-relaxed">
      Ajukan pembaruan dokumen Anda sekarang atau lacak pengajuan yang sudah ada dengan cepat dan mudah.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="{{ route('public.submit.create') }}"
        class="px-8 py-4 bg-white text-brand font-bold rounded-lg hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
        <i class="fas fa-file-alt"></i> Pengajuan Baru
      </a>
      <a href="{{ route('tracking.public') }}"
        class="px-8 py-4 border-2 border-white text-white font-bold rounded-lg hover:bg-white/10 transition-colors inline-flex items-center gap-2">
        <i class="fas fa-search"></i> Lacak Pengajuan
      </a>
      <a href="{{ route('auth.login') }}"
        class="px-8 py-4 border-2 border-white text-white font-bold rounded-lg hover:bg-white/10 transition-colors inline-flex items-center gap-2">
        <i class="fas fa-sign-in-alt"></i> Masuk Sistem
      </a>
    </div>
  </div>
</section>

@endsection
