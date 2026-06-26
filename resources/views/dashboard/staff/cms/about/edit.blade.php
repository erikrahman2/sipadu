@extends('layouts.admin')

@section('title', 'Edit Section Tentang')
@section('page-title', 'Edit Section Tentang')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.admin.cms.kelola-konten.about.index') }}" class="text-gray-500 hover:text-gray-700">Section Tentang</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Edit {{ $section->title }}</span>
@endsection

@section('content')
<div class="max-w-3xl">
  <a href="{{ route('dashboard.admin.cms.kelola-konten.about.index') }}" class="text-sm text-primary hover:underline mb-4 inline-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
  </a>

  <form method="POST" action="{{ route('dashboard.admin.cms.kelola-konten.about.update', $section) }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">
    @csrf @method('PUT')

    <div class="p-4 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kunci Section</label>
        <input type="text" value="{{ $section->section_key }}" disabled
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500 bg-gray-50 font-mono">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-400">*</span></label>
        <input type="text" name="title" value="{{ old('title', $section->title) }}" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Konten <span class="text-red-400">*</span></label>
        <textarea name="content" rows="10" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-y font-mono">{{ old('content', $section->content) }}</textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar</label>
        @if($section->image_path)
        <div class="mb-2">
          <img src="{{ Storage::url($section->image_path) }}" class="h-24 rounded-lg border" alt="Image">
        </div>
        @endif
        <input type="file" name="image_path" accept="image/*"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Urutan Tampil <span class="text-red-400">*</span></label>
          <input type="number" name="display_order" value="{{ old('display_order', $section->display_order) }}" min="0" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }}
              class="w-4 h-4 rounded text-primary focus:ring-primary">
            <span>Aktif & tampil di halaman publik</span>
          </label>
        </div>
      </div>
    </div>

    <div class="px-4 py-4 bg-gray-50 flex items-center justify-end gap-3">
      <a href="{{ route('dashboard.admin.cms.kelola-konten.about.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Batal</a>
      <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
        Simpan Perubahan
      </button>
    </div>
  </form>
</div>
@endsection
