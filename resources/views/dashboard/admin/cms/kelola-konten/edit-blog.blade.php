@extends('layouts.admin')

@section('title', 'Edit Berita')
@section('page-title', 'Edit Berita')

@section('content')
<a href="{{ route('kelola-konten.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <i class="fas fa-arrow-left"></i> Kembali ke Kelola Konten
</a>

<form action="{{ route('kelola-konten.blog.update', $post->id) }}" method="POST" enctype="multipart/form-data"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
    @csrf
    @method('PATCH')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Berita *</label>
        <input type="text" name="title" value="{{ old('title', $post->title) }}"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none @error('title') border-red-400 @enderror">
        @error('title')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $post->slug) }}"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none font-mono">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
        <textarea name="excerpt" rows="2"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">{{ old('excerpt', $post->excerpt) }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Konten *</label>
        <textarea name="content" rows="10"
                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none @error('content') border-red-400 @enderror">{{ old('content', $post->content) }}</textarea>
        @error('content')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
            @if($post->cover_image)
                <div class="mb-3">
                    <img src="{{ Storage::url($post->cover_image) }}" alt="Current"
                         class="h-24 rounded-xl border border-gray-200 object-cover">
                </div>
            @endif
            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/jpg,image/webp"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Author Name</label>
            <input type="text" name="author_name" value="{{ old('author_name', $post->author_name) }}"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
                <option value="DRAFT" {{ old('status', $post->status) === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                <option value="PUBLISHED" {{ old('status', $post->status) === 'PUBLISHED' ? 'selected' : '' }}>Published</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Published At</label>
        <input type="datetime-local" name="published_at"
               value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
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
