@extends('layouts.admin')

@section('title', 'Beranda - Kelola Konten')
@section('page-title', 'Kelola Konten')

@php
    // Human-readable labels for home section keys
    $labels = [
        'home_seo'            => ['label' => 'Meta & SEO Beranda',   'desc' => 'Judul, deskripsi untuk mesin pencari', 'icon' => 'fa-tags'],
        'blog_header'         => ['label' => 'Judul Halaman Berita',  'desc' => 'Judul dan deskripsi halaman berita publik', 'icon' => 'fa-newspaper'],
        'hero'                => ['label' => 'Headline Utama',        'desc' => 'Judul besar di bagian atas halaman beranda', 'icon' => 'fa-home'],
        'about_header'        => ['label' => 'Tentang SiPadu',        'desc' => 'Penjelasan singkat apa itu SiPadu', 'icon' => 'fa-info-circle'],
        'proses_metodologi'   => ['label' => 'Cara Kerja',            'desc' => 'Langkah-langkah proses layanan', 'icon' => 'fa-arrows-spin'],
        'fitur_unggulan'      => ['label' => 'Fitur Unggulan',        'desc' => 'Daftar fitur yang ditawarkan', 'icon' => 'fa-sparkles'],
        'statistik'           => ['label' => 'Angka & Statistik',     'desc' => 'Data statistik ditampilkan di beranda', 'icon' => 'fa-chart-simple'],
        'cta_footer'          => ['label' => 'Ajakan Bertindak (CTA)','desc' => 'Tombol ajakan di bagian bawah beranda', 'icon' => 'fa-bullhorn'],
    ];
@endphp

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-paint-brush mr-2 text-primary"></i>Beranda
      </h1>
      <p class="text-sm text-gray-500 mt-1">Kelola konten halaman utama yang dilihat pengunjung.</p>
    </div>
    <a href="{{ route('dashboard.admin.cms.home.create') }}"
       class="inline-flex items-center gap-2 bg-stone-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-stone-800 transition">
      <i class="fas fa-plus"></i> Tambah Section
    </a>
  </div>

  @forelse($sections as $home)
  @php
      $key = $home->section_key;
      $info = $labels[$key] ?? ['label' => $key, 'desc' => 'Section kustom', 'icon' => 'fa-puzzle-piece'];
  @endphp
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-stone-100 flex items-center justify-center flex-shrink-0">
          <i class="fas {{ $info['icon'] }} text-stone-600 text-sm"></i>
        </div>
        <div>
          <h2 class="text-base font-bold text-gray-800">{{ $info['label'] }}</h2>
          <p class="text-xs text-gray-500">{{ $info['desc'] }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        @if($home->is_active)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
        @else
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Non-aktif</span>
        @endif
        <a href="{{ route('dashboard.admin.cms.home.edit', $home) }}"
           class="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50 transition" title="Edit">
          <i class="fas fa-pen text-xs"></i>
        </a>
        @php $confirmText = 'Hapus section ' . $info['label'] . '?'; @endphp
        <form action="{{ route('dashboard.admin.cms.home.destroy', $home) }}" method="POST" class="inline"
              onsubmit="return confirm({{ Js::from($confirmText) }})">
          @csrf @method('DELETE')
          <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 transition" title="Hapus">
            <i class="fas fa-trash text-xs"></i>
          </button>
        </form>
      </div>
    </div>
    <div class="p-5">
      <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div>
          <span class="text-gray-500 font-medium">Subtitle:</span>
          <p class="text-gray-700 mt-1">{{ $home->subtitle ?: '-' }}</p>
        </div>
        <div>
          <span class="text-gray-500 font-medium">CTA:</span>
          <p class="text-gray-700 mt-1">{{ $home->cta_label ?: '-' }}</p>
        </div>
        @if($home->image_path)
        <div class="md:col-span-2">
          <span class="text-gray-500 font-medium">Gambar:</span>
          <div class="mt-1">
            <img src="{{ Storage::url($home->image_path) }}" alt="{{ $home->title }}" class="h-20 rounded-lg object-cover">
          </div>
        </div>
        @endif
      </div>
      @if($home->content)
      <div class="mt-3">
        <span class="text-gray-500 font-medium">Konten:</span>
        <div class="text-gray-700 mt-1 prose prose-sm max-w-none">{!! str($home->content)->limit(200) !!}</div>
      </div>
      @endif
    </div>
  </div>
  @empty
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-400">
    <i class="fas fa-inbox text-3xl mb-3 block"></i>
    Belum ada konten. <a href="{{ route('dashboard.admin.cms.home.create') }}" class="text-primary hover:underline">Tambah section</a>.
  </div>
  @endforelse
</div>
@endsection
