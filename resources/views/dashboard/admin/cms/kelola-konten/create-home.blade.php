@extends('layouts.admin')

@section('title', 'Tambah Section Beranda')
@section('page-title', 'Tambah Section Beranda')

@section('content')
<a href="{{ route('kelola-konten.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <i class="fas fa-arrow-left"></i> Kembali ke Kelola Konten
</a>

<form action="{{ route('kelola-konten.home.store') }}" method="POST" enctype="multipart/form-data"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content Type *</label>
            <select name="content_type"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none font-mono @error('content_type') border-red-400 @enderror">
                <option value="">— Pilih Type —</option>
                <option value="hero"           {{ old('content_type') == 'hero'           ? 'selected' : '' }}>Hero (Headline Utama)</option>
                <option value="home_seo"       {{ old('content_type') == 'home_seo'       ? 'selected' : '' }}>SEO Meta</option>
                <option value="proses_metodologi" {{ old('content_type') == 'proses_metodologi' ? 'selected' : '' }}>Proses Metodologi</option>
                <option value="fitur_unggulan" {{ old('content_type') == 'fitur_unggulan' ? 'selected' : '' }}>Fitur Unggulan</option>
                <option value="statistik"      {{ old('content_type') == 'statistik'      ? 'selected' : '' }}>Statistik</option>
                <option value="blog_header"    {{ old('content_type') == 'blog_header'    ? 'selected' : '' }}>Blog Header</option>
                <option value="cta_footer"     {{ old('content_type') == 'cta_footer'     ? 'selected' : '' }}>CTA Footer</option>
                <option value="testimoni"      {{ old('content_type') == 'testimoni'      ? 'selected' : '' }}>Testimoni</option>
                <option value="cta_layanan"    {{ old('content_type') == 'cta_layanan'    ? 'selected' : '' }}>CTA Layanan</option>
            </select>
            @error('content_type')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-1">Pilih jenis section yang akan dikelola. Tidak boleh duplikat.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
            <input type="number" name="display_order" value="{{ old('display_order', 0) }}" min="0"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" value="{{ old('title') }}" placeholder="Judul section"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none @error('title') border-red-400 @enderror">
            @error('title')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
            <input type="text" name="subtitle" value="{{ old('subtitle') }}" placeholder="Subjudul singkat"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
        <textarea name="content" rows="5"
                  placeholder="Konten utama section..."
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">{{ old('content') }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
            <input type="file" name="image_path" accept="image/jpeg,image/png,image/jpg,image/webp"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
            <p class="text-xs text-gray-400 mt-1">JPEG, PNG, JPG, WebP. Maks 5MB.</p>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CTA Label</label>
                <input type="text" name="cta_label" value="{{ old('cta_label') }}" placeholder="Ajukan Sekarang"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CTA URL</label>
                <input type="text" name="cta_url" value="{{ old('cta_url') }}" placeholder="/pengajuan"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Secondary CTA URL</label>
                <input type="text" name="secondary_cta_url" value="{{ old('secondary_cta_url') }}" placeholder="/layanan"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
                <p class="text-xs text-gray-400 mt-1">Link untuk tombol sekunder (mis. tombol "Layanan").</p>
            </div>
        </div>
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
