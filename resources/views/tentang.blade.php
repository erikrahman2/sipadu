@extends('layouts.public')

@section('title', 'Tentang SiPadu')

@push('styles')
<style>
/* ===== Animation system — same as welcome-new & berita ===== */
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
.fade-up-delay-4 { transition-delay: 0.4s; }
.observe-fade { will-change: opacity, transform; }

/* Hero */
.hero { position:relative; height:100vh; min-height:500px; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.hero__bg { position:absolute; inset:0; background:url('/assets/asset(6).jpg') center/cover no-repeat; }
.hero__ov { position:absolute; inset:0; background:linear-gradient(180deg,rgba(13,31,8,.35),rgba(13,31,8,.55)); }
.hero__ct { position:relative; z-index:2; text-align:center; padding:0 1.5rem; }
.hero__ct .fade-up { opacity:1; transform:none; transition:none; }

/* Hero banner */
.hero-banner { position:relative; height:60vh; min-height:350px; overflow:hidden; display:flex; align-items:flex-end; }
.hero-banner__bg { position:absolute; inset:0; background-size:cover; background-position:center; }
.hero-banner__ov { position:absolute; inset:0; }
.hero-banner__ct { position:relative; z-index:2; padding:3rem; max-width:80rem; margin:0 auto; width:100%; }

/* Image card zoom effect */
.ic { position:relative; overflow:hidden; border-radius:14px; background-size:cover; background-position:center; will-change:transform; transform:scale(1.06); transition:transform 1.2s ease; }
.ic.v { transform:scale(1); }

/* Typography */
.tt { font-size:clamp(2rem,4.5vw,4.5rem); font-weight:800; line-height:1.1; color:#31110F; }
.section-dark .tt { color:#fff; }
.section-dark .tt em { color:#FFF0C4; }
.tt em { color:#0D1F08; font-style:italic; }
.cn { font-size:clamp(3rem,6vw,4.5rem); font-weight:800; line-height:1; font-variant-numeric:tabular-nums; color:#0D1F08; }

/* Decorative rule */
.rule { height:2px; margin:0 auto; max-width:56rem; background:linear-gradient(90deg,transparent,#0D1F08 30%,#86A77C 70%,transparent); opacity:.1; }

/* Keunggulan card lift */
.card-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card-lift:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(49,17,15,0.08); }
</style>
@endpush

@section('content')

<!-- ==================== HERO ==================== -->
<div class="hero">
    <div class="hero__bg"></div>
    <div class="hero__ov"></div>
    <div class="hero__ct">
        <h1 class="fade-up text-3xl md:text-6xl lg:text-7xl font-extrabold text-white leading-tight mb-6">
            SiPadu<br><em class="text-[#FFF0C4] italic">merombak arsitektur</em><br>layanan publik.
        </h1>
        <p class="fade-up fade-up-delay-2 text-lg md:text-xl text-white/60 max-w-2xl mx-auto">
            Jembatan digital antara Pengadilan Agama dan Dinas Kependudukan untuk proses yang transparan dan tanpa kertas.
        </p>
    </div>
</div>

<div class="rule"></div>

{{-- ==================== CMS DYNAMIC SECTIONS ==================== --}}
@if($sections->isEmpty())
    {{-- No CMS content available --}}
@else
    {{-- ============ CMS-DRIVEN SECTIONS ============ --}}
    @foreach($sections as $section)
    @if($section->content_type === 'tentang_sipadu')
    <!-- ==================== CMS: Apa Itu SiPadu ==================== -->
    <section class="bg-[#F7F4EB] py-16 md:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center mb-16">
            <span class="inline-block fade-up text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Tentang Kami</span>
            <h2 class="tt mb-6 leading-snug fade-up fade-up-delay-1">
                {!! $section->title !!}
            </h2>
            <p class="text-sm md:text-base text-[#31110F]/70 max-w-3xl mx-auto leading-relaxed fade-up fade-up-delay-2">
                {!! $section->content !!}
            </p>
        </div>
    </section>
    <div class="rule"></div>

    @elseif($section->content_type === 'institusi_kerja_sama')
    <!-- ==================== CMS: Instansi & Kerja Sama ==================== -->
    <div class="hero-banner">
        <div class="hero-banner__bg" @if($section->image_path) style="background-image:url('{{ asset('storage/' . $section->image_path) }}');" @endif></div>
        <div class="hero-banner__ov" style="background:linear-gradient(90deg,rgba(13,31,8,.7) 0%,rgba(13,31,8,.3) 50%,transparent 80%);"></div>
        <div class="hero-banner__ct">
            <div class="fade-up">
                <p class="text-white/70 text-xs md:text-sm uppercase tracking-widest mb-2">{{ $section->subtitle }}</p>
                <p class="text-white text-xl md:text-3xl font-bold max-w-2xl leading-snug">
                    {!! $section->title !!}
                </p>
            </div>
            <div class="fade-up fade-up-delay-2 mt-6 space-y-4">
                <p class="text-white/80 text-sm md:text-base max-w-2xl leading-relaxed">
                    {!! $section->content !!}
                </p>
            </div>
        </div>
    </div>

    @elseif($section->content_type === 'institusi_pendukung')
    <!-- ==================== CMS: Institusi Pendukung ==================== -->
    <div class="hero-banner">
        <div class="hero-banner__bg" @if($section->image_path) style="background-image:url('{{ asset('storage/' . $section->image_path) }}');" @endif></div>
        <div class="hero-banner__ov" style="background:linear-gradient(90deg,rgba(247,244,235,.9) 0%,rgba(247,244,235,.6) 50%,transparent 80%);"></div>
        <div class="hero-banner__ct">
            <div class="fade-up">
                <p class="text-[#31110F]/80 text-xs md:text-sm uppercase tracking-widest mb-2">{{ $section->subtitle }}</p>
                <p class="text-[#31110F] text-xl md:text-3xl font-bold max-w-2xl leading-snug">
                    {!! $section->title !!}
                </p>
            </div>
            <div class="fade-up fade-up-delay-2 mt-6 space-y-4">
                <p class="text-[#31110F]/80 text-sm md:text-base max-w-2xl leading-relaxed">
                    {!! $section->content !!}
                </p>
            </div>
        </div>
    </div>

    @elseif($section->content_type === 'visi_misi')
    <!-- ==================== CMS: Visi & Misi ==================== -->
    <div class="rule"></div>
    <section class="bg-[#F7F4EB] py-24 sm:py-32 px-6 sm:px-10 lg:px-16">
        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
            <div class="fade-up">
                @if($section->image_path)
                <div class="ic" style="aspect-ratio:1/1;background-image:url('{{ asset('storage/' . $section->image_path) }}');background-size:cover;background-position:center;border-radius:4px;"></div>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-3 mb-8 fade-up">
                    <span class="w-2 h-2 rounded-full bg-[#0D1F08]"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#0D1F08]/50">VISI MISI SIPADU</span>
                </div>
                <div class="fade-up fade-up-delay-1">
                    <h2 class="text-[30px] md:text-[42px] font-bold text-[#111827] leading-[1.15] tracking-tight mb-6">
                        {!! $section->title !!}
                    </h2>
                </div>
                <div class="space-y-4 fade-up fade-up-delay-2">
                    <p class="text-base text-[#31110F]/80 leading-relaxed">
                        {!! $section->content !!}
                    </p>
                </div>
            </div>
        </div>
    </section>

    @elseif($section->content_type === 'fitur_keunggulan')
    <!-- ==================== CMS: Fitur & Keunggulan ==================== -->
    <div class="rule"></div>
    <section class="bg-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12 fade-up">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Keunggulan</span>
                <h2 class="tt mb-3">
                    {!! $section->title !!}
                </h2>
                <p class="text-[#31110F]/70 max-w-xl mx-auto">
                    {!! $section->subtitle !!}
                </p>
            </div>

            @php
                $items = json_decode($section->content, true);
                $isJsonArray = is_array($items) && !empty($items) && isset($items[0]);
            @endphp

            @if($isJsonArray)
            <div class="grid md:grid-cols-2 lg:grid-cols-{{ count($items) > 2 ? count($items) : 4 }} gap-5">
                @foreach($items as $idx => $item)
                <div class="fade-up fade-up-delay-{{ min(($idx % 4) + 1, 4) }}">
                    <div class="ic rounded-2xl bg-[#F7F4EB] p-6 h-full flex flex-col card-lift">
                        <div class="mb-3">
                            @if(!empty($item['icon']))
                                {!! $item['icon'] !!}
                            @else
                                <svg class="w-10 h-10 text-[#0D1F08]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            @endif
                        </div>
                        <h3 class="text-sm font-bold text-[#31110F] mb-1">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-xs text-[#31110F]/70 leading-relaxed">{{ $item['description'] ?? '' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            {{-- Legacy: plain HTML/content text --}}
            <div class="max-w-3xl mx-auto fade-up">
                <p class="text-sm md:text-base text-[#31110F]/80 leading-relaxed space-y-3">
                    {!! $section->content !!}
                </p>
            </div>
            @endif
        </div>
    </section>

    @endif
    @endforeach
@endif

{{-- ==================== SIPADU BANNER (Always displayed) ==================== --}}
<div class="hero-banner">
    <div class="hero-banner__bg" style="background-image:url('/assets/asset(2).jpg');"></div>
    <div class="hero-banner__ov" style="background:linear-gradient(180deg,transparent 10%,rgba(13,31,8,.6) 60%,rgba(13,31,8,.85) 100%);"></div>
    <div class="hero-banner__ct">
        <div class="fade-up">
            <p class="text-white/70 text-xs md:text-sm uppercase tracking-widest mb-2">SiPadu</p>
            <p class="text-white text-xl md:text-3xl font-bold max-w-2xl leading-snug">
                Satu sistem, dua institusi,<br><em class="italic text-[#FFF0C4]">tanpa batas.</em>
            </p>
        </div>
        <div class="fade-up fade-up-delay-2 mt-6 space-y-4">
            <p class="text-white/80 text-sm md:text-base max-w-2xl leading-relaxed">
                Salah satu langkah nyata untuk <strong class="text-[#FFF0C4]">menghilangkan proses manual</strong> transmisi putusan perceraian dari Pengadilan Agama ke Dinas Dukcapil.
            </p>
            <p class="text-white/80 text-sm md:text-base max-w-2xl leading-relaxed">
                Sebelum SiPadu, proses transmisi putusan memakan waktu <strong class="text-[#FFF0C4]">berminggu-minggu</strong> dengan pengiriman dokumen fisik antar-instansi.
            </p>
            <p class="text-white/80 text-sm md:text-base max-w-2xl leading-relaxed">
                Sekarang, <strong class="text-[#FFF0C4]">sinkronisasi data terjadi otomatis dan real-time</strong>, menjamin kepastian hukum bagi masyarakat tanpa penundaan.
            </p>
        </div>
    </div>
</div>

<div class="rule"></div>

{{-- ==================== STATISTICS ==================== --}}
<section class="bg-[#0D1F08] py-12 md:py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto grid grid-cols-3 gap-6 text-center">
        <div class="fade-up fade-up-delay-1">
            <div class="cn text-[#FFF0C4]">2</div>
            <div class="mt-2 text-xs text-[#86A77C] font-medium">Lembaga</div>
        </div>
        <div class="fade-up fade-up-delay-2">
            <div class="cn text-[#FFF0C4]">2</div>
            <div class="mt-2 text-xs text-[#86A77C] font-medium">Instansi</div>
        </div>
        <div class="fade-up fade-up-delay-3">
            <div class="cn text-[#FFF0C4]">1</div>
            <div class="mt-2 text-xs text-[#86A77C] font-medium">SK Penetapan</div>
        </div>
    </div>
</section>

<div class="rule"></div>

{{-- ==================== ALUR KERJA ==================== --}}
<section class="bg-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12 fade-up">
            <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Cara Kerja</span>
            <h2 class="tt mb-3">Alur yang transparan.</h2>
        </div>
        <div class="space-y-10">
            <div class="grid md:grid-cols-2 gap-6 items-center">
                <div class="fade-up">
                    <div class="ic" style="aspect-ratio:16/10;background-image:url('/assets/asset(7).jpg');"></div>
                </div>
                <div class="fade-up fade-up-delay-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-7 h-7 rounded-full bg-[#0D1F08] text-white text-xs font-bold flex items-center justify-center">01</div>
                        <span class="text-xs uppercase tracking-widest text-[#0D1F08]/50">Permohonan</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#31110F] mb-2">Pengajuan Permohonan</h3>
                    <p class="text-sm text-[#31110F]/70 leading-relaxed">Warga mengajukan pemutakhiran data secara online melalui portal SiPadu.</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-6 items-center md:flex-row-reverse">
                <div class="fade-up fade-up-delay-1">
                    <div class="ic" style="aspect-ratio:16/10;background-image:url('/assets/asset(8).jpg');"></div>
                </div>
                <div class="fade-up fade-up-delay-2">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-7 h-7 rounded-full bg-[#0D1F08] text-white text-xs font-bold flex items-center justify-center">02</div>
                        <span class="text-xs uppercase tracking-widest text-[#0D1F08]/50">Verifikasi</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#31110F] mb-2">Verifikasi oleh PA</h3>
                    <p class="text-sm text-[#31110F]/70 leading-relaxed">Pengadilan Agama memverifikasi kelengkapan dokumen dan putusan.</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-6 items-center">
                <div class="fade-up fade-up-delay-2">
                    <div class="ic" style="aspect-ratio:16/10;background-image:url('/assets/asset(9).jpg');"></div>
                </div>
                <div class="fade-up fade-up-delay-3">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-7 h-7 rounded-full bg-[#0D1F08] text-white text-xs font-bold flex items-center justify-center">03</div>
                        <span class="text-xs uppercase tracking-widest text-[#0D1F08]/50">Sinkronisasi</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#31110F] mb-2">Sinkronisasi Data</h3>
                    <p class="text-sm text-[#31110F]/70 leading-relaxed">Data dikirim otomatis ke sistem Dinas Dukcapil melalui API.</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-6 items-center md:flex-row-reverse">
                <div class="fade-up fade-up-delay-3">
                    <div class="ic" style="aspect-ratio:16/10;background-image:url('/assets/asset(10).jpg');"></div>
                </div>
                <div class="fade-up fade-up-delay-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-7 h-7 rounded-full bg-[#0D1F08] text-white text-xs font-bold flex items-center justify-center">04</div>
                        <span class="text-xs uppercase tracking-widest text-[#0D1F08]/50">Pemutakhiran</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#31110F] mb-2">Pembaruan Dokumen</h3>
                    <p class="text-sm text-[#31110F]/70 leading-relaxed">Dinas Dukcapil memperbarui dokumen kependudukan.</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-6 items-center">
                <div class="fade-up fade-up-delay-4">
                    <div class="ic" style="aspect-ratio:16/10;background-image:url('/assets/asset(11).jpg');"></div>
                </div>
                <div class="fade-up">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-7 h-7 rounded-full bg-[#0D1F08] text-white text-xs font-bold flex items-center justify-center">05</div>
                        <span class="text-xs uppercase tracking-widest text-[#0D1F08]/50">Selesai</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#31110F] mb-2">Notifikasi Hasil</h3>
                    <p class="text-sm text-[#31110F]/70 leading-relaxed">Warga menerima notifikasi dan mengunduh dokumen terbaru.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="rule"></div>

{{-- ==================== GALERI ==================== --}}
<section class="bg-white py-16 md:py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12 fade-up">
            <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#0D1F08]/60 mb-4">Galeri</span>
            <h2 class="tt mb-3">Momen di balik layar.</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="fade-up fade-up-delay-1">
                <div class="ic" style="aspect-ratio:3/4;background-image:url('/assets/asset(13).jpg');"></div>
            </div>
            <div class="fade-up fade-up-delay-2">
                <div class="ic" style="aspect-ratio:3/4;background-image:url('/assets/asset(14).jpg');"></div>
            </div>
            <div class="fade-up fade-up-delay-3">
                <div class="ic" style="aspect-ratio:3/4;background-image:url('/assets/asset(15).jpg');"></div>
            </div>
            <div class="fade-up fade-up-delay-4">
                <div class="ic" style="aspect-ratio:3/4;background-image:url('/assets/asset(16).jpg');"></div>
            </div>
        </div>
    </div>
</section>

<div class="rule"></div>

{{-- ==================== TESTIMONI ==================== --}}
<section class="bg-[#0D1F08] py-16 md:py-24 px-4 sm:px-6 lg:px-8 section-dark">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12 fade-up">
            <span class="inline-block text-xs font-semibold uppercase tracking-widest text-[#86A77C]/40 mb-4">Testimoni</span>
            <h2 class="tt mb-3 text-white">Apa kata mereka.</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-5">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 fade-up fade-up-delay-1">
                <blockquote class="text-sm text-[#FFF0C4]/70 leading-relaxed mb-4 italic">
                    "Sebelumnya proses transmisi putusan memakan waktu berminggu-minggu, sekarang hanya dalam hitungan hari."
                </blockquote>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-[#86A77C]/30 flex items-center justify-center text-xs font-semibold text-[#FFF0C4]">HM</div>
                    <div>
                        <p class="text-sm font-medium text-[#FFF0C4]">H. Mukhtar, S.Ag., M.Ag.</p>
                        <p class="text-xs text-white/30">Ketua PA Painan</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 fade-up fade-up-delay-2">
                <blockquote class="text-sm text-[#FFF0C4]/70 leading-relaxed mb-4 italic">
                    "Bisa urus pembaruan KTP dan KK tanpa bolak-balik. Sangat memudahkan!"
                </blockquote>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-xs font-semibold text-[#FFF0C4]">SR</div>
                    <div>
                        <p class="text-sm font-medium text-[#FFF0C4]">Siti Rahmawati</p>
                        <p class="text-xs text-white/30">Warga Pessel</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="rule" style="background:linear-gradient(90deg,transparent,#86A77C 30%,#0D1F08 70%,transparent);"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    var obsFade = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(e.isIntersecting){
                e.target.classList.add('visible');
                e.target.classList.remove('fade-up');
                obsFade.unobserve(e.target);
            }
        });
    }, {threshold: 0.08, rootMargin: '0px 0px -30px 0px'});

    document.querySelectorAll('.fade-up').forEach(function(el){ el.classList.add('observe-fade'); obsFade.observe(el); });

    var obsImg = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(e.isIntersecting){
                e.target.classList.add('v');
                obsImg.unobserve(e.target);
            }
        });
    }, {threshold: 0.1});

    document.querySelectorAll('.ic').forEach(function(el){ obsImg.observe(el); });
});
</script>
@endpush
