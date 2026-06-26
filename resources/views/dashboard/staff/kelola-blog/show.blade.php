@extends('layouts.admin')

@section('title', $post->title)
@section('page-title', $post->title)

@section('breadcrumb')
  <a href="{{ route('staff.kelola-blog.index') }}" class="hover:text-gray-600">Kelola Berita</a>
  <i class="fas fa-chevron-right text-[10px]"></i>
  <span class="text-gray-800 font-medium">Detail</span>
@endsection

@section('content')
<div class="space-y-4 max-w-4xl">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-bold text-gray-800">{{ $post->title }}</h2>
      <p class="text-xs text-gray-500 mt-0.5">
        Oleh <strong>{{ $post->author?->name ?? $post->author_name ?? '—' }}</strong>
        @if($post->published_at)
          · Dipublikasikan {{ $post->published_at->format('d M Y H:i') }}
        @else
          · <em>Belum dipublikasikan</em>
        @endif
      </p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('staff.kelola-blog.edit', $post->id) }}" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90">
        <i class="fas fa-edit mr-1"></i> Edit
      </a>
      <a href="{{ route('staff.kelola-blog.index') }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
    </div>
  </div>

  {{-- Status badge --}}
  <div>
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
  </div>

  {{-- Konten --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    @if($post->excerpt)
      <p class="text-sm italic text-gray-500 mb-4 pb-4 border-b">{{ $post->excerpt }}</p>
    @endif
    <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line leading-relaxed">{{ $post->body }}</div>
  </div>

  {{-- Meta --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-xs text-gray-500 flex flex-wrap gap-x-6 gap-y-1">
    <div><strong>Slug:</strong> <code class="bg-gray-50 px-1.5 py-0.5 rounded">{{ $post->slug }}</code></div>
    <div><strong>Dibuat:</strong> {{ $post->created_at->format('d M Y H:i') }}</div>
    @if($post->updated_at && $post->updated_at != $post->created_at)
      <div><strong>Diubah:</strong> {{ $post->updated_at->format('d M Y H:i') }}</div>
    @endif
  </div>

</div>
@endsection
