@extends('layouts.admin')

@section('title', 'Kelola Konten')
@section('page-title', 'Kelola Konten')

@php
    $activeTab = request('tab', 'beranda');
    $homeSections = \App\Models\CmsHomeSection::ordered()->get();
    $aboutSections = \App\Models\CmsAboutSection::ordered()->get();

    // Human-readable labels for home sections
    $homeLabels = [
        'home_seo'            => ['label' => 'Meta & SEO Beranda',      'icon' => 'fa-tags',          'page' => 'beranda'],
        'blog_header'         => ['label' => 'Judul Halaman Berita',    'icon' => 'fa-newspaper',   'page' => 'beranda'],
        'hero'                => ['label' => 'Headline Utama (Hero)',   'icon' => 'fa-home',        'page' => 'beranda'],
        'about_header'        => ['label' => 'Tentang SiPadu',          'icon' => 'fa-info-circle', 'page' => 'beranda'],
        'proses_metodologi'   => ['label' => 'Cara Kerja',              'icon' => 'fa-arrows-spin', 'page' => 'beranda'],
        'fitur_unggulan'      => ['label' => 'Fitur Unggulan',          'icon' => 'fa-sparkles',    'page' => 'beranda'],
        'statistik'           => ['label' => 'Angka & Statistik',       'icon' => 'fa-chart-simple', 'page' => 'beranda'],
        'cta_footer'          => ['label' => 'Ajakan Bertindak (CTA)',  'icon' => 'fa-bullhorn',    'page' => 'beranda'],
    ];

    // Human-readable labels for about sections
    $aboutLabels = [
        'tentang_sipadu'     => ['label' => 'Apa Itu SiPadu?',         'icon' => 'fa-book-open',   'page' => 'tentang'],
        'visi_misi'          => ['label' => 'Visi & Misi',             'icon' => 'fa-eye',         'page' => 'tentang'],
        'fitur_keunggulan'   => ['label' => 'Fitur & Keunggulan',      'icon' => 'fa-gem',         'page' => 'tentang'],
        'institusi_pendukung'=> ['label' => 'Institusi Pendukung',     'icon' => 'fa-building',    'page' => 'tentang'],
    ];
@endphp

