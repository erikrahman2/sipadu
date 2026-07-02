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
      Kerja Sama Pengadilan Agama Painan<br class="hidden sm:block" /> dan Disdukcapil Kabupaten Pesisir Selatan
    </h1>
    <p class="text-base md:text-lg text-brown/60 max-w-2xl mx-auto leading-relaxed observe-fade">
      Layanan terintegrasi untuk pembaruan dokumen kependudukan pasca perceraian —
      dari pengajuan hingga penerbitan KK dan KTP-el baru, tanpa harus bolak-balik ke kantor.
    </p>
  </div>
</section>


{{-- ===================================================================
    SECTION 2 — LAYANAN CARDS (Summary + Click Expand Animation)
    =================================================================== --}}
<section class="bg-cream px-4 sm:px-6 lg:px-8 py-12 md:py-20">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25 mb-4">Daftar Layanan</p>
      <h2 class="text-2xl md:text-3xl font-medium text-brown mb-4">Layanan Unggulan Kerja Sama</h2>
      <p class="text-sm text-brown/50 max-w-2xl mx-auto">Berbagai layanan pembaruan dokumen kependudukan yang disediakan melalui sinergi antara Pengadilan Agama Painan dan Disdukcapil Pessel.</p>
    </div>

    {{-- CMS-managed layanan categories --}}
    <div class="space-y-3">
      @foreach($categories as $key => $cat)
      <div class="layan-card bg-white rounded-2xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up {{ ['','fade-up-delay-1','fade-up-delay-2'][$loop->index % 3] }}">
        <button class="layan-toggle w-full flex items-center justify-between p-6 text-left cursor-pointer" type="button" aria-expanded="false">
          <div class="flex items-start gap-4 flex-1">
            <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center shrink-0">
              <i class="{{ $cat['icon'] ?? 'fas fa-link' }} text-brand text-xl"></i>
            </div>
            <div>
              <h3 class="text-base font-semibold text-brown mb-1">{{ $cat['title'] }}</h3>
              <p class="text-xs text-brown/50 leading-relaxed">{{ $cat['subtitle'] }}</p>
            </div>
          </div>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 layan-icon ml-4"></i>
        </button>

        <div class="layan-detail" style="display:none">
          <div class="px-6 pb-6 pt-0">
            <p class="text-xs text-brown/40 mb-4">{{ $cat['subtitle'] }}:</p>
            <div class="space-y-2">
              @if(!empty($groups[$key]))
              @foreach($groups[$key] as $item)
              <a href="{{ $item['url'] }}" class="group flex items-start gap-4 bg-brown/[0.03] hover:bg-brown/[0.06] rounded-xl p-4 transition-all duration-200">
                <div class="w-9 h-9 bg-brand/10 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                  <i class="{{ $item['icon'] ?? 'fas fa-link' }} text-brand text-sm"></i>
                </div>
                <div>
                  <p class="text-sm font-semibold text-brown">{{ $item['nama'] }}</p>
                  <p class="text-xs text-brown/40 mt-0.5">{{ $item['deskripsi'] }}</p>
                  <p class="text-[11px] text-brand font-medium mt-1.5 group-hover:underline">Ajukan sekarang →</p>
                </div>
              </a>
              @endforeach
              @else
              <p class="text-xs text-brown/40 italic">Belum ada layanan di kategori ini.</p>
              @endif
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 3 — ALOUR KERJA SAMA (Kolaborasi PA & Disdukcapil)
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
                <i class="fas fa-handshake text-cream text-lg"></i>
              </div>
              <span class="font-semibold">Kolaborasi Dua Instansi</span>
            </div>
            <p class="text-sm text-cream/70 leading-relaxed mb-4">
              SiPadu merupakan jembatan digital antara <strong>Pengadilan Agama Painan</strong> dan <strong>Disdukcapil Kabupaten Pesisir Selatan</strong>. Kerja sama ini mempercepat pembaruan dokumen kependudukan pasca perceraian.
            </p>
            <ul class="text-sm text-cream/70 space-y-2 mb-6">
              <li class="flex items-start gap-2"><i class="fas fa-check text-green-sm mt-1 text-[10px]"></i><span>PA Painan: penerbitan putusan & ikhtisar perceraian</span></li>
              <li class="flex items-start gap-2"><i class="fas fa-check text-green-sm mt-1 text-[10px]"></i><span>Disdukcapil Pessel: pembaruan KK, KTP-el, Akta</span></li>
              <li class="flex items-start gap-2"><i class="fas fa-check text-green-sm mt-1 text-[10px]"></i><span>Sinkronisasi data otomatis antar-instansi</span></li>
            </ul>
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
        <p class="text-[10px] font-semibold uppercase tracking-widest text-brown/25 mb-4">Informasi Layanan</p>
        <h2 class="text-2xl md:text-3xl font-medium text-brown leading-tight mb-6">
          Lacak & Kelola Pengajuan Anda
        </h2>
        <div class="space-y-5">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-green-sm/20 flex items-center justify-center shrink-0 mt-0.5">
              <i class="fas fa-search text-brand text-sm"></i>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-brown mb-1">Lacak Status Pengajuan</h3>
              <p class="text-xs text-brown/50 leading-relaxed">
                Masukkan token pengajuan Anda untuk melihat status terkini — apakah sedang diverifikasi PA Painan atau diproses Disdukcapil Pessel.
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
                Verifikasi PA Painan dan pembaruan Disdukcapil Pessel biasanya memakan waktu 3-5 hari kerja setelah dokumen lengkap.
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
                Data Anda dienkripsi dan hanya dapat diakses oleh petugas berwenang di PA Painan dan Disdukcapil Pessel sesuai tugasnya.
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
      Pengalaman masyarakat dan petugas Pengadilan Agama Painan serta Disdukcapil Pessel dalam menggunakan SiPadu.
    </p>

    <div class="grid md:grid-cols-2 gap-6">

      {{-- Testimonial 1 --}}
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-brown/10 card-lift observe-fade fade-up">
        <div class="flex items-center gap-1 mb-4">
          @for($i=0; $i<5; $i++)<i class="fas fa-star text-amber-400 text-xs"></i>@endfor
        </div>
        <blockquote class="text-sm text-brown/70 leading-relaxed mb-5">
          "Kerja sama SiPadu antara PA Painan dan Disdukcapil Pessel mempercepat transmisi putusan ke data kependudukan. Yang biasanya berminggu-minggu, kini selesai dalam hitungan hari."
        </blockquote>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-green-sm/30 flex items-center justify-center text-sm font-semibold text-brand">HM</div>
          <div>
            <p class="text-sm font-medium text-brown">H. Mukhtar, S.Ag., M.Ag.</p>
            <p class="text-xs text-brown/40">Ketua Pengadilan Agama Painan</p>
          </div>
        </div>
      </div>

      {{-- Testimonial 2 --}}
      <div class="bg-white rounded-2xl p-7 shadow-sm border border-brown/10 card-lift observe-fade fade-up fade-up-delay-1">
        <div class="flex items-center gap-1 mb-4">
          @for($i=0; $i<5; $i++)<i class="fas fa-star text-amber-400 text-xs"></i>@endfor
        </div>
        <blockquote class="text-sm text-brown/70 leading-relaxed mb-5">
          "Sebagai warga Pessel yang baru bercerai, saya bisa mengurus pembaruan KTP dan KK tanpa bolak-balik ke Painan atau Punnajawa. Hemat waktu dan biaya."
        </blockquote>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-coral/20 flex items-center justify-center text-sm font-semibold text-brown">SR</div>
          <div>
            <p class="text-sm font-medium text-brown">Siti Rahmawati</p>
            <p class="text-xs text-brown/40">Warga, Kabupaten Pesisir Selatan</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


