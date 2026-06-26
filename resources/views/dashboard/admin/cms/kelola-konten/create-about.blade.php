@extends('layouts.admin')

@section('title', 'Tambah Section Tentang')
@section('page-title', 'Tambah Section Tentang')

@section('content')
<a href="{{ route('kelola-konten.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <i class="fas fa-arrow-left"></i> Kembali ke Kelola Konten
</a>

<form action="{{ route('kelola-konten.about.store') }}" method="POST" enctype="multipart/form-data"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Key *</label>
            <input type="text" name="section_key" value="{{ old('section_key') }}" placeholder="hero, visi_misi, fitur"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none font-mono @error('section_key') border-red-400 @enderror">
            @error('section_key')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
            <input type="number" name="display_order" value="{{ old('display_order', 0) }}" min="0"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
        <input type="text" name="title" value="{{ old('title') }}" placeholder="Judul section"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none @error('title') border-red-400 @enderror">
        @error('title')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Content (HTML)</label>
        <textarea name="content" rows="6"
                  placeholder="Konten HTML section tentang..."
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">{{ old('content') }}</textarea>
        <p class="text-xs text-gray-400 mt-1">Dukung HTML tag dasar: &lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;li&gt;, dll.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
        <input type="file" name="image_path" accept="image/jpeg,image/png,image/jpg,image/webp"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        <p class="text-xs text-gray-400 mt-1">JPEG, PNG, JPG, WebP. Maks 5MB.</p>
    </div>

    <div class="flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                   class="sr-only peer">
            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
            <span class="ms-3 text-sm font-medium text-gray-700">Aktif</span>
        </label>
    </div>

    <div class="flex gap-3 pt-4 border-t border-gray-100">
        <button type="submit" class="px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary/90 transition shadow-sm">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
        <a href="{{ route('kelola-konten.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
            Batal
        </a>
    </div>
</form>
@endsection
