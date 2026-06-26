@extends('layouts.public')

@section('title', 'Beranda - SiPadu')

@section('content')

{{-- Hero Section --}}
<section class="public-dark-bg text-[#F7F4EB] py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-5xl mx-auto text-center">
    @if(!empty($homeSeo['meta_title']))
      <h1 class="text-4xl md:text-5xl font-bold mb-4 text-[#F7F4EB]">{{ $homeSeo['meta_title'] }}</h1>
    @else
      <h1 class="text-4xl md:text-5xl font-bold mb-4 text-[#F7F4EB]">Sistem Pembaruan Dokumen Pasca Perceraian</h1>
    @endif
    @if(!empty($homeSeo['meta_description']))
      <p class="text-[#F7F4EB] text-lg max-w-3xl mx-auto">{{ $homeSeo['meta_description'] }}</p>
    @endif
  </div>
</section>

{{-- Quick Actions --}}
<section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
  <div class="max-w-5xl mx-auto">
    <div class="grid md:grid-cols-3 gap-8">
      <a href="{{ route('public.submit.create') }}" class="group text-center p-8 rounded-xl bg-brand-light/10 hover:bg-brand-light/20 transition-all">
        <div class="w-16 h-16 rounded-full bg-brand-light/20 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
          <i class="fas fa-file-alt text-2xl text-brand-dark"></i>
        </div>
        <h3 class="text-xl font-bold text-[#31110F] mb-2 font-display">Ajukan Pengisian Ulang Data</h3>
        <p class="text-[#31110F] text-sm">Pengajuan pembaruan data kependudukan pasca perceraian</p>
      </a>
      <a href="{{ route('tracking.public') }}" class="group text-center p-8 rounded-xl bg-brand-light/10 hover:bg-brand-light/20 transition-all">
        <div class="w-16 h-16 rounded-full bg-brand-light/20 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
          <i class="fas fa-search text-2xl text-brand-dark"></i>
        </div>
        <h3 class="text-xl font-bold text-[#31110F] mb-2 font-display">Lacak Status Pengajuan</h3>
        <p class="text-[#31110F] text-sm">Cek status pengajuan Anda secara real-time</p>
      </a>
      <a href="{{ route('tentang') }}" class="group text-center p-8 rounded-xl bg-brand-light/10 hover:bg-brand-light/20 transition-all">
        <div class="w-16 h-16 rounded-full bg-brand-light/20 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
          <i class="fas fa-info-circle text-2xl text-brand-dark"></i>
        </div>
        <h3 class="text-xl font-bold text-[#31110F] mb-2 font-display">Pelajari Lebih Lanjut</h3>
        <p class="text-[#31110F] text-sm">Informasi lengkap tentang layanan SiPadu</p>
      </a>
    </div>
  </div>
</section>

{{-- Info Section --}}
<section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
  <div class="max-w-5xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12 items-center">
      <div>
        <h2 class="text-3xl font-bold text-[#31110F] mb-6">Proses Mudah dan Terintegrasi</h2>
        <div class="space-y-4">
          <div class="flex gap-4">
            <div class="w-8 h-8 rounded-full bg-brand text-[#F7F4EB] flex items-center justify-center flex-shrink-0 font-bold">1</div>
            <div>
              <h4 class="font-bold text-[#31110F]">Ajukan Online</h4>
              <p class="text-[#31110F]">Isi formulir dengan NIK dan lampirkan dokumen perceraian</p>
            </div>
          </div>
          <div class="flex gap-4">
            <div class="w-8 h-8 rounded-full bg-brand text-[#F7F4EB] flex items-center justify-center flex-shrink-0 font-bold">2</div>
            <div>
              <h4 class="font-bold text-[#31110F]">Proses Otomatis</h4>
              <p class="text-[#31110F]">Sistem memverifikasi data dan mengkoordinasikan antar-instansi</p>
            </div>
          </div>
          <div class="flex gap-4">
            <div class="w-8 h-8 rounded-full bg-brand text-[#F7F4EB] flex items-center justify-center flex-shrink-0 font-bold">3</div>
            <div>
              <h4 class="font-bold text-[#31110F]">Data Diperbarui</h4>
              <p class="text-[#31110F]">Data kependudukan Anda diperbarui secara otomatis</p>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white p-8 rounded-xl shadow-lg">
        <div class="aspect-square bg-gradient-to-br from-brand-dark/10 to-brand-light/10 rounded-lg flex items-center justify-center">
          <i class="fas fa-cogs text-6xl text-brand"></i>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- Footer CTA --}}
<section class="public-dark-bg text-[#F7F4EB] py-16 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-3xl font-bold mb-4">Siap Memulai Proses?</h2>
    <p class="text-[#F7F4EB] text-lg mb-8">Buat pengajuan Anda sekarang dan dapatkan token tracking via WhatsApp</p>
    <div class="flex flex-wrap gap-4 justify-center">
      <a href="{{ route('public.submit.create') }}" class="px-8 py-4 bg-brand text-white font-bold rounded-lg hover:bg-brand/90 transition inline-flex items-center gap-2">
        Buat Pengajuan <i class="fas fa-arrow-right"></i>
      </a>
      <a href="{{ route('tentang') }}" class="px-8 py-4 border-2 border-[#F7F4EB] text-[#F7F4EB] font-bold rounded-lg hover:bg-white/10 transition inline-flex items-center gap-2">
        Pelajari Lebih Lanjut
      </a>
    </div>
  </div>
</section>

@endsection