{{-- ===================================================================
    SECTION 5 — FAQ ACCORDION
    =================================================================== --}}
<section id="faq" class="bg-cream px-4 sm:px-6 lg:px-8 py-12 md:py-20">
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
        <div class="faq-content" style="display:none">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              <strong>SiPadu (Sistem Pembaruan Dokumen)</strong> adalah platform digital hasil kerja sama <strong>Pengadilan Agama Painan</strong> dan <strong>Disdukcapil Kabupaten Pesisir Selatan</strong>. Sistem ini memudahkan pembaruan dokumen kependudukan (KTP-el, KK, Akta Perceraian) bagi warga yang baru saja mengalami perceraian, tanpa harus mengurus dokumen fisik atau bolak-balik ke kantor.
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
        <div class="faq-content" style="display:none">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Layanan SiPadu tersedia untuk masyarakat <strong>Kabupaten Pesisir Selatan</strong> yang telah memiliki putusan perceraian dari Pengadilan Agama Painan dan memerlukan pembaruan dokumen kependudukan (KK, KTP-el, Akta Perceraian) sesuai status perkawinan terbaru.
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
        <div class="faq-content" style="display:none">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Setelah pengajuan Anda lengkap, verifikasi oleh <strong>PA Painan</strong> memakan waktu sekitar 1-2 hari kerja, kemudian Disdukcapil Pessel memproses pembaruan data kependudukan dalam 2-3 hari kerja berikutnya. Total rata-rata 3-5 hari kerja.
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
        <div class="faq-content" style="display:none">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Tidak. Seluruh proses dapat dilakukan secara online — mulai dari pengajuan, upload dokumen putusan PA Painan, hingga pelacakan status. Anda hanya perlu datang ke <strong>Disdukcapil Pessel</strong> untuk pengambilan dokumen fisik (KTP-el/KK baru) jika diperlukan.
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
        <div class="faq-content" style="display:none">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              Anda memerlukan: <strong>(1)</strong> salinan putusan perceraian dari PA Painan yang telah berkekuatan hukum tetap, <strong>(2)</strong> KTP-el lama, <strong>(3)</strong> Kartu Keluarga lama, dan <strong>(4)</strong> dokumen identitas pendukung lain. Daftar lengkap tersedia saat proses pengajuan.
            </p>
          </div>
        </div>
      </div>

      {{-- FAQ 6 --}}
      <div class="faq-item bg-white rounded-xl shadow-sm border border-brown/10 overflow-hidden observe-fade fade-up fade-up-delay-1">
        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left" type="button">
          <span class="text-sm font-semibold text-brown pr-4">Apa dasar hukum kerja sama ini?</span>
          <i class="fas fa-chevron-down text-brown/30 text-xs transition-transform duration-300 faq-icon"></i>
        </button>
        <div class="faq-content" style="display:none">
          <div class="px-6 pb-6">
            <p class="text-sm text-brown/60 leading-relaxed">
              <strong>Pengadilan Agama Painan</strong> dibentuk berdasarkan Peraturan Pemerintah Nomor 45 Tahun 1957 dan diperkuat dengan Penetapan Menteri Agama Nomor 58 Tahun 1957. <strong>Disdukcapil Pessel</strong> menyelenggarakan layanan administrasi kependudukan sesuai regulasi Kementerian Dalam Negeri. SiPadu merupakan implementasi digital dari sinergi kedua instansi tersebut.
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

  // ── Event delegation: FAQ + Layanan accordions ──────────────────────
  document.addEventListener('click', function (e) {

    // ── FAQ toggle ───────────────────────────────────────────
    var faqBtn = e.target.closest('.faq-toggle');
    if (faqBtn) {
      var content = faqBtn.nextElementSibling;
      var icon = faqBtn.querySelector('.faq-icon');

      if (content.style.display === 'block') {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
      } else {
        document.querySelectorAll('.faq-content').forEach(function (c) { c.style.display = 'none'; });
        document.querySelectorAll('.faq-icon').forEach(function (i) { i.style.transform = 'rotate(0deg)'; });
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
      }
      return;
    }

    // ── Layanan toggle ──────────────────────────────────���────
    var layBtn = e.target.closest('.layan-toggle');
    if (layBtn) {
      var detail = layBtn.nextElementSibling;
      var icon = layBtn.querySelector('.layan-icon');

      if (detail.style.display === 'block') {
        detail.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
      } else {
        document.querySelectorAll('.layan-detail').forEach(function (d) { d.style.display = 'none'; });
        document.querySelectorAll('.layan-icon').forEach(function (i) { i.style.transform = 'rotate(0deg)'; });
        detail.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
        layBtn.setAttribute('aria-expanded', 'true');
      }
      return;
    }
  });

  // ── Smooth scroll for FAQ anchor ──────────────────────────────────
  document.addEventListener('click', function(e) {
    if (e.target.href && e.target.getAttribute('href') === '#faq') {
      e.preventDefault();
      var target = document.querySelector('#faq');
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
</script>
