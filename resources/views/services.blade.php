@extends('layouts.public')

@section('title', 'SiPadu - Layanan Kami')

@push('styles')
<style>
  /* Animations — reuse same pattern as welcome-new */
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
  .observe-fade { will-change: opacity, transform; }
</style>
@endpush

@section('content')

{{-- ===================================================================
    SECTION 1 — HERO TITLE
    =================================================================== --}}
<section class="bg-cream px-4 sm:px-6 lg:px-8 pt-16 pb-12 md:pt-20 md:pb-16">
  <div class="max-w-7xl mx-auto text-center">
    <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25 mb-4 observe-fade">Layanan Kami</p>
    <h1 class="text-3xl sm:text-4xl md:text-[2.75rem] lg:text-5xl leading-[1.15] font-medium tracking-tight text-brown max-w-3xl mx-auto mb-6 observe-fade">
      Cara Kami Bekerja Sama Dengan Anda
    </h1>
    <p class="text-base md:text-lg text-brown/60 max-w-xl mx-auto leading-relaxed observe-fade">
      Solusi terintegrasi untuk pembaruan dokumen kependudukan pasca perceraian, dari pengajuan hingga penyelesaian.
    </p>
  </div>
</section>


{{-- ===================================================================
    SECTION 2 — THREE SERVICE CARDS
    =================================================================== --}}
<section class="bg-cream px-4 sm:px-6 lg:px-8 py-12 md:py-20">
  <div class="max-w-7xl mx-auto">
    <div class="grid md:grid-cols-3 gap-6">

      {{-- Card 1: Pengajuan Pembaruan Dokumen (real) --}}
      <div class="bg-white rounded-2xl p-8 shadow-sm border border-brown/10 card-lift observe-fade fade-up">
        <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6">
          <i class="fas fa-file-alt text-brand text-lg"></i>
        </div>
        <h2 class="text-lg font-semibold text-brown mb-3">Pengajuan Pembaruan Dokumen</h2>
        <p class="text-sm text-brown/60 leading-relaxed mb-6">
          Ajukan pembaruan dokumen kependudukan pasca perceraian secara online. Upload dokumen pendukung dan lacak prosesnya secara real-time.
        </p>
        <ul class="space-y-2 text-xs text-brown/50 mb-8">
          <li class="flex items-start gap-2">
            <i class="fas fa-check text-brand mt-0.5 text-[10px]"></i>
            <span>Pengisian formulir online yang mudah</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fas fa-check text-brand mt-0.5 text-[10px]"></i>
            <span>Upload dokumen bukti pendukung</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fas fa-check text-brand mt-0.5 text-[10px]"></i>
            <span>Proses verifikasi oleh petugas PA</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fas fa-check text-brand mt-0.5 text-[10px]"></i>
            <span>Notifikasi status otomatis</span>
          </li>
        </ul>
        <a href="{{ route('public.submit.create') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-brand hover:gap-3 transition-all">
          Mulai Pengajuan <i class="fas fa-arrow-right text-xs"></i>
        </a>
      </div>

      {{-- Card 2: Dummy --}}
      <div class="bg-white/50 rounded-2xl p-8 border border-brown/10 border-dashed observe-fade fade-up fade-up-delay-1">
        <div class="w-12 h-12 bg-brown/5 rounded-xl flex items-center justify-center mb-6">
          <i class="fas fa-puzzle-piece text-brown/30 text-lg"></i>
        </div>
        <h2 class="text-lg font-semibold text-brown/50 mb-3">Layanan Lainnya</h2>
        <p class="text-sm text-brown/40 leading-relaxed mb-6">
          Layanan tambahan untuk kebutuhan Anda. Segera hadir untuk mendukung proses pembaruan dokumen Anda.
        </p>
        <p class="text-xs text-brown/30 italic">Segera hadir</p>
      </div>

      {{-- Card 3: Dummy --}}
      <div class="bg-white/50 rounded-2xl p-8 border border-brown/10 border-dashed observe-fade fade-up fade-up-delay-2">
        <div class="w-12 h-12 bg-brown/5 rounded-xl flex items-center justify-center mb-6">
          <i class="fas fa-puzzle-piece text-brown/30 text-lg"></i>
        </div>
        <h2 class="text-lg font-semibold text-brown/50 mb-3">Fitur Masa Depan</h2>
        <p class="text-sm text-brown/40 leading-relaxed mb-6">
          Kami terus mengembangkan fitur baru untuk meningkatkan pengalaman Anda. Nantikan inovasi-inovasi yang sedang kami siapkan.
        </p>
        <p class="text-xs text-brown/30 italic">Dalam pengembangan</p>
      </div>

    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 3 — GUIDANCE / INFO SECTION
    =================================================================== --}}
