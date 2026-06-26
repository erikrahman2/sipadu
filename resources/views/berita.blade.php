@extends('layouts.public')

@section('title', 'Berita & Pengumuman - SiPadu')

@push('styles')
<style>
  .fade-up {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s cubic-bezier(.22,.61,.36,1),
                transform 0.7s cubic-bezier(.22,.61,.36,1);
  }
  .fade-up.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .fade-up-delay-1 { transition-delay: 0.1s; }
  .fade-up-delay-2 { transition-delay: 0.2s; }
  .fade-up-delay-3 { transition-delay: 0.3s; }
  .observe-fade {
    will-change: opacity, transform;
  }
</style>
@endpush

@section('content')
{{-- Hero Banner --}}
<section class="relative bg-[#0D1F08] text-[#F7F4EB] py-16 px-4 sm:px-6 lg:px-8 overflow-hidden">
  {{-- Decorative gradient blurs --}}
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-white/5 blur-[120px]"></div>
    <div class="absolute -bottom-32 -left-32 w-[400px] h-[400px] rounded-full bg-[#FFF0C4]/5 blur-[100px]"></div>
  </div>
  <div class="max-w-7xl mx-auto relative">
    <div class="max-w-3xl">
      <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight leading-[1.15] observe-fade fade-up">
        Berita <span class="italic font-light">&amp;</span> Pengumuman
      </h1>
      <p class="mt-5 text-base/7 text-[#FFF0C4]/80 max-w-xl observe-fade fade-up fade-up-delay-1">
        Informasi terbaru seputar layanan SiPadu, pengumuman penting, dan update sistem pembaruan dokumen pasca perceraian.
      </p>
    </div>

    {{-- Search & Filter Bar --}}
    <div class="mt-8 flex flex-col sm:flex-row gap-3 max-w-2xl observe-fade fade-up">
      <div class="flex-1 relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#31110F]/40">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" placeholder="Cari berita..." id="searchInput"
          class="w-full pl-11 pr-4 py-3 rounded-xl bg-white text-[#31110F] placeholder:text-[#31110F]/30 border-0 focus:ring-2 focus:ring-[#0D1F08]/30 outline-none text-sm">
      </div>
      <div class="relative">
        <select id="categoryFilter"
          class="appearance-none w-full sm:w-52 pl-4 pr-10 py-3 rounded-xl bg-white text-[#31110F] border-0 focus:ring-2 focus:ring-[#0D1F08]/30 outline-none text-sm cursor-pointer">
          <option value="">Semua Kategori</option>
          <option value="Pengumuman">Pengumuman</option>
          <option value="Update Sistem">Update Sistem</option>
          <option value="Informasi">Informasi</option>
          <option value="Regulasi">Regulasi</option>
        </select>
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#31110F]/40 pointer-events-none">
          <i class="fas fa-chevron-down text-xs"></i>
        </span>
      </div>
    </div>
  </div>
</section>

{{-- Blog Posts Grid --}}
<section class="py-16 px-4 sm:px-6 lg:px-8 bg-[#F7F4EB]">
  <div class="max-w-7xl mx-auto">
    @if($posts->isNotEmpty())
    <div class="columns-1 md:columns-2 lg:columns-3 gap-6 space-y-6">
      @foreach($posts as $post)
      <article class="break-inside-avoid group observe-fade fade-up {{ $loop->odd ? 'fade-up-delay-1' : ($loop->index >= 3 ? 'fade-up-delay-2' : '') }}">
        <div class="rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-white">
          {{-- Card Image --}}
          @if($post->cover_image)
          <div class="relative overflow-hidden">
            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}"
              class="w-full object-cover group-hover:scale-105 transition-transform duration-500">
          </div>
          @else
          <div class="w-full h-48 bg-brand/5 flex items-center justify-center">
            <span class="text-brand/20 text-5xl"><i class="fas fa-newspaper"></i></span>
          </div>
          @endif

          {{-- Card Content --}}
          <div class="p-5">
            {{-- Meta row --}}
            <div class="flex items-center gap-3 mb-3">
              @if($post->category_name)
              <span class="px-2.5 py-1 bg-brand text-white text-[10px] font-semibold uppercase tracking-wider rounded-lg">
                {{ $post->category_name }}
              </span>
              @endif
              <span class="text-xs text-[#31110F]/50">
                {{ $post->published_at ? $post->published_at->format('d M Y') : 'Draft' }}
              </span>
            </div>

            {{-- Title --}}
            <h3 class="text-base font-bold text-[#31110F] leading-snug mb-2 group-hover:text-brand transition-colors">
              <a href="{{ route('berita.detail', $post->slug) }}">{{ $post->title }}</a>
            </h3>

            {{-- Excerpt --}}
            @if($post->excerpt)
            <p class="text-sm text-[#31110F]/60 leading-relaxed line-clamp-3">{{ Str::limit($post->excerpt, 150) }}</p>
            @endif

            {{-- Read more --}}
            <div class="mt-4">
              <a href="{{ route('berita.detail', $post->slug) }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:gap-2.5 transition-all">
                Baca Selengkapnya <i class="fas fa-arrow-right text-xs"></i>
              </a>
            </div>
          </div>
        </div>
      </article>
      @endforeach
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
    <div class="mt-12 flex justify-center">
      {{ $posts->links() }}
    </div>
    @endif

    @else
    {{-- Empty State --}}
    <div class="text-center py-20 observe-fade fade-up">
      <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-brand/5 flex items-center justify-center">
        <i class="fas fa-newspaper text-3xl text-brand/20"></i>
      </div>
      <h3 class="text-xl font-bold text-[#31110F] mb-2">Belum Ada Berita</h3>
      <p class="text-[#31110F]/50 text-sm max-w-md mx-auto">
        Saat ini belum ada berita yang dipublikasikan. Periksa kembali nanti atau hubungi administrator untuk informasi lebih lanjut.
      </p>
    </div>
    @endif
  </div>
</section>

{{-- Load Font Awesome --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

{{-- Search & Filter Script --}}
<script>
(function() {
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const articles = document.querySelectorAll('article');

  function filterArticles() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;

    articles.forEach(article => {
      const title = article.querySelector('h3')?.textContent.toLowerCase() || '';
      const excerpt = article.querySelector('p')?.textContent.toLowerCase() || '';
      const category = article.querySelector('span[class*="bg-brand"]')?.textContent.trim() || '';

      const matchesSearch = !searchTerm || title.includes(searchTerm) || excerpt.includes(searchTerm);
      const matchesCategory = !selectedCategory || category.includes(selectedCategory);

      article.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterArticles);
  if (categoryFilter) categoryFilter.addEventListener('change', filterArticles);
})();
</script>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var els = document.querySelectorAll('.observe-fade.fade-up');
    if (!els.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -40px 0px'
    });

    els.forEach(function (el) {
      observer.observe(el);
    });
  });
</script>
@endpush
@endsection
