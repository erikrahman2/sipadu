@extends('layouts.admin')

@section('title', $editing ? 'Edit Section Beranda' : 'Tambah Section Beranda')
@section('page-title', $editing ? 'Edit Section Beranda' : 'Tambah Section Beranda')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.admin.cms.kelola-konten.index') }}" class="text-gray-500 hover:text-gray-700">Kelola Konten</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">{{ $editing ? 'Edit' : 'Tambah Section' }}</span>
@endsection

@section('content')
<div class="max-w-3xl">
  <a href="{{ route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'beranda']) }}" class="text-sm text-primary hover:underline mb-4 inline-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
  </a>

  <form method="POST" action="{{ $editing ? route('dashboard.admin.cms.kelola-konten.home.update', $section) : route('dashboard.admin.cms.kelola-konten.home.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">
    @csrf
    @if($editing) @method('PATCH') @endif

    <div class="p-4 space-y-4">
      {{-- Content Type --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Konten <span class="text-red-400">*</span></label>
        <input type="text" name="content_type" value="{{ old('content_type', $section->content_type ?? '') }}" {{ $editing ? 'readonly disabled class="bg-gray-100"' : 'required' }}
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none {{ $editing ? 'bg-gray-100' : '' }}"
          placeholder="hero, statistik, fitur_unggulan, dll.">
        <p class="text-xs text-gray-400 mt-1">Identifikator unik untuk section ini. Tidak bisa diubah saat edit.</p>
        @error('content_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-400">*</span></label>
        <input type="text" name="title" value="{{ old('title', $editing ? $section->title : '') }}" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        @error('title') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sub-judul</label>
        <input type="text" name="subtitle" value="{{ old('subtitle', $editing ? $section->subtitle : '') }}"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Konten</label>
        <textarea name="content" rows="6"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-y font-mono"
          placeholder="HTML didukung">{{ old('content', $editing ? $section->content : '') }}</textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar</label>
        @if($editing && $section->image_path)
        <div class="mb-2">
          <img src="{{ Storage::url($section->image_path) }}" class="h-24 rounded-lg border" alt="Image">
        </div>
        @endif
        <input type="file" name="image_path" accept="image/*"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Label Tombol (CTA)</label>
          <input type="text" name="cta_label" value="{{ old('cta_label', $editing ? $section->cta_label : '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
            placeholder="Baca Selengkapnya">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">URL Tombol (CTA)</label>
          <input type="text" name="cta_url" value="{{ old('cta_url', $editing ? $section->cta_url : '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
            placeholder="/pengajuan">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">URL Tombol Kedua</label>
        <input type="text" name="secondary_cta_url" value="{{ old('secondary_cta_url', $editing ? $section->secondary_cta_url : '') }}"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
          placeholder="/informasi">
        <p class="text-xs text-gray-400 mt-1">Link untuk tombol kedua (misalnya "Layanan")</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Urutan Tampil <span class="text-red-400">*</span></label>
          <input type="number" name="display_order" value="{{ old('display_order', $editing ? $section->display_order : 0) }}" min="0" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $editing ? $section->is_active : true) ? 'checked' : '' }}
              class="w-4 h-4 rounded text-primary focus:ring-primary">
            <span>Aktif & tampil di halaman publik</span>
          </label>
        </div>
      </div>
    </div>

    <div class="px-4 py-4 bg-gray-50 flex items-center justify-end gap-3">
      <a href="{{ route('dashboard.admin.cms.kelola-konten.index', ['tab' => 'beranda']) }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Batal</a>
      <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
        {{ $editing ? 'Simpan Perubahan' : 'Simpan' }}
      </button>
    </div>
  </form>
</div>
@endsection