<section class="bg-cream px-4 sm:px-6 lg:px-8 py-12 md:py-20">
  <div class="max-w-7xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12 md:gap-16 items-center">

      {{-- Left: Visual --}}
      <div class="observe-fade fade-up">
        <div class="bg-brand rounded-2xl p-10 text-cream relative overflow-hidden">
          <div class="absolute top-0 right-0 w-48 h-48 bg-green-sm/15 rounded-full blur-3xl"></div>
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-12 h-12 rounded-full bg-cream/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-cream" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v1.2c0 .7.5 1.2 1.2 1.2h16.8c.7 0 1.2-.5 1.2-1.2v-1.2c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
              </div>
              <span class="font-semibold">Butuh Bantuan?</span>
            </div>
            <p class="text-sm text-cream/70 leading-relaxed mb-6">
              Tim kami siap membantu Anda dalam setiap tahap proses pembaruan dokumen. Jika Anda memiliki pertanyaan atau butuh arahan, jangan ragu untuk menghubungi kami.
            </p>
            <div class="flex flex-wrap gap-3">
              <a href="{{ route('tracking.public') }}"
                 class="px-5 py-2.5 bg-cream text-brand text-sm font-semibold rounded-full hover:opacity-90 transition">
                Lacak Pengajuan
              </a>
              <a href="{{ route('tentang') }}"
                 class="px-5 py-2.5 border border-cream/25 text-cream text-sm font-medium rounded-full hover:bg-cream/5 transition">
                Pelajari Lebih Lanjut
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- Right: Info --}}
      <div class="observe-fade fade-up">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25 mb-4">Informasi</p>
        <h2 class="text-2xl md:text-3xl font-medium text-brown leading-tight mb-6">
          Lacak & Kelola Pengajuan Anda
        </h2>
        <div class="space-y-5">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-green-sm/20 flex items-center justify-center shrink-0 mt-0.5">
              <i class="fas fa-search text-brand text-sm"></i>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-brown mb-1">Lacak Status</h3>
              <p class="text-xs text-brown/50 leading-relaxed">
                Masukkan token pengajuan Anda untuk mengetahui status terkini proses verifikasi.
              </p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-sm/20 flex items-center justify-center shrink-0 mt-0.5">
              <i class="fas fa-clock text-brand text-sm"></i>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-brown mb-1">Estimasi Waktu</h3>
              <p class="text-xs text-brown/50 leading-relaxed">
                Proses verifikasi biasanya memakan waktu 3-5 hari kerja setelah dokumen lengkap diverifikasi.
              </p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-brand/10 flex items-center justify-center shrink-0 mt-0.5">
              <i class="fas fa-shield-alt text-brand text-sm"></i>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-brown mb-1">Keamanan Data</h3>
              <p class="text-xs text-brown/50 leading-relaxed">
                Seluruh data Anda dilindungi dengan enkripsi dan akses terbatas hanya oleh petugas berwenang.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 4 — TESTIMONIALS
    =================================================================== --}}
<section class="bg-cream px-4 sm:px-6 lg:px-8 py-12 md:py-20">
  <div class="max-w-6xl mx-auto">
    <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25 mb-4 text-center observe-fade">Testimoni</p>
    <h2 class="text-2xl md:text-4xl font-medium text-brown text-center mb-3 observe-fade">
      Apa Kata Mereka
    </h2>
    <p class="text-sm text-brown/50 text-center mb-12 max-w-lg mx-auto observe-fade">
      Bergabunglah dengan ribuan pengguna yang telah merasakan kemudahan proses pembaruan dokumen melalui SiPadu.
    </p>

    <div class="grid md:grid-cols-2 gap-6">

      {{-- Testimonial 1 --}}
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-brown/10 card-lift observe-fade fade-up">
        <div class="flex items-center gap-1 mb-4">
          @for($i=0; $i<5; $i++)<i class="fas fa-star text-amber-400 text-xs"></i>@endfor
        </div>
        <blockquote class="text-sm text-brown/70 leading-relaxed mb-5">
          "SiPadu sangat membantu kami dalam mempercepat proses transmisi putusan pengadilan ke Disdukcapil. Sebelumnya proses ini memakan waktu berminggu-minggu, sekarang hanya dalam hitungan hari."
        </blockquote>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-green-sm/30 flex items-center justify-center text-sm font-semibold text-brand">HS</div>
          <div>
            <p class="text-sm font-medium text-brown">H. Ahmad Surya</p>
            <p class="text-xs text-brown/40">Ketua PA Kota Jakarta Selatan</p>
          </div>
        </div>
      </div>

      {{-- Testimonial 2 --}}
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-brown/10 card-lift observe-fade fade-up fade-up-delay-1">
        <div class="flex items-center gap-1 mb-4">
          @for($i=0; $i<5; $i++)<i class="fas fa-star text-amber-400 text-xs"></i>@endfor
        </div>
        <blockquote class="text-sm text-brown/70 leading-relaxed mb-5">
          "Sebagai warga yang baru mengalami perceraian, saya bisa melacak status pengajuan dokumen dari rumah tanpa harus bolak-balik kantor. Sangat memudahkan!"
        </blockquote>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-coral/20 flex items-center justify-center text-sm font-semibold text-brown">DR</div>
          <div>
            <p class="text-sm font-medium text-brown">Dewi Rahayu</p>
            <p class="text-xs text-brown/40">Warga, Jakarta Selatan</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 5 — FAQ ACCORDION
    =================================================================== --}}
