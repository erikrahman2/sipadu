@extends('layouts.admin')

@section('title', $post->exists ? 'Edit Berita' : 'Tulis Berita')
@section('page-title', $post->exists ? 'Edit Berita' : 'Tulis Berita')

@section('breadcrumb')
  <a href="{{ route('staff.kelola-blog.index') }}" class="hover:text-gray-600">Kelola Berita</a>
  <i class="fas fa-chevron-right text-[10px]"></i>
  <span class="text-gray-800 font-medium">{{ $post->exists ? 'Edit' : 'Baru' }}</span>
@endsection

@section('content')
<div class="space-y-4 max-w-4xl">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-bold text-gray-800">
        {{ $post->exists ? 'Edit Berita' : 'Tulis Berita Baru' }}
      </h2>
      <p class="text-xs text-gray-500 mt-0.5">Publikasi artikel untuk halaman publik.</p>
    </div>
    <a href="{{ route('staff.kelola-blog.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
      <i class="fas fa-arrow-left mr-1"></i>Kembali
    </a>
  </div>

  {{-- Error --}}
  @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
      <p class="font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Terjadi kesalahan:</p>
      <ul class="list-disc pl-5 space-y-1">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ $post->exists ? route('staff.kelola-blog.update', $post->id) : route('staff.kelola-blog.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if($post->exists)
      @method('PUT')
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">

      {{-- Judul --}}
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Judul <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $post->title) }}" required
               class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
               placeholder="Masukkan judul berita...">
      </div>

      {{-- Excerpt --}}
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Ringkasan</label>
        <textarea name="excerpt" rows="2"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
                  placeholder="Ringkasan singkat (maks. 500 karakter)...">{{ old('excerpt', $post->excerpt) }}</textarea>
      </div>

      {{-- Konten --}}
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Konten <span class="text-red-500">*</span></label>
        <textarea name="body" rows="12" required
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none font-mono"
                  placeholder="Tulis konten berita di sini...">{{ old('body', $post->body) }}</textarea>
        <p class="text-[11px] text-gray-400 mt-1">Mendukung teks biasa. Markdown akan di-render di halaman publik.</p>
      </div>

      {{-- Status --}}
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Status Publikasi</label>
        <select name="status" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <option value="DRAFT" {{ old('status', $post->status ?? 'DRAFT') === 'DRAFT' ? 'selected' : '' }}>Draft — tidak ditampilkan di publik</option>
          <option value="PUBLISHED" {{ old('status', $post->status) === 'PUBLISHED' ? 'selected' : '' }}>Published — tampil di publik</option>
          <option value="ARCHIVED" {{ old('status', $post->status) === 'ARCHIVED' ? 'selected' : '' }}>Archived — disembunyikan dari publik</option>
        </select>
      </div>

    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center justify-end gap-3">
      <a href="{{ route('staff.kelola-blog.index') }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition">
        Batal
      </a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary/90 transition shadow-sm">
        <i class="fas fa-save text-xs"></i>
        {{ $post->exists ? 'Simpan Perubahan' : 'Publikasikan' }}
      </button>
    </div>

  </form>

</div>
@endsection
