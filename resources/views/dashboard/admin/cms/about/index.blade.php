@extends('layouts.admin')

@section('title', 'Tentang - Kelola Konten')
@section('page-title', 'Kelola Konten')

@php
    // Human-readable labels for about section keys
    $labels = [
        'tentang_sipadu'     => ['label' => 'Apa Itu SiPadu?',     'desc' => 'Penjelasan Singkat tentang platform', 'icon' => 'fa-book-open'],
        'visi_misi'          => ['label' => 'Visi & Misi',         'desc' => 'Tujuan dan arah platform', 'icon' => 'fa-eye'],
        'fitur_keunggulan'   => ['label' => 'Fitur & Keunggulan',  'desc' => 'Fitur unggulan platform', 'icon' => 'fa-gem'],
        'institusi_pendukung'=> ['label' => 'Institusi Pendukung', 'desc' => 'Instansi yang berpartisipasi', 'icon' => 'fa-building'],
    ];
@endphp

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-info-circle mr-2 text-primary"></i>Tentang SiPadu
      </h1>
      <p class="text-sm text-gray-500 mt-1">Kelola konten halaman "Tentang" yang dilihat pengunjung.</p>
    </div>
    <a href="{{ route('dashboard.admin.cms.about.create') }}" class="inline-flex items-center gap-2 bg-stone-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-stone-800 transition">
      <i class="fas fa-plus"></i> Tambah Section
    </a>
  </div>

  @forelse($sections as $section)
  @php
      $key = $section->section_key;
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
        @if($section->is_active)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
        @else
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Non-aktif</span>
        @endif
        <a href="{{ route('dashboard.admin.cms.about.edit', $section) }}" class="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50 transition" title="Edit">
          <i class="fas fa-pen text-xs"></i>
        </a>
        @php $confirmText = 'Hapus section ' . $info['label'] . '?'; @endphp
        <form action="{{ route('dashboard.admin.cms.about.destroy', $section) }}" method="POST" class="inline"
              onsubmit="return confirm({{ Js::from($confirmText) }})">
          @csrf @method('DELETE')
          <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 transition" title="Hapus">
            <i class="fas fa-trash text-xs"></i>
          </button>
        </form>
      </div>
    </div>
    <div class="p-5 text-sm">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <span class="text-gray-500 font-medium">Subtitle:</span>
          <p class="text-gray-700 mt-1">{{ $section->subtitle ?: '-' }}</p>
        </div>
        <div>
          <span class="text-gray-500 font-medium">Urut:</span>
          <p class="text-gray-700 mt-1">{{ $section->display_order }}</p>
        </div>
      </div>
      @if($section->content)
      <div class="mt-3">
        <span class="text-gray-500 font-medium">Konten:</span>
        <div class="text-gray-700 mt-1 prose prose-sm max-w-none">{!! str($section->content)->limit(200) !!}</div>
      </div>
      @endif
    </div>
  </div>
  @empty
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-400">
    <i class="fas fa-inbox text-3xl mb-3 block"></i>
    Belum ada section. <a href="{{ route('dashboard.admin.cms.about.create') }}" class="text-primary hover:underline">Tambah section</a>.
  </div>
  @endforelse
</div>
@endsection
