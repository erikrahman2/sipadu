@extends('layouts.admin')

@section('title', 'Kelola Homepage')
@section('page-title', 'Kelola Homepage')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-house-chimney mr-2 text-brand"></i>Section Homepage</h1>
    <div class="flex gap-2">
      <a href="{{ route('dashboard.admin.cms.kelola-konten.index') }}"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <a href="{{ route('dashboard.admin.cms.kelola-konten.home.create') }}"
         class="inline-flex items-center gap-2 bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
        <i class="fas fa-plus"></i> Tambah Section
      </a>
    </div>
  </div>

  @if(session('success'))
  <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
    {{ session('success') }}
  </div>
  @endif

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-500 text-left">
          <th class="px-4 py-3 font-medium">#</th>
          <th class="px-4 py-3 font-medium">Key</th>
          <th class="px-4 py-3 font-medium">Title</th>
          <th class="px-4 py-3 font-medium">Subtitle</th>
          <th class="px-4 py-3 font-medium">CTA</th>
          <th class="px-4 py-3 font-medium">Status</th>
          <th class="px-4 py-3 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($homeSections as $section)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 text-gray-500">{{ $section->display_order }}</td>
          <td class="px-4 py-3 font-mono text-xs text-brand">{{ $section->section_key }}</td>
          <td class="px-4 py-3 font-medium text-gray-800">{{ $section->title }}</td>
          <td class="px-4 py-3 text-gray-600">{{ Str::limit($section->subtitle, 30) ?: '-' }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $section->cta_label ?: '-' }}</td>
          <td class="px-4 py-3">
            @if($section->is_active)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
            @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
            @endif
          </td>
          <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-1">
              <a href="{{ route('dashboard.admin.cms.kelola-konten.home.edit', $section->id) }}"
                 class="p-2 rounded-lg text-blue-500 hover:bg-blue-50 transition" title="Edit">
                <i class="fas fa-pen text-xs"></i>
              </a>
              <form action="{{ route('dashboard.admin.cms.kelola-konten.home.destroy', $section->id) }}"
                    method="POST" class="inline"
                    onsubmit="return confirm('Yakin hapus section ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 rounded-lg text-red-400 hover:bg-red-50 transition" title="Hapus">
                  <i class="fas fa-trash text-xs"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="text-center py-10 text-gray-400">
            <i class="fas fa-inbox text-3xl mb-2 block"></i>
            Belum ada section homepage.
            <a href="{{ route('dashboard.admin.cms.kelola-konten.home.create') }}" class="text-brand hover:underline">Tambah section sekarang</a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $homeSections->links() }}
  </div>
</div>
@endsection
