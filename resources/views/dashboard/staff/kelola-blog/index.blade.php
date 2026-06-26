@extends('layouts.admin')

@section('title', 'Kelola Berita & Blog')
@section('page-title', 'Kelola Berita & Blog')

@section('breadcrumb')
  <span class="text-gray-600">CMS</span>
  <i class="fas fa-chevron-right text-[10px]"></i>
  <span class="text-gray-800 font-medium">Berita</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-gray-800">Kelola Berita & Blog</h2>
      <p class="text-sm text-gray-500 mt-1">Publikasi berita dan artikel untuk halaman publik.</p>
    </div>
    <a href="{{ route('staff.kelola-blog.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary/90 transition shadow-sm">
      <i class="fas fa-plus text-xs"></i>
      Tulis Berita
    </a>
  </div>

  {{-- Statistik --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    @php
      $statColors = ['all' => 'slate', 'DRAFT' => 'gray', 'PUBLISHED' => 'green', 'ARCHIVED' => 'yellow'];
      $statData = [
        'all' => ($stats['all'] ?? 0),
        'DRAFT' => ($stats['DRAFT'] ?? 0),
        'PUBLISHED' => ($stats['PUBLISHED'] ?? 0),
        'ARCHIVED' => ($stats['ARCHIVED'] ?? 0),
      ];
    @endphp
    @foreach($statData as $key => $value)
      @php $color = $statColors[$key]; @endphp
      <a href="{{ request()->fullUrlWithQuery(['status' => $key === 'all' ? null : $key]) }}"
         class="bg-white border border-gray-100 rounded-2xl px-4 py-3 text-center hover:shadow-sm transition
                {{ (request('status') === $key || ($key === 'all' && !request('status'))) ? 'ring-2 ring-'.$color.'-400' : '' }}">
        <div class="text-2xl font-bold text-{{ $color }}-600">{{ $value }}</div>
        <div class="text-xs text-gray-500 mt-0.5">
          {{ $key === 'all' ? 'Total' : ucfirst(strtolower($key)) }}
        </div>
      </a>
    @endforeach
  </div>

  {{-- Filter & Pencarian --}}
  <form method="GET" class="flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <input type="text" name="q" value="{{ request('q') }}"
             placeholder="Cari judul atau isi berita..."
             class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
    </div>
    <button type="submit" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition">
      <i class="fas fa-search mr-1"></i>Cari
    </button>
  </form>

  {{-- Tabel --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
          <th class="px-4 py-3">Judul</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Penulis</th>
          <th class="px-4 py-3">Dipublikasikan</th>
          <th class="px-4 py-3">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($posts as $post)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 max-w-md">
            <a href="{{ route('staff.kelola-blog.show', $post->id) }}" class="font-medium text-gray-800 hover:text-primary line-clamp-1">
              {{ $post->title }}
            </a>
            @if($post->excerpt)
              <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $post->excerpt }}</p>
            @endif
          </td>
          <td class="px-4 py-3">
            @if($post->status === 'PUBLISHED')
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700">
                <i class="fas fa-circle text-[6px]"></i> Published
              </span>
            @elseif($post->status === 'DRAFT')
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600">
                <i class="fas fa-pen text-[6px]"></i> Draft
              </span>
            @else
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-yellow-100 text-yellow-700">
                <i class="fas fa-archive text-[6px]"></i> Archived
              </span>
            @endif
          </td>
          <td class="px-4 py-3 text-xs text-gray-500">
            {{ $post->author?->name ?? $post->author_name ?? '—' }}
          </td>
          <td class="px-4 py-3 text-xs text-gray-500">
            {{ $post->published_at ? $post->published_at->format('d/m/Y H:i') : '—' }}
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <a href="{{ route('staff.kelola-blog.edit', $post->id) }}" class="text-primary hover:underline text-xs font-medium">
                <i class="fas fa-edit"></i> Edit
              </a>
              <form action="{{ route('staff.kelola-blog.destroy', $post->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus berita ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-500 hover:underline text-xs font-medium">
                  <i class="fas fa-trash"></i> Hapus
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="text-center py-12 text-gray-400">
            <i class="fas fa-newspaper text-3xl mb-2 text-gray-300"></i>
            <p>Belum ada berita.</p>
            <a href="{{ route('staff.kelola-blog.create') }}" class="text-primary hover:underline text-xs mt-2 inline-block">
              Tulis berita pertama <i class="fas fa-arrow-right ml-1"></i>
            </a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
    @if(method_exists($posts, 'links'))
      <div class="px-4 py-3 border-t">{{ $posts->links() }}</div>
    @endif
  </div>

</div>
@endsection
