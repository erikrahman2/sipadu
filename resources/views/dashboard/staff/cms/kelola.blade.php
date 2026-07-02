@extends('layouts.admin')

@section('title', 'Kelola Konten')
@section('page-title', 'Kelola Konten')

@section('breadcrumb')
  <i class="fas fa-file-lines text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Kelola Konten</span>
@endsection

@section('content')
<div class="space-y-6">
  <div>
    <h1 class="text-xl font-bold text-darktext"><i class="fas fa-layer-group mr-2 text-primary"></i>Kelola Konten</h1>
    <p class="text-sm text-earth-muted mt-1">Pilih modul konten untuk dikelola</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <a href="{{ route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'beranda']) }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md hover:border-primary/30 transition-all">
      <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-100 transition-colors">
        <i class="fas fa-house-chimney text-xl text-blue-600 group-hover:scale-110 transition-transform"></i>
      </div>
      <h3 class="font-semibold text-darktext group-hover:text-primary transition-colors">Beranda</h3>
      <p class="text-sm text-earth-muted mt-1">Section halaman utama</p>
    </a>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'tentang']) }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md hover:border-coral/30 transition-all">
      <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4 group-hover:bg-red-100 transition-colors">
        <i class="fas fa-building text-xl text-red-600 group-hover:scale-110 transition-transform"></i>
      </div>
      <h3 class="font-semibold text-darktext group-hover:text-coral transition-colors">Tentang</h3>
      <p class="text-sm text-earth-muted mt-1">Section halaman tentang</p>
    </a>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'layanan']) }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md hover:border-accent/30 transition-all">
      <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-100 transition-colors">
        <i class="fas fa-concierge-bell text-xl text-purple-600 group-hover:scale-110 transition-transform"></i>
      </div>
      <h3 class="font-semibold text-darktext group-hover:text-accent transition-colors">Layanan</h3>
      <p class="text-sm text-earth-muted mt-1">Katalog layanan publik</p>
    </a>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'berita']) }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md hover:border-orange-500/30 transition-all">
      <div class="w-14 h-14 rounded-2xl bg-orange-50 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-100 transition-colors">
        <i class="fas fa-newspaper text-xl text-orange-600 group-hover:scale-110 transition-transform"></i>
      </div>
      <h3 class="font-semibold text-darktext group-hover:text-orange-500 transition-colors">Berita</h3>
      <p class="text-sm text-earth-muted mt-1">Artikel & postingan</p>
    </a>
  </div>
</div>
@endsection
