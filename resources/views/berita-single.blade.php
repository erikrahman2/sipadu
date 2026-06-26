@extends('layouts.public')

@section('title', $post->title . ' - SiPadu')

@section('content')
{{-- Hero Banner for Single Post --}}
<section class="relative bg-[#0D1F08] text-[#F7F4EB] py-16 px-4 sm:px-6 lg:px-8 overflow-hidden">
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-white/5 blur-[120px]"></div>
  </div>
  <div class="max-w-4xl mx-auto relative">
    {{-- Back Button --}}
    <div class="mb-6">
      <a href="{{ route('berita') }}"
        class="inline-flex items-center gap-2 text-sm text-[#FFF0C4]/70 hover:text-[#FFF0C4] transition-colors">
        <i class="fas fa-arrow-left text-xs"></i> Kembali ke Berita
      </a>
    </div>

    {{-- Category Tag --}}
    @if($post->category_name)
    <span class="inline-block px-3 py-1 bg-white/10 text-white text-[10px] font-semibold uppercase tracking-wider rounded-lg mb-4">
      {{ $post->category_name }}
    </span>
    @endif

    {{-- Title --}}
    <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold tracking-tight leading-[1.2]">
      {{ $post->title }}
    </h1>

    {{-- Metadata --}}
    <div class="mt-5 flex flex-wrap items-center gap-4 text-sm text-[#FFF0C4]/60">
      <span class="flex items-center gap-1.5">
        <i class="far fa-calendar text-xs"></i>
        {{ $post->published_at ? $post->published_at->format('d F Y') : 'Draft' }}
      </span>
      @if($post->author_name)
      <span class="flex items-center gap-1.5">
        <i class="far fa-user text-xs"></i>
        {{ $post->author_name }}
      </span>
      @endif
    </div>
  </div>
</section>

{{-- Main Content --}}
<section class="py-16 px-4 sm:px-6 lg:px-8 bg-[#F7F4EB]">
  <div class="max-w-4xl mx-auto">
    {{-- Featured Image --}}
    @if($post->cover_image)
    <div class="mb-10 rounded-2xl overflow-hidden shadow-lg">
      <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}"
        class="w-full max-h-[480px] object-cover">
    </div>
    @endif

    {{-- Article Body --}}
    <div class="prose prose-lg max-w-none">
      <div class="text-[#31110F]/80 leading-relaxed text-base md:text-[1.05rem]">
        {!! nl2br(e($post->content)) !!}
      </div>
    </div>

    {{-- Tags --}}
    @if(!empty($post->tags))
    <div class="mt-10 pt-8 border-t border-[#31110F]/10">
      <h4 class="text-xs font-semibold text-[#31110F]/40 uppercase tracking-wider mb-3">Tags</h4>
      <div class="flex flex-wrap gap-2">
        @foreach(is_array($post->tags) ? $post->tags : json_decode($post->tags) as $tag)
        <span class="px-3 py-1.5 bg-brand/5 text-brand text-sm rounded-lg">
          {{ is_string($tag) ? $tag : (isset($tag->name) ? $tag->name : $tag) }}
        </span>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Share Section --}}
    <div class="mt-10 pt-8 border-t border-[#31110F]/10">
      <h4 class="text-xs font-semibold text-[#31110F]/40 uppercase tracking-wider mb-3">Bagikan</h4>
      <div class="flex gap-3">
        <button onclick="sharePost('whatsapp')"
          class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-xl hover:bg-green-700 transition-colors">
          <i class="fab fa-whatsapp"></i> WhatsApp
        </button>
        <button onclick="sharePost('facebook')"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700 transition-colors">
          <i class="fab fa-facebook-f"></i> Facebook
        </button>
        <button onclick="copyLink()"
          class="flex items-center gap-2 px-4 py-2 bg-[#31110F]/10 text-[#31110F] text-sm rounded-xl hover:bg-[#31110F]/20 transition-colors">
          <i class="fas fa-link"></i> Salin Link
        </button>
      </div>
    </div>
  </div>
</section>

{{-- Related Posts --}}
@php
  $relatedPosts = \App\Models\BlogPost::published()
    ->where('slug', '!=', $post->slug)
    ->orderBy('published_at', 'desc')
    ->take(3)
    ->get();
@endphp
@if($relatedPosts->isNotEmpty())
<section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
  <div class="max-w-7xl mx-auto">
    <h2 class="text-2xl font-bold text-[#31110F] mb-8">Berita Terkait</h2>
    <div class="grid md:grid-cols-3 gap-6">
      @foreach($relatedPosts as $related)
      <article class="group bg-[#F7F4EB] rounded-xl overflow-hidden hover:shadow-md transition-all">
        @if($related->cover_image)
        <div class="h-40 overflow-hidden">
          <img src="{{ asset('storage/' . $related->cover_image) }}" alt="{{ $related->title }}"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        </div>
        @endif
        <div class="p-5">
          <h3 class="text-sm font-bold text-[#31110F] leading-snug mb-2 group-hover:text-brand transition-colors">
            <a href="{{ route('berita.detail', $related->slug) }}">{{ $related->title }}</a>
          </h3>
          <span class="text-xs text-[#31110F]/40">
            {{ $related->published_at ? $related->published_at->format('d M Y') : '' }}
          </span>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- Load Font Awesome --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

{{-- Share Scripts --}}
<script>
function sharePost(platform) {
  const url = encodeURIComponent(window.location.href);
  const title = encodeURIComponent('{{ $post->title }}');
  let shareUrl = '';

  switch(platform) {
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${title}%20${url}`;
      break;
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
      break;
  }

  if (shareUrl) window.open(shareUrl, '_blank');
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(() => {
    alert('Link berhasil disalin!');
  });
}
</script>
@endsection
