@extends('layouts.admin')

@section('title', 'Kelola Konten – Satu Pintu')
@section('page-title', 'Kelola Konten')

@section('breadcrumb')
  <i class="fas fa-file-lines text-primary"></i>
  <span class="text-primary font-medium">Dashboard</span>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Kelola Konten</span>
@endsection

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-darktext"><i class="fas fa-layer-group mr-2 text-primary"></i>Kelola Konten</h1>
      <p class="text-sm text-earth-muted mt-1">Kelola seluruh konten halaman publik dalam satu tempat.</p>
    </div>
  </div>

  {{-- Tabs Navigation --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="border-b border-gray-100">
      <nav class="flex -mb-px" aria-label="Tabs">
        @foreach(['beranda' => 'Beranda', 'tentang' => 'Tentang', 'layanan' => 'Layanan', 'berita' => 'Berita'] as $tab => $label)
          <button onclick="switchTab('{{ $tab }}')"
                  class="cms-tab px-5 py-3.5 text-sm font-medium border-b-2 transition text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 {{ $activeTab === $tab ? 'text-primary border-primary' : '' }}"
                  data-tab="{{ $tab }}">
            {{ $label }}
          </button>
        @endforeach
      </nav>
    </div>

    {{-- Tab Content --}}
    <div class="p-6">

      {{-- ========== BERANDA TAB ========== --}}
      <div class="cms-tab-content {{ $activeTab !== 'beranda' ? 'hidden' : '' }}" id="tab-beranda">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-darktext">Halaman Beranda</h2>
          <a href="{{ route('dashboard.admin.cms.kelola-konten.home.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus text-xs"></i> Tambah Section
          </a>
        </div>
        <p class="text-sm text-earth-muted mb-4">Section yang tampil di halaman beranda publik.</p>
        @if($homeSections->isEmpty())
          <div class="text-center py-12">
            <i class="fas fa-home text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500">Belum ada section beranda. Klik "Tambah Section" untuk mulai.</p>
          </div>
        @else
          <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                  <th class="px-4 py-3">Judul</th>
                  <th class="px-4 py-3">Tipe</th>
                  <th class="px-4 py-3">Urutan</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @foreach($homeSections as $s)
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-darktext">{{ Str::limit($s->title, 45) }}</td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                      {{ $s->content_type === 'metodologi' ? 'bg-purple-50 text-purple-700' :
                        ($s->content_type === 'stats' ? 'bg-green-50 text-green-700' :
                        ($s->content_type === 'blog_header' ? 'bg-yellow-50 text-yellow-700' :
                        ($s->content_type === 'seo' ? 'bg-indigo-50 text-indigo-700' :
                        'bg-blue-50 text-blue-700'))) }}">
                      {{ ucfirst(str_replace('_', ' ', $s->content_type ?? 'umum')) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-600">#{{ $s->display_order }}</td>
                  <td class="px-4 py-3">
                    @if($s->is_active)
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                        <i class="fas fa-check-circle"></i> Aktif
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                        <i class="fas fa-minus-circle"></i> Nonaktif
                      </span>
                    @endif
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <a href="{{ route('dashboard.admin.cms.kelola-konten.home.edit', $s->id) }}"
                         class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                        <i class="fas fa-pen-to-square"></i>
                      </a>
                      <form action="{{ route('dashboard.admin.cms.kelola-konten.home.destroy', $s->id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Hapus section ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Hapus">
                          <i class="fas fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            {{ $homeSections->appends(['tab' => 'beranda'])->links() }}
          </div>
        @endif
      </div>

      {{-- ========== TENTANG TAB ========== --}}
      <div class="cms-tab-content {{ $activeTab !== 'tentang' ? 'hidden' : '' }}" id="tab-tentang">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-darktext">Halaman Tentang</h2>
          <a href="{{ route('dashboard.admin.cms.kelola-konten.about.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus text-xs"></i> Tambah Section
          </a>
        </div>
        <p class="text-sm text-earth-muted mb-4">Section yang tampil di halaman tentang publik.</p>
        @if($aboutSections->isEmpty())
          <div class="text-center py-12">
            <i class="fas fa-building text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500">Belum ada section tentang. Klik "Tambah Section" untuk mulai.</p>
          </div>
        @else
          <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                  <th class="px-4 py-3">Judul</th>
                  <th class="px-4 py-3">Urutan</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @foreach($aboutSections as $s)
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-darktext">{{ Str::limit($s->title, 45) }}</td>
                  <td class="px-4 py-3 text-gray-600">#{{ $s->display_order }}</td>
                  <td class="px-4 py-3">
                    @if($s->is_active)
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                        <i class="fas fa-check-circle"></i> Aktif
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                        <i class="fas fa-minus-circle"></i> Nonaktif
                      </span>
                    @endif
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <a href="{{ route('dashboard.admin.cms.kelola-konten.about.edit', $s->id) }}"
                         class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                        <i class="fas fa-pen-to-square"></i>
                      </a>
                      <form action="{{ route('dashboard.admin.cms.kelola-konten.about.destroy', $s->id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Hapus section ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Hapus">
                          <i class="fas fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            {{ $aboutSections->appends(['tab' => 'tentang'])->links() }}
          </div>
        @endif
      </div>

      {{-- ========== LAYANAN TAB ========== --}}
      <div class="cms-tab-content {{ $activeTab !== 'layanan' ? 'hidden' : '' }}" id="tab-layanan">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-darktext">Halaman Layanan</h2>
          <a href="{{ route('dashboard.admin.cms.layan.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus text-xs"></i> Tambah Layanan
          </a>
        </div>
        <p class="text-sm text-earth-muted mb-4">Katalog layanan publik yang ditampilkan di halaman /layanan.</p>
        @if($layans->isEmpty())
          <div class="text-center py-12">
            <i class="fas fa-concierge-bell text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500">Belum ada layanan. Klik "Tambah Layanan" untuk mulai.</p>
          </div>
        @else
          <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                  <th class="px-4 py-3">Nama Layanan</th>
                  <th class="px-4 py-3">Kategori</th>
                  <th class="px-4 py-3">Icon</th>
                  <th class="px-4 py-3">Urutan</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @foreach($layans as $l)
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-darktext">{{ Str::limit($l->nama, 40) }}</td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                      {{ $l->kategori === 'pencatatan_sipil' ? 'bg-blue-50 text-blue-700' :
                        ($l->kategori === 'pembaruan_dokumen' ? 'bg-purple-50 text-purple-700' :
                        ($l->kategori === 'perkawinan' ? 'bg-pink-50 text-pink-700' :
                        ($l->kategori === 'identitas_digital' ? 'bg-teal-50 text-teal-700' :
                        'bg-gray-50 text-gray-700'))) }}">
                      {{ ucfirst(str_replace('_', ' ', $l->kategori)) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $l->icon }}</td>
                  <td class="px-4 py-3 text-gray-600">#{{ $l->urutan }}</td>
                  <td class="px-4 py-3">
                    @if($l->aktif)
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                        <i class="fas fa-check-circle"></i> Aktif
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                        <i class="fas fa-minus-circle"></i> Nonaktif
                      </span>
                    @endif
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <a href="{{ route('dashboard.admin.cms.layan.edit', $l->id) }}"
                         class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                        <i class="fas fa-pen-to-square"></i>
                      </a>
                      <form action="{{ route('dashboard.admin.cms.layan.destroy', $l->id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Hapus layanan ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Hapus">
                          <i class="fas fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            {{ $layans->appends(['tab' => 'layanan'])->links() }}
          </div>
        @endif
      </div>

      {{-- ========== BERITA TAB ========== --}}
      <div class="cms-tab-content {{ $activeTab !== 'berita' ? 'hidden' : '' }}" id="tab-berita">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-darktext">Halaman Berita</h2>
          <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus text-xs"></i> Tambah Berita
          </a>
        </div>
        <p class="text-sm text-earth-muted mb-4">Artikel dan postingan berita untuk halaman publik.</p>
        @if($blogPosts->isEmpty())
          <div class="text-center py-12">
            <i class="fas fa-newspaper text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500">Belum ada berita. Klik "Tambah Berita" untuk mulai.</p>
          </div>
        @else
          <div class="overflow-x-auto rounded-lg border border-gray-100">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                  <th class="px-4 py-3">Judul</th>
                  <th class="px-4 py-3">Penulis</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Tanggal</th>
                  <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                @foreach($blogPosts as $post)
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-darktext max-w-xs">
                    {{ Str::limit($post->judul, 45) }}
                  </td>
                  <td class="px-4 py-3 text-gray-600">
                    {{ $post->author ? $post->author->name : '—' }}
                  </td>
                  <td class="px-4 py-3">
                    @if($post->status === 'PUBLISHED')
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                        <i class="fas fa-check-circle"></i> Terbit
                      </span>
                    @elseif($post->status === 'DRAFT')
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-yellow-700">
                        <i class="fas fa-edit"></i> Draft
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                        <i class="fas fa-archive"></i> Arsip
                      </span>
                    @endif
                  </td>
                  <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $post->published_at ? $post->published_at->format('d M Y') : '—' }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.edit', $post->id) }}"
                         class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                        <i class="fas fa-pen-to-square"></i>
                      </a>
                      <form action="{{ route('dashboard.admin.cms.kelola-konten.blog.destroy', $post->id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Hapus berita ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Hapus">
                          <i class="fas fa-trash-can"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            {{ $blogPosts->withQueryString()->links() }}
          </div>
        @endif
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab) {
  document.querySelectorAll('.cms-tab').forEach(btn => {
    btn.classList.remove('text-primary', 'border-primary');
    btn.classList.add('text-gray-500', 'border-transparent');
  });
  document.querySelectorAll('.cms-tab-content').forEach(content => {
    content.classList.add('hidden');
  });
  document.querySelector(`[data-tab="${tab}"]`).classList.add('text-primary', 'border-primary');
  document.querySelector(`[data-tab="${tab}"]`).classList.remove('text-gray-500', 'border-transparent');
  document.getElementById('tab-' + tab).classList.remove('hidden');
  // Update URL hash
  history.replaceState(null, '', '?tab=' + tab);
}

// Restore tab from URL hash on load
document.addEventListener('DOMContentLoaded', () => {
  const hash = new URLSearchParams(window.location.search).get('tab');
  if (hash && ['beranda','tentang','layanan','berita'].includes(hash)) {
    switchTab(hash);
  }
});
</script>
@endpush
