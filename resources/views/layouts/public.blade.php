<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'SiPadu') - Sistem Pembaruan Dokumen Pasca Perceraian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: { DEFAULT: '#1e40af', light: '#3b82f6', dark: '#1e3a8a' },
              accent: { DEFAULT: '#0891b2', light: '#22d3ee' },
            },
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
          }
        }
      }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        .gradient-hero { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 40%, #0891b2 100%); }
        .gradient-dark { background: linear-gradient(135deg, #1e1b4b 0%, #1e3a8a 100%); }
        .nav-link { @apply text-gray-600 hover:text-brand font-medium transition-colors text-sm; }
        .nav-link-light { @apply text-gray-400 hover:text-white font-medium transition-colors; }
        .section-title { @apply text-3xl md:text-4xl font-bold text-gray-900 mb-4; }
        .section-subtitle { @apply text-gray-600 text-lg leading-relaxed max-w-2xl; }
        .card-hover { @apply transition-all duration-300 hover:shadow-2xl hover:-translate-y-1; }
    </style>
    @stack('styles')
</head>
<body class="font-sans bg-white text-gray-800 antialiased">

<div class="flex flex-col min-h-screen">

  {{-- ═════════════════════════════════════════════════════════════════════
      HEADER
      ═════════════════════════════════════════════════════════════════════ --}}
  <header class="sticky top-0 z-40 bg-white border-b border-gray-100 shadow-sm">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      
      {{-- Logo/Brand --}}
      <a href="{{ route('home') }}" class="flex items-center gap-2 group">
        <div class="w-9 h-9 rounded-lg gradient-hero flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <span class="font-bold text-gray-900 text-lg tracking-tight group-hover:text-brand transition-colors">SiPadu</span>
      </a>

      {{-- Main Navigation - Desktop --}}
      <div class="hidden md:flex items-center gap-8">
        <a href="{{ route('public.submit.create') }}" class="nav-link">
          <i class="fas fa-file-alt mr-1.5"></i> Pengajuan
        </a>
        <a href="{{ route('tentang') }}" class="nav-link">
          <i class="fas fa-info-circle mr-1.5"></i> Tentang
        </a>
        <a href="{{ route('berita') }}" class="nav-link">
          <i class="fas fa-newspaper mr-1.5"></i> Berita
        </a>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center gap-3">
        @auth
          <a href="{{ route('dashboard.index') }}"
            class="px-4 py-2 text-sm font-semibold text-white gradient-hero rounded-lg hover:opacity-90 transition-opacity">
            Dashboard
          </a>
          <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              Keluar
            </button>
          </form>
        @else
          <a href="{{ route('auth.login') }}"
            class="px-4 py-2 text-sm font-semibold text-brand hover:text-brand transition-colors">
            Masuk
          </a>
          <a href="{{ route('auth.login') }}"
            class="px-4 py-2 text-sm font-semibold text-white gradient-hero rounded-lg hover:opacity-90 transition-opacity">
            Daftar
          </a>
        @endauth
      </div>
    </nav>
  </header>

  {{-- ═════════════════════════════════════════════════════════════════════
      MAIN CONTENT
      ═════════════════════════════════════════════════════════════════════ --}}
  <main class="flex-1">
    @yield('content')
  </main>

  {{-- ═════════════════════════════════════════════════════════════════════
      FOOTER
      ═════════════════════════════════════════════════════════════════════ --}}
  <footer class="gradient-dark text-gray-300 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      
      {{-- Footer Grid --}}
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
        
        {{-- About Section --}}
        <div>
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg gradient-hero flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <span class="font-bold text-white text-lg">SiPadu</span>
          </div>
          <p class="text-gray-400 text-sm leading-relaxed">
            Sistem terintegrasi pembaruan dokumen kependudukan pasca perceraian antara Pengadilan Agama dan Dinas Kependudukan.
          </p>
        </div>

        {{-- Layanan --}}
        <div>
          <h4 class="font-semibold text-white mb-4 uppercase text-xs tracking-wider">Layanan</h4>
          <ul class="space-y-3 text-sm">
            <li><a href="{{ route('public.submit.create') }}" class="nav-link-light">Pengajuan Dokumen</a></li>
            <li><a href="{{ route('tracking.public') }}" class="nav-link-light">Lacak Pengajuan</a></li>
            <li><a href="{{ route('auth.login') }}" class="nav-link-light">Masuk Sistem</a></li>
            <li><a href="{{ route('tentang') }}" class="nav-link-light">Tentang Kami</a></li>
          </ul>
        </div>

        {{-- Informasi --}}
        <div>
          <h4 class="font-semibold text-white mb-4 uppercase text-xs tracking-wider">Informasi</h4>
          <ul class="space-y-3 text-sm">
            <li><a href="{{ route('berita') }}" class="nav-link-light">Berita & Pengumuman</a></li>
            <li><a href="#" class="nav-link-light">Panduan Pengguna</a></li>
            <li><a href="#" class="nav-link-light">FAQ</a></li>
            <li><a href="#" class="nav-link-light">Kontak</a></li>
          </ul>
        </div>

        {{-- Legal --}}
        <div>
          <h4 class="font-semibold text-white mb-4 uppercase text-xs tracking-wider">Legal</h4>
          <ul class="space-y-3 text-sm">
            <li><a href="#" class="nav-link-light">Kebijakan Privasi</a></li>
            <li><a href="#" class="nav-link-light">Syarat & Ketentuan</a></li>
            <li><a href="#" class="nav-link-light">Lisensi</a></li>
            <li><a href="#" class="nav-link-light">Hubungi Kami</a></li>
          </ul>
        </div>

      </div>

      {{-- Divider --}}
      <div class="border-t border-gray-700 mb-8"></div>

      {{-- Footer Bottom --}}
      <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="text-sm text-gray-400">
          <p>&copy; 2026 SiPadu. Dikembangkan oleh PA & Disdukcapil.</p>
          <p class="mt-1 text-xs">Sistem Pembaruan Dokumen Pasca Perceraian</p>
        </div>

        {{-- Social Links --}}
        <div class="flex items-center gap-4">
          <a href="#" class="w-10 h-10 rounded-full bg-gray-700 hover:bg-accent transition-colors flex items-center justify-center text-gray-300 hover:text-white">
            <i class="fab fa-facebook-f text-sm"></i>
          </a>
          <a href="#" class="w-10 h-10 rounded-full bg-gray-700 hover:bg-accent transition-colors flex items-center justify-center text-gray-300 hover:text-white">
            <i class="fab fa-twitter text-sm"></i>
          </a>
          <a href="#" class="w-10 h-10 rounded-full bg-gray-700 hover:bg-accent transition-colors flex items-center justify-center text-gray-300 hover:text-white">
            <i class="fab fa-instagram text-sm"></i>
          </a>
          <a href="#" class="w-10 h-10 rounded-full bg-gray-700 hover:bg-accent transition-colors flex items-center justify-center text-gray-300 hover:text-white">
            <i class="fab fa-whatsapp text-sm"></i>
          </a>
        </div>
      </div>

    </div>
  </footer>

</div>

@stack('scripts')
</body>
</html>