@section('content')
<div class="space-y-6">

    {{-- Tab Navigation --}}
    <div class="flex flex-wrap gap-1 bg-gray-100 rounded-xl p-1">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'beranda']) }}"
           class="px-5 py-2.5 rounded-lg text-sm font-medium transition {{ $activeTab === 'beranda' ? 'bg-white shadow text-stone-700' : 'text-stone-600 hover:text-stone-800' }}">
            <i class="fas fa-home mr-1"></i> Beranda
            <span class="bg-gray-200 text-stone-600 text-xs px-1.5 py-0.5 rounded-full ml-1">{{ $homeSections->count() }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'tentang']) }}"
           class="px-5 py-2.5 rounded-lg text-sm font-medium transition {{ $activeTab === 'tentang' ? 'bg-white shadow text-stone-700' : 'text-stone-600 hover:text-stone-800' }}">
            <i class="fas fa-info-circle mr-1"></i> Tentang
            <span class="bg-gray-200 text-stone-600 text-xs px-1.5 py-0.5 rounded-full ml-1">{{ $aboutSections->count() }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'berita']) }}"
           class="px-5 py-2.5 rounded-lg text-sm font-medium transition {{ $activeTab === 'berita' ? 'bg-white shadow text-stone-700' : 'text-stone-600 hover:text-stone-800' }}">
            <i class="fas fa-newspaper mr-1"></i> Berita
            <span class="bg-gray-200 text-stone-600 text-xs px-1.5 py-0.5 rounded-full ml-1">{{ \App\Models\CmsBlogPost::count() }}</span>
        </a>
    </div>

    {{-- ════════════ BERANDA TAB ════════════ --}}
    <div class="{{ $activeTab !== 'beranda' ? 'hidden' : '' }}">

        {{-- Beranda Page Header --}}
        <div class="bg-gradient-to-r from-stone-700 to-stone-800 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">Halaman Beranda</h2>
                    <p class="text-stone-300 text-sm mt-1">Kelola konten halaman utama yang dilihat pengunjung.</p>
                </div>
                <a href="{{ route('kelola-konten.home.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-xl transition border border-white/20">
                    <i class="fas fa-plus text-xs"></i> Tambah Section
                </a>
            </div>
        </div>

        {{-- Section Cards --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($homeSections as $section)
            @php
                $key = $section->section_key;
                $info = $homeLabels[$key] ?? ['label' => $key, 'icon' => 'fa-puzzle-piece', 'page' => 'beranda'];
                $preview = $section->title ?: ($section->subtitle ?: '-');
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition group">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-stone-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $info['icon'] }} text-stone-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-stone-800 truncate">{{ $info['label'] }}</h4>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold
                                {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $section->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $preview }}</p>
                        <div class="flex items-center gap-3 mt-3">
                            <a href="{{ route('kelola-konten.home.edit', $section->id) }}"
                               class="text-xs font-medium text-stone-700 hover:text-stone-900 flex items-center gap-1">
                                <i class="fas fa-pen text-[10px]"></i> Edit
                            </a>
                            <form action="{{ route('kelola-konten.home.destroy', $section->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus section {{ $section->title }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 flex items-center gap-1">
                                    <i class="fas fa-trash text-[10px]"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-xl border border-gray-200 p-8 text-center">
                <i class="fas fa-home text-3xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 text-sm">Belum ada section beranda.</p>
                <a href="{{ route('kelola-konten.home.create') }}" class="text-stone-700 hover:underline text-sm font-medium mt-1 inline-block">Tambah section pertama</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ════════════ TENTANG TAB ════════════ --}}
    <div class="{{ $activeTab !== 'tentang' ? 'hidden' : '' }}">

        {{-- Tentang Page Header --}}
        <div class="bg-gradient-to-r from-stone-700 to-stone-800 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">Halaman Tentang SiPadu</h2>
                    <p class="text-stone-300 text-sm mt-1">Kelola konten halaman "Tentang" yang dilihat pengunjung.</p>
                </div>
                <a href="{{ route('kelola-konten.about.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-xl transition border border-white/20">
                    <i class="fas fa-plus text-xs"></i> Tambah Section
                </a>
            </div>
        </div>

        {{-- Section Cards --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($aboutSections as $section)
            @php
                $key = $section->section_key;
                $info = $aboutLabels[$key] ?? ['label' => $key, 'icon' => 'fa-puzzle-piece', 'page' => 'tentang'];
                $preview = $section->title ?: (strip_tags($section->content ?? '') ?: '-');
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition group">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-stone-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $info['icon'] }} text-stone-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-stone-800 truncate">{{ $info['label'] }}</h4>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold
                                {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $section->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $preview }}</p>
                        <div class="flex items-center gap-3 mt-3">
                            <a href="{{ route('kelola-konten.about.edit', $section->id) }}"
                               class="text-xs font-medium text-stone-700 hover:text-stone-900 flex items-center gap-1">
                                <i class="fas fa-pen text-[10px]"></i> Edit
                            </a>
                            <form action="{{ route('kelola-konten.about.destroy', $section->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus section {{ $section->title }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 flex items-center gap-1">
                                    <i class="fas fa-trash text-[10px]"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-xl border border-gray-200 p-8 text-center">
                <i class="fas fa-info-circle text-3xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 text-sm">Belum ada section tentang.</p>
                <a href="{{ route('kelola-konten.about.create') }}" class="text-stone-700 hover:underline text-sm font-medium mt-1 inline-block">Tambah section pertama</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ════════════ BERITA TAB ════════════ --}}
    <div class="{{ $activeTab !== 'berita' ? 'hidden' : '' }}">

        {{-- Berita Page Header --}}
        <div class="bg-gradient-to-r from-stone-700 to-stone-800 rounded-2xl p-6 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">Halaman Berita</h2>
                    <p class="text-stone-300 text-sm mt-1">Tulis, kelola, dan publikasikan artikel atau pengumuman.</p>
                </div>
                <a href="{{ route('kelola-konten.blog.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-xl transition border border-white/20 w-fit">
                    <i class="fas fa-pen text-xs"></i> Tulis Berita
                </a>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Cari judul atau isi berita..."
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-stone-500/30 focus:border-stone-500 outline-none">
                </div>
                <select name="status" class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-stone-500/30 focus:border-stone-500 outline-none">
                    <option value="">Semua Status</option>
                    <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="PUBLISHED" {{ request('status') === 'PUBLISHED' ? 'selected' : '' }}>Published</option>
                    <option value="ARCHIVED" {{ request('status') === 'ARCHIVED' ? 'selected' : '' }}>Archived</option>
                </select>
                <button type="submit" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
            </form>
        </div>

        {{-- Posts Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                            <th class="px-4 py-3">Judul</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Penulis</th>
                            <th class="px-4 py-3">Dipublikasikan</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $posts = \App\Models\CmsBlogPost::query()
                                ->when(request('q'), fn($q) => $q->where(function($qr) {
                                    $sr = request('q');
                                    $qr->where('title','LIKE',"%$sr%")->orWhere('content','LIKE',"%$sr%")->orWhere('excerpt','LIKE',"%$sr%")->orWhere('author_name','LIKE',"%$sr%");
                                }))
                                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                                ->orderByDesc('id')
                                ->get();
                        @endphp
                        @forelse($posts as $post)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-stone-800">{{ $post->title }}</div>
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
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-stone-600">
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
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('kelola-konten.blog.edit', $post->id) }}" class="text-stone-700 hover:underline text-xs font-medium" title="Edit">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                    <form action="{{ route('kelola-konten.blog.destroy', $post->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus berita ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline text-xs font-medium" title="Hapus">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-gray-400">
                                <i class="fas fa-newspaper text-3xl mb-2 text-gray-300 block"></i>
                                Belum ada berita.
                                <a href="{{ route('kelola-konten.blog.create') }}" class="text-stone-700 hover:underline">Tulis berita</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.flex-wrap > a').forEach(tab => {
        tab.addEventListener('click', e => {
            const url = new URL(e.href);
            history.replaceState(null, '', url);
        });
    });
});
</script>

@endsection
