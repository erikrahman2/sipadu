@extends('layouts.admin')

@section('title', 'Edit Section Tentang')
@section('page-title', 'Edit Section Tentang')

@section('content')
<a href="{{ route('kelola-konten.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <i class="fas fa-arrow-left"></i> Kembali ke Kelola Konten
</a>

<form action="{{ route('kelola-konten.about.update', $section->id) }}" method="POST" enctype="multipart/form-data"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
    @csrf
    @method('PATCH')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content Type</label>
            <input type="text" value="{{ $section->content_type }}" disabled
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-500 bg-gray-50 font-mono outline-none">
            <input type="hidden" name="content_type" value="{{ old('content_type', $section->content_type) }}">
            @error('content_type')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
            <input type="number" name="display_order" value="{{ old('display_order', $section->display_order) }}" min="0"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
        <input type="text" name="title" value="{{ old('title', $section->title) }}"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none @error('title') border-red-400 @enderror">
        @error('title')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Content (HTML)</label>
        <textarea name="content" rows="6"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">{{ old('content', $section->content) }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
        @if($section->image_path)
            <div class="mb-3">
                <img src="{{ Storage::url($section->image_path) }}" alt="Current"
                     class="h-24 rounded-xl border border-gray-200 object-cover">
                <p class="text-xs text-gray-400 mt-1">{{ $section->image_path }}</p>
            </div>
        @endif
        <input type="file" name="image_path" accept="image/jpeg,image/png,image/jpg,image/webp"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        <p class="text-xs text-gray-400 mt-1">Upload baru untuk mengganti gambar saat ini.</p>
    </div>

    <div class="flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }}
                   class="sr-only peer">
            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
            <span class="ms-3 text-sm font-medium text-gray-700">Aktif</span>
        </label>
    </div>

    <div class="flex gap-3 pt-4 border-t border-gray-100">
        <button type="submit" class="px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary/90 transition shadow-sm">
            <i class="fas fa-save mr-1"></i> Perbarui
        </button>
        <a href="{{ route('kelola-konten.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
            Batal
        </a>
    </div>
</form>
@endsection