<section class="bg-cream px-4 sm:px-6 lg:px-8 py-12 md:py-20">
  <div class="max-w-3xl mx-auto">
    <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25 mb-4 text-center observe-fade">FAQ</p>
    <h2 class="text-2xl md:text-4xl font-medium text-brown text-center mb-12 observe-fade">
      Pertanyaan yang Sering Diajukan
    </h2>

    <div class="space-y-3">

      {{-- FAQ 1 --}}
      <div class="faq-item bg-white rounded-xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up">
        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left" type="button">
          <span class="text-sm font-semibold text-brown pr-4">Apa itu SiPadu?</span>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 faq-icon"></i>
        </button>
        <div class="faq-content hidden">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              SiPadu (Sistem Pembaruan Dokumen) adalah platform terintegrasi yang memudahkan proses pembaruan dokumen kependudukan bagi Anda yang baru saja mengalami perceraian. Sistem ini menghubungkan Pengadilan Agama dan Dinas Kependudukan secara otomatis.
            </p>
          </div>
        </div>
      </div>

      {{-- FAQ 2 --}}
      <div class="faq-item bg-white rounded-xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up fade-up-delay-1">
        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left" type="button">
          <span class="text-sm font-semibold text-brown pr-4">Siapa yang bisa menggunakan layanan ini?</span>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 faq-icon"></i>
        </button>
        <div class="faq-content hidden">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Layanan SiPadu tersedia untuk masyarakat umum yang baru saja mengalami perceraian dan memerlukan pembaruan dokumen kependudukan (KTP, KK) sesuai dengan status perkawinan terbaru mereka.
            </p>
          </div>
        </div>
      </div>

      {{-- FAQ 3 --}}
      <div class="faq-item bg-white rounded-xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up fade-up-delay-2">
        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left" type="button">
          <span class="text-sm font-semibold text-brown pr-4">Berapa lama proses pembaruan dokumen?</span>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 faq-icon"></i>
        </button>
        <div class="faq-content hidden">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Proses verifikasi typically memakan waktu 3-5 hari kerja setelah dokumen Anda dinyatakan lengkap. Waktu pembaruan fisik KTP/KK tergantung pada jadwal dari Disdukcapil setempat.
            </p>
          </div>
        </div>
      </div>

      {{-- FAQ 4 --}}
      <div class="faq-item bg-white rounded-xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up fade-up-delay-3">
        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left" type="button">
          <span class="text-sm font-semibold text-brown pr-4">Apakah saya perlu datang ke pengadilan?</span>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 faq-icon"></i>
        </button>
        <div class="faq-content hidden">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Tidak. Seluruh proses dapat dilakukan secara online — mulai dari pengajuan, upload dokumen, hingga pelacakan status. Anda hanya perlu datang ke Disdukcapil setempat untuk pengambilan dokumen fisik jika diperlukan.
            </p>
          </div>
        </div>
      </div>

      {{-- FAQ 5 --}}
      <div class="faq-item bg-white rounded-xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up">
        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left" type="button">
          <span class="text-sm font-semibold text-brown pr-4">Dokumen apa saja yang diperlukan?</span>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 faq-icon"></i>
        </button>
        <div class="faq-content hidden">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Anda memerlukan salinan putusan pengadilan yang telah memperoleh hukum tetap, KTP lama, Kartu Keluarga lama, serta dokumen identitas pendukung lainnya. Daftar lengkap tersedia saat proses pengajuan.
            </p>
          </div>
        </div>
      </div>

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
  });

  // ── FAQ Accordion ─────────────────────────────────────────────────
  document.querySelectorAll('.faq-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const content = this.nextElementSibling;
      const icon = this.querySelector('.faq-icon');
      const isOpen = !content.classList.contains('hidden');

      // Close all FAQs
      document.querySelectorAll('.faq-content').forEach(function (c) {
        c.classList.add('hidden');
      });
      document.querySelectorAll('.faq-icon').forEach(function (i) {
        i.style.transform = 'rotate(0deg)';
      });

      // Toggle current
      if (!isOpen) {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
      }
    });
  });
</script>
@endpush
