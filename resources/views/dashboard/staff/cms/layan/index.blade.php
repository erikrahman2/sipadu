@extends('layouts.admin')

@section('title', 'Section Layanan')
@section('page-title', 'Section Layanan')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-stone-800 font-medium">Section Layanan</span>
@endsection

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-display text-darktext"><i class="fas fa-concierge-bell mr-2 text-primary"></i>Section Layanan</h1>
    <a href="{{ route('dashboard.admin.cms.layan.create') }}" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
      <i class="fas fa-plus"></i> Tambah Layanan
    </a>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-stone-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-stone-50 text-stone-500 text-left border-b border-stone-200">
            <th class="px-5 py-3 font-medium">Urut</th>
            <th class="px-5 py-3 font-medium">Icon</th>
            <th class="px-5 py-3 font-medium">Nama</th>
            <th class="px-5 py-3 font-medium">Deskripsi</th>
            <th class="px-5 py-3 font-medium">Status</th>
            <th class="px-5 py-3 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($layans as $l)
          <tr class="border-b border-stone-100 hover:bg-stone-50 transition">
            <td class="px-5 py-3 text-stone-500">{{ $l->urutan }}</td>
            <td class="px-5 py-3 font-mono text-xs text-stone-600">{{ $l->icon ?? '-' }}</td>
            <td class="px-5 py-3 font-medium text-darktext">{{ $l->nama }}</td>
            <td class="px-5 py-3 text-stone-600 max-w-xs truncate">{{ $l->deskripsi ?? '-' }}</td>
            <td class="px-5 py-3">
              @if($l->aktif)
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
              @else
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600">Non-aktif</span>
              @endif
            </td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <a href="{{ route('dashboard.admin.cms.layan.edit', $l) }}" class="p-2 rounded-lg text-stone-600 hover:bg-stone-100 hover:text-stone-800 transition" title="Edit">
                  <i class="fas fa-pen text-xs"></i>
                </a>
                <form action="{{ route('dashboard.admin.cms.layan.destroy', $l) }}" method="POST" class="inline" onsubmit="return confirm('Hapus layanan ini?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="p-2 rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600 transition" title="Hapus">
                    <i class="fas fa-trash text-xs"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center py-12 text-stone-400">
            <i class="fas fa-inbox text-3xl mb-2 block opacity-50"></i>Belum ada layanan. <a href="{{ route('dashboard.admin.cms.layan.create') }}" class="text-primary font-medium hover:underline">Tambah layanan</a>.
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="px-5 py-4 border-t border-stone-200">
      @if($layans->hasPages())
      <div class="flex justify-center">{{ $layans->links() }}</div>
      @endif
    </div>
  </div>
</div>
@endsection
