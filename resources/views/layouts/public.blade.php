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
              // Halden Miller-inspired palette
              cream:    '#F7F4EB',   // warm off-white / page bg
              brown:    '#31110F',   // near-black body text
              brand:    '#0D1F08',   // deep green primary
              'brand-lt': '#1a3d14',
              'brand-dk': '#051006',
              accent:   '#0891b2',   // teal accent
              'accent-lt':'#22d3ee',
              coral:    '#D4633C',   // warm coral CTA
              'coral-dk':'#B8502F',
              'green-sm':'#86A77C',  // soft sage green
              'green-lt':'#E6EDDF',
              footer:   '#C7BDA1',   // beige footer
            },
            fontFamily: {
              sans: ['Inter', 'system-ui', 'sans-serif'],
              serif: ['Georgia', 'Cambria', 'serif'],
            },
          }
        }
      }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-serif { font-family: Georgia, Cambria, serif; }

        /* Section divider lines */
        .section-line {
            border-top: 1px solid rgba(49,17,15,0.12);
        }

        /* Trust logo grid */
        .trust-logo {
            filter: grayscale(1) opacity(0.5);
            transition: filter 0.3s;
        }
        .trust-logo:hover { filter: none; }

        /* Editorial image mask */
        .img-mask-round {
            border-radius: 50%;
            overflow: hidden;
        }
        .img-mask-rect {
            border-radius: 0;
        }

        /* Smooth scroll */
        html { scroll-behavior: smooth; }

        /* Subtle hover lift */
        .card-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(49,17,15,0.08);
        }
    </style>
    @stack('styles')
</head>
<body class="font-sans bg-cream text-brown antialiased">

