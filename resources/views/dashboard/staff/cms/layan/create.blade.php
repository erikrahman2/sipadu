@extends('layouts.admin')

@section('title', 'Tambah Layanan')
@section('page-title', 'Tambah Layanan')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.admin.cms.layan.index') }}" class="text-stone-500 hover:text-stone-700">Layanan</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-stone-800 font-medium">Tambah</span>
@endsection

@section('content')
<div class="max-w-3xl">
  <a href="{{ route('dashboard.admin.cms.layan.index') }}" class="text-sm text-primary hover:underline mb-4 inline-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
  </a>

  <form method="POST" action="{{ route('dashboard.admin.cms.layan.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-stone-200 overflow-hidden">
    @csrf

    <div class="p-5 space-y-5">
      <div>
        <label class="block text-sm font-medium text-darktext mb-1.5">Nama Layanan <span class="text-red-500">*</span></label>
        <input type="text" name="nama" value="{{ old('nama') }}" required
          class="w-full border border-stone-200 rounded-lg px-3.5 py-2.5 text-sm text-darktext placeholder-stone-400 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
          placeholder="contoh: Layanan Pendaftaran Online">
        @error('nama') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-darktext mb-1.5">Deskripsi</label>
        <textarea name="deskripsi" rows="5"
          class="w-full border border-stone-200 rounded-lg px-3.5 py-2.5 text-sm text-darktext placeholder-stone-400 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-y"
          placeholder="Penjelasan singkat tentang layanan">{{ old('deskripsi') }}</textarea>
        @error('deskripsi') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-darktext mb-1.5">Icon <span class="text-xs text-stone-400">(Font Awesome)</span></label>
        <input type="text" name="icon" value="{{ old('icon') }}"
          class="w-full border border-stone-200 rounded-lg px-3.5 py-2.5 text-sm font-mono text-darktext placeholder-stone-400 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none"
          placeholder="fas fa-concierge-bell">
        <p class="text-xs text-stone-400 mt-1.5">Masukkan class Font Awesome, misal: <code class="bg-stone-100 px-1.5 py-0.5 rounded">fas fa-file-alt</code></p>
        @error('icon') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-darktext mb-1.5">Urutan Tampil</label>
        <input type="number" name="urutan" value="{{ old('urutan', 0) }}" min="0"
          class="w-full border border-stone-200 rounded-lg px-3.5 py-2.5 text-sm text-darktext placeholder-stone-400 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        <p class="text-xs text-stone-400 mt-1.5">Kosongkan untuk otomatis diurutkan terakhir</p>
        @error('urutan') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="px-5 py-4 bg-stone-50 border-t border-stone-200 flex items-center justify-end gap-3">
      <a href="{{ route('dashboard.admin.cms.layan.index') }}" class="px-4 py-2 text-sm text-stone-600 hover:text-stone-800 transition">Batal</a>
      <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
        Simpan
      </button>
    </div>
  </form>
</div>
@endsection
