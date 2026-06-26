@extends('layouts.admin')

@section('title', 'Tambah Berita')
@section('page-title', 'Tambah Berita')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.admin.cms.blog.index') }}" class="text-gray-500 hover:text-gray-700">Kelola Berita</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Tambah Berita</span>
@endsection

@section('content')
<div class="max-w-3xl">
  <a href="{{ route('dashboard.admin.cms.blog.index') }}" class="text-sm text-primary hover:underline mb-4 inline-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
  </a>

  <form method="POST" action="{{ route('dashboard.admin.cms.blog.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">
    @csrf

    {{-- Judul --}}
    <div class="p-4 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Berita <span class="text-red-400">*</span></label>
        <input type="text" name="title" value="{{ old('title') }}" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
          placeholder="Masukkan judul berita">
        @error('title') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
        <input type="text" name="slug" value="{{ old('slug') }}"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500 bg-gray-50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
          placeholder="Auto-generate jika kosong">
        @error('slug') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ringkasan (Excerpt)</label>
        <textarea name="excerpt" rows="2"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-none"
          placeholder="Tampilan singkat di halaman publik">{{ old('excerpt') }}</textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Konten <span class="text-red-400">*</span></label>
        <textarea name="content" rows="10" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-y font-mono"
          placeholder="Tulis konten berita (supports HTML)">{{ old('content') }}</textarea>
        @error('content') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penulis</label>
        <input type="text" name="author_name" value="{{ old('author_name') }}"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
          placeholder="Nama tampil di halaman publik">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Cover</label>
        <input type="file" name="cover_image" accept="image/*"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        @error('cover_image') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-400">*</span></label>
        <select name="status" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <option value="DRAFT" {{ old('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
          <option value="PUBLISHED" {{ old('status') === 'PUBLISHED' ? 'selected' : '' }}>Published</option>
          <option value="ARCHIVED" {{ old('status') === 'ARCHIVED' ? 'selected' : '' }}>Archived</option>
        </select>
        @error('status') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="px-4 py-4 bg-gray-50 flex items-center justify-end gap-3">
      <a href="{{ route('dashboard.admin.cms.blog.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Batal</a>
      <button type="submit" class="px-4 py-2 bg-stone-700 text-white text-sm font-medium rounded-lg hover:bg-stone-800 transition">
        Simpan
      </button>
    </div>
  </form>
</div>
@endsection
