@extends('layouts.admin')

@section('title', 'Section Tentang')
@section('page-title', 'Section Tentang')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Section Tentang</span>
@endsection

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-display text-gray-800"><i class="fas fa-info-circle mr-2 text-primary"></i>Section Halaman Tentang</h1>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.about.create') }}" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
      <i class="fas fa-plus"></i> Tambah Section
    </a>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-500 text-left">
            <th class="px-4 py-3 font-medium">Order</th>
            <th class="px-4 py-3 font-medium">Key</th>
            <th class="px-4 py-3 font-medium">Judul</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($sections as $section)
          <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 text-gray-500">{{ $section->display_order }}</td>
            <td class="px-4 py-3 font-mono text-xs text-primary">{{ $section->section_key }}</td>
            <td class="px-4 py-3 font-medium text-gray-800">{{ $section->title }}</td>
            <td class="px-4 py-3">
              @if($section->is_active)
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
              @else
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Non-aktif</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <a href="{{ route('dashboard.admin.cms.kelola-konten.about.edit', $section) }}" class="p-2 rounded-lg text-blue-500 hover:bg-blue-50 transition" title="Edit">
                  <i class="fas fa-pen text-xs"></i>
                </a>
                <form action="{{ route('dashboard.admin.cms.kelola-konten.about.destroy', $section) }}" method="POST" class="inline" onsubmit="return confirm('Hapus section ini?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="p-2 rounded-lg text-red-400 hover:bg-red-50 transition" title="Hapus">
                    <i class="fas fa-trash text-xs"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center py-8 text-gray-400">
            <i class="fas fa-inbox text-3xl mb-2 block"></i>Belum ada section. <a href="{{ route('dashboard.admin.cms.kelola-konten.about.create') }}" class="text-primary hover:underline">Tambah section</a>.
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