<div class="flex flex-col min-h-screen">

  {{-- ═══════════════════════════════════════════════════════════════════════════
      HEADER — clean, minimal, editorial
      ═══════════════════════════════════════════════════════════════════════════ --}}
  <header class="sticky top-0 z-40 bg-cream/95 backdrop-blur-sm border-b border-brown/10">
    <div class="max-w-7xl mx-auto h-16 flex items-center">

      {{-- Brand --}}
      <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0 pl-4 sm:pl-6 lg:pl-8">
        <img src="{{ asset('assets/logo white.png') }}" alt="SiPadu" class="h-10" />
      </a>

      {{-- Spacing --}}
      <div class="flex-1"></div>

      {{-- Hamburger bar --}}
      <button id="menuToggle" type="button" class="flex items-center justify-center h-full transition hover:bg-[#70B5AE] text-white pr-4 sm:pr-6 lg:pr-8" style="background:#7FC3BC; width: 4rem;" aria-label="Menu" aria-expanded="false">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path id="menuIcon" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

    </div>
  </header>

  {{-- Mobile Nav Overlay --}}
  <div id="mobileNav" class="fixed inset-0 z-50 bg-cream/98 backdrop-blur-md flex flex-col overflow-hidden" style="transform: translateX(100%); transition: transform 0.3s ease; visibility: hidden;">

    {{-- Top Bar --}}
    <div class="flex items-center justify-between px-4 sm:px-6 h-16 shrink-0">
      <span class="text-brown text-lg font-bold tracking-tight">Menu</span>
      <button type="button" id="menuClose" class="w-10 h-10 flex items-center justify-center text-brown/70 hover:text-brown transition-shrink-0 rounded" aria-label="Tutup">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- Links --}}
    <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6">
      <nav class="flex flex-col gap-2 max-w-sm mx-auto">
        <a href="{{ route('services') }}" class="block px-4 py-3 text-lg font-medium text-brown/80 hover:text-brown hover:bg-brown/5 rounded-lg transition nav-desktop-link {{ request()->routeIs('services') ? '!text-brand bg-brand/5 font-semibold' : '' }}">Layanan</a>
        <a href="{{ route('tentang') }}" class="block px-4 py-3 text-lg font-medium text-brown/80 hover:text-brown hover:bg-brown/5 rounded-lg transition nav-desktop-link {{ request()->routeIs('tentang') ? '!text-brand bg-brand/5 font-semibold' : '' }}">Tentang</a>
        <a href="{{ route('berita') }}" class="block px-4 py-3 text-lg font-medium text-brown/80 hover:text-brown hover:bg-brown/5 rounded-lg transition nav-desktop-link {{ request()->routeIs('berita') ? '!text-brand bg-brand/5 font-semibold' : '' }}">Berita</a>
        <a href="{{ route('tracking.public') }}" class="block px-4 py-3 text-lg font-medium text-brown/80 hover:text-brown hover:bg-brown/5 rounded-lg transition">Lacak Pengajuan</a>
      </nav>
    </div>

    {{-- Auth Buttons --}}
    <div class="px-4 sm:px-6 pb-10 pt-4 shrink-0">
      <div class="flex flex-col gap-3 max-w-sm mx-auto">
        @auth
          <a href="{{ route('dashboard.index') }}" class="block w-full text-center px-6 py-3 text-sm font-semibold text-cream bg-brand rounded-lg hover:opacity-90 transition">Dashboard</a>
          <form method="POST" action="{{ route('auth.logout') }}" class="mx-auto">
            @csrf
            <button type="submit" class="w-full px-6 py-3 text-sm font-medium text-brown border border-brown/20 rounded-lg hover:bg-brown/5 transition">Keluar</button>
          </form>
        @else
          <a href="{{ route('auth.login') }}" class="block w-full text-center px-6 py-3 text-sm font-semibold text-cream bg-brand rounded-lg hover:opacity-90 transition">Masuk</a>
        @endauth
      </div>
    </div>

  </div>

  {{-- ═══════════════════════════════════════════════════════════════════════════
      MAIN CONTENT
      ═══════════════════════════════════════════════════════════════════════════ --}}
  <main class="flex-1">
    @yield('content')
  </main>

  {{-- ═══════════════════════════════════════════════════════════════════════════
      FOOTER — editorial grid, clean typography
      ═══════════════════════════════════════════════════════════════════════════ --}}
  <footer class="bg-[#0a0a0a] text-white mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

      <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">

        <div>
          <img src="{{ asset('assets/logo dark.png') }}" alt="SiPadu" class="h-9" />
          <p class="text-xs text-white/60 mt-3 leading-relaxed">
            Sistem Pembaruan Dokumen Pasca Perceraian. Terhubung antara Pengadilan Agama dan Disdukcapil.
          </p>
        </div>

        <div>
          <h4 class="text-[10px] font-semibold uppercase tracking-widest text-white/40 mb-4">Layanan</h4>
          <ul class="space-y-2.5 text-sm text-white/70">
            <li><a href="{{ route('services') }}" class="hover:text-white transition">Layanan Kami</a></li>
            <li><a href="{{ route('public.submit.create') }}" class="hover:text-white transition">Pengajuan Dokumen</a></li>
            <li><a href="{{ route('tracking.public') }}" class="hover:text-white transition">Lacak Pengajuan</a></li>
            <li><a href="{{ route('auth.login') }}" class="hover:text-white transition">Masuk Sistem</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-[10px] font-semibold uppercase tracking-widest text-white/40 mb-4">Informasi</h4>
          <ul class="space-y-2.5 text-sm text-white/70">
            <li><a href="{{ route('berita') }}" class="hover:text-white transition">Berita</a></li>
            <li><a href="{{ route('tentang') }}" class="hover:text-white transition">Tentang Kami</a></li>
            <li><a href="#" class="hover:text-white transition">FAQ</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-[10px] font-semibold uppercase tracking-widest text-white/40 mb-4">Legal</h4>
          <ul class="space-y-2.5 text-sm text-white/70">
            <li><a href="#" class="hover:text-white transition">Kebijakan Privasi</a></li>
            <li><a href="#" class="hover:text-white transition">Syarat & Ketentuan</a></li>
            <li><a href="#" class="hover:text-white transition">Hubungi Kami</a></li>
          </ul>
        </div>

      </div>

      <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-xs text-white/40">&copy; 2026 SiPadu &middot; PA &amp; Disdukcapil</p>
      </div>

    </div>
  </footer>

</div>

@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const toggle = document.getElementById('menuToggle');
  const nav = document.getElementById('mobileNav');
  const closeBtn = document.getElementById('menuClose');
  const icon = document.getElementById('menuIcon');
  if(!toggle || !nav || !closeBtn || !icon) return;

  let isOpen = false;

  function openMenu(){
    var nav = document.getElementById('mobileNav');
    var icon = document.getElementById('menuIcon');
    nav.style.transform = 'translateX(0)';
    nav.style.visibility = 'visible';
    icon.setAttribute('d','M6 18L18 6M6 6l12 12');
    document.body.style.overflow = 'hidden';
    toggle.setAttribute('aria-expanded','true');
    isOpen = true;
  }

  function closeMenu(){
    var nav = document.getElementById('mobileNav');
    var icon = document.getElementById('menuIcon');
    nav.style.transform = 'translateX(100%)';
    nav.style.visibility = 'hidden';
    icon.setAttribute('d','M4 6h16M4 12h16M4 18h16');
    document.body.style.overflow = '';
    toggle.setAttribute('aria-expanded','false');
    isOpen = false;
  }

  toggle.addEventListener('click', function(){
    (isOpen ? closeMenu : openMenu)();
  });

  closeBtn.addEventListener('click', closeMenu);

  nav.addEventListener('click', function(e){
    if(e.target === nav) closeMenu();
  });

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && isOpen) closeMenu();
  });
});
</script>
</body>
</html>
