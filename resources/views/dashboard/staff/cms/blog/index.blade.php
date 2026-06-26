@extends('layouts.admin')

@section('title', 'Kelola Berita')
@section('page-title', 'Kelola Berita')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Kelola Berita</span>
@endsection

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-display text-gray-800"><i class="fas fa-newspaper mr-2 text-primary"></i>Kelola Berita</h1>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.create') }}" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
      <i class="fas fa-plus"></i> Tambah Berita
    </a>
  </div>

  {{-- Summary Cards --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.index') }}" class="bg-white border border-gray-100 rounded-xl p-4 hover:border-primary/30 transition">
      <p class="text-xs text-gray-500">Total</p>
      <p class="text-2xl font-bold text-gray-800 mt-1">{{ $counts['all'] }}</p>
    </a>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.index', ['status' => 'DRAFT']) }}" class="bg-white border border-gray-100 rounded-xl p-4 hover:border-amber-300 transition">
      <p class="text-xs text-gray-500">Draft</p>
      <p class="text-2xl font-bold text-amber-600 mt-1">{{ $counts['draft'] }}</p>
    </a>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.index', ['status' => 'PUBLISHED']) }}" class="bg-white border border-gray-100 rounded-xl p-4 hover:border-green-300 transition">
      <p class="text-xs text-gray-500">Published</p>
      <p class="text-2xl font-bold text-green-600 mt-1">{{ $counts['published'] }}</p>
    </a>
    <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.index', ['status' => 'ARCHIVED']) }}" class="bg-white border border-gray-100 rounded-xl p-4 hover:border-gray-300 transition">
      <p class="text-xs text-gray-500">Archived</p>
      <p class="text-2xl font-bold text-gray-600 mt-1">{{ $counts['archived'] }}</p>
    </a>
  </div>

  {{-- Table --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-500 text-left">
            <th class="px-4 py-3 font-medium">Judul</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Penulis</th>
            <th class="px-4 py-3 font-medium">Dipublikasi</th>
            <th class="px-4 py-3 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($posts as $post)
          <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-800">{{ Str::limit($post->title, 50) }}</p>
              <p class="text-xs text-gray-400">{{ Str::limit($post->excerpt, 80) }}</p>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                @if($post->status === 'PUBLISHED') bg-green-100 text-green-700
                @elseif($post->status === 'DRAFT') bg-amber-100 text-amber-700
                @else bg-gray-100 text-gray-600 @endif">
                {{ $post->status }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">
              {{ $post->author?->name ?? $post->author_name ?? '-' }}
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">
              {{ $post->published_at ? $post->published_at->format('d/m/Y H:i') : '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.edit', $post) }}" class="p-2 rounded-lg text-blue-500 hover:bg-blue-50 transition" title="Edit">
                  <i class="fas fa-pen text-xs"></i>
                </a>
                <form action="{{ route('dashboard.admin.cms.kelola-konten.blog.destroy', $post) }}" method="POST" class="inline" onsubmit="return confirm('Hapus berita ini?')">
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
            <i class="fas fa-inbox text-3xl mb-2 block"></i>Belum ada berita.
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $posts->links() }}</div>
  </div>
</div>
@endsection
