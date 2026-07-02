@extends('layouts.admin')

@section('title', 'Kelola Konten')
@section('page-title', 'Kelola Konten')

@section('breadcrumb')
  <span class="text-gray-600">Kelola Konten</span>
@endsection

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-darktext"><i class="fas fa-layer-group mr-2 text-primary"></i>Kelola Konten</h1>
      <p class="text-sm text-earth-muted mt-1">Pilih modul konten untuk dikelola</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
    {{-- Kiri: Gambar --}}
    <div>
      <img src="{{ asset('assets/asset(1).jpg') }}" alt="Kelola Konten" class="w-full object-cover rounded-2xl" style="aspect-ratio: 4/3" />
    </div>
    {{-- Kanan: Menu List --}}
    <div class="divide-y divide-gray-200">

      {{-- Homepage --}}
      <a href="{{ route('dashboard.admin.cms.home.index') }}" class="block py-5">
        <div class="flex items-start gap-4">
          <div class="flex-grow min-w-0">
            <span class="text-xs text-earth-muted uppercase tracking-wider">Homepage</span>
            <p class="text-sm text-earth-muted mt-1">Kelola section halaman beranda website</p>
          </div>
          <i class="fas fa-chevron-right text-gray-300 flex-shrink-0 mt-1"></i>
        </div>
      </a>

      {{-- Tentang --}}
      <a href="{{ route('dashboard.admin.cms.about.index') }}" class="block py-5">
        <div class="flex items-start gap-4">
          <div class="flex-grow min-w-0">
            <span class="text-xs text-earth-muted uppercase tracking-wider">Tentang</span>
            <p class="text-sm text-earth-muted mt-1">Kelola halaman informasi tentang instansi</p>
          </div>
          <i class="fas fa-chevron-right text-gray-300 flex-shrink-0 mt-1"></i>
        </div>
      </a>

      {{-- Berita --}}
      <a href="{{ route('dashboard.admin.cms.blog.index') }}" class="block py-5">
        <div class="flex items-start gap-4">
          <div class="flex-grow min-w-0">
            <span class="text-xs text-earth-muted uppercase tracking-wider">Berita</span>
            <p class="text-sm text-earth-muted mt-1">Kelola artikel dan postingan berita</p>
          </div>
          <i class="fas fa-chevron-right text-gray-300 flex-shrink-0 mt-1"></i>
        </div>
      </a>

      {{-- Layanan --}}
      <a href="{{ route('dashboard.admin.cms.layan.index') }}" class="block py-5">
        <div class="flex items-start gap-4">
          <div class="flex-grow min-w-0">
            <span class="text-xs text-earth-muted uppercase tracking-wider">Layanan</span>
            <p class="text-sm text-earth-muted mt-1">Kelola daftar layanan pengadilan</p>
          </div>
          <i class="fas fa-chevron-right text-gray-300 flex-shrink-0 mt-1"></i>
        </div>
      </a>

    </div>
  </div>
</div>
@endsection
