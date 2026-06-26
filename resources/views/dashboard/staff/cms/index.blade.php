@extends('layouts.admin')

@section('title', 'Kelola Konten')
@section('page-title', 'Kelola Konten')

@section('breadcrumb')
  <span class="text-gray-600">Kelola Konten</span>
@endsection

@section('content')
<div class="space-y-6">
  <div>
    <h1 class="text-xl font-bold text-darktext"><i class="fas fa-layer-group mr-2 text-primary"></i>Kelola Konten</h1>
    <p class="text-sm text-earth-muted mt-1">Pilih modul konten untuk dikelola</p>
  </div>

  {{-- ── Card Grid ──────────────────────────────────────────────── --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- ── Homepage ─────────────────────────────────────────────── --}}
    <a href="{{ route('dashboard.admin.cms.home.index') }}"
       class="group bg-white rounded-2xl shadow-sm border border-cream overflow-hidden hover:shadow-lg hover:border-primary/30 transition-all duration-200">
      <div class="bg-gradient-to-br from-primary to-brand-dark h-28 flex items-center justify-center">
        <i class="fas fa-house-chimney text-white text-4xl group-hover:scale-110 transition-transform duration-200"></i>
      </div>
      <div class="p-5">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-bold text-lg text-darktext">Homepage</h3>
        </div>
        <p class="text-sm text-earth-muted mb-3">Kelola section halaman beranda website</p>
        <div class="px-5 py-3 bg-earth-bg border-t border-cream group-hover:bg-primary/5 transition-colors">
          <span class="text-sm font-medium text-primary group-hover:text-primary-dark">
            Kelola Homepage <i class="fas fa-arrow-right ml-1 text-xs group-hover:translate-x-1 transition-transform"></i>
          </span>
        </div>
      </div>
    </a>

    {{-- ── Tentang ─────────────────────────────────────────────── --}}
    <a href="{{ route('dashboard.admin.cms.about.index') }}"
       class="group bg-white rounded-2xl shadow-sm border border-cream overflow-hidden hover:shadow-lg hover:border-coral/30 transition-all duration-200">
      <div class="bg-gradient-to-br from-coral to-coral-dark h-28 flex items-center justify-center">
        <i class="fas fa-info-circle text-white text-4xl group-hover:scale-110 transition-transform duration-200"></i>
      </div>
      <div class="p-5">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-bold text-lg text-darktext">Tentang</h3>
        </div>
        <p class="text-sm text-earth-muted mb-3">Kelola halaman informasi tentang instansi</p>
        <div class="px-5 py-3 bg-earth-bg border-t border-cream group-hover:bg-coral/5 transition-colors">
          <span class="text-sm font-medium text-coral group-hover:text-coral-dark">
            Kelola Tentang <i class="fas fa-arrow-right ml-1 text-xs group-hover:translate-x-1 transition-transform"></i>
          </span>
        </div>
      </div>
    </a>

    {{-- ── Berita ─────────────────────────────────────────────── --}}
    <a href="{{ route('dashboard.admin.cms.blog.index') }}"
       class="group bg-white rounded-2xl shadow-sm border border-cream overflow-hidden hover:shadow-lg hover:border-accent/30 transition-all duration-200">
      <div class="bg-gradient-to-br from-accent to-accent-dark h-28 flex items-center justify-center">
        <i class="fas fa-newspaper text-white text-4xl group-hover:scale-110 transition-transform duration-200"></i>
      </div>
      <div class="p-5">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-bold text-lg text-darktext">Berita</h3>
        </div>
        <p class="text-sm text-earth-muted mb-3">Kelola artikel dan postingan berita</p>
        <div class="px-5 py-3 bg-earth-bg border-t border-cream group-hover:bg-accent/5 transition-colors">
          <span class="text-sm font-medium text-accent group-hover:text-accent-dark">
            Kelola Berita <i class="fas fa-arrow-right ml-1 text-xs group-hover:translate-x-1 transition-transform"></i>
          </span>
        </div>
      </div>
    </a>

  </div>
</div>
@endsection
