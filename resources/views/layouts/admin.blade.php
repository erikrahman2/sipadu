<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Dashboard') — SiPadu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            sidebar: '#1a1a1a',
            'sidebar-hover': '#2a2a2a',
            'sidebar-active': '#6b7c5e',
            primary: { DEFAULT: '#6b7c5e', light: '#8a9e76', dark: '#4e5c45' },
            'brand-dark': '#2d3a27',
            'coral': '#c9956a',
            'coral-dark': '#a07050',
            'accent': '#8b6f47',
            'accent-dark': '#5a4630',
            'earth-bg': '#f5f0e8',
            'darktext': '#1a1a1a',
            'earth-muted': '#8a8578',
            'cream': '#e8e0d0',
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    [x-cloak] { display: none !important; }
  </style>
  @stack('styles')
</head>

<body class="bg-earth-bg" x-data="adminLayout()" @keydown.escape="sidebarOpen = false">

<div class="flex h-screen overflow-hidden">

{{-- ══════════════════════════════════════════════════════════════════════
     MOBILE OVERLAY
══════════════════════════════════════════════════════════════════════ --}}
<!-- Mobile overlay — hanya untuk role yang pakai sidebar fixed -->
@if(auth()->user()->hasRole(['pa_assistant', 'pa_management', 'pa_staff', 'disdukcapil_staff']))
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-black/50 lg:hidden"
     style="display:none"></div>
@endif

{{-- ═══════════════��══════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════════════════════ --}}
<aside id="sidebar"
       @if(!auth()->user()->hasRole(['pa_assistant', 'pa_management', 'pa_staff', 'disdukcapil_staff']))
          class="lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 w-64 bg-sidebar flex flex-col flex-shrink-0"
       @else
          :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0"
       @endif
>

  {{-- Brand --}}
  <div class="flex items-center gap-3 px-5 py-5 border-b border-white/5 flex-shrink-0">
    <img src="{{ asset('assets/logo dark.png') }}" alt="SiPadu" class="w-12 h-12 flex-shrink-0" />
    <div class="min-w-0">
      <p class="text-white font-bold text-sm truncate">SiPadu</p>
      <p class="text-earth-muted text-xs truncate">Sistem Integrasi PA-Diskcapil</p>
    </div>
    {{-- Close button mobile --}}
    @if(auth()->user()->hasRole(['pa_assistant', 'pa_management', 'pa_staff', 'disdukcapil_staff']))
    <button @click="sidebarOpen = false" class="ml-auto text-earth-muted hover:text-white lg:hidden flex-shrink-0">
      <i class="fas fa-times"></i>
    </button>
    @endif
  </div>

  {{-- Navigation --}}
  <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

    {{-- Dashboard (semua role) --}}
    <x-admin-nav-item route="dashboard.index" icon="fa-gauge-high" label="Dashboard" />

    {{-- ── PA Management ─────────────────────────────────── --}}
    @role('pa_management')
      <div class="pt-4 pb-1 px-3">
        <p class="text-xs font-semibold text-earth-muted uppercase tracking-wider">Semua Data</p>
      </div>
      <x-admin-nav-item route="dashboard.review.all_data" icon="fa-database" label="Semua Data" />
    @endrole

    {{-- ── Disdukcapil Staff ───────────────────────────────── --}}
    @role('disdukcapil_staff')
      <div class="pt-4 pb-1 px-3">
        <p class="text-xs font-semibold text-earth-muted uppercase tracking-wider">Validasi</p>
      </div>
      <x-admin-nav-item route="dashboard.cases" icon="fa-clipboard-check" label="Validasi Kasus" />
    @endrole

    {{-- ── PA Staff ─────────────────────────────────────────── --}}
    @role('pa_staff')
      <div class="pt-4 pb-1 px-3">
        <p class="text-xs font-semibold text-earth-muted uppercase tracking-wider">Kasus</p>
      </div>
      <x-admin-nav-item route="dashboard.cases" icon="fa-folder" label="Daftar Kasus" />
    @endrole

    {{-- ── PA Staff ─────────────────────────────────────────── --}}
    @role('pa_staff')
      <div class="pt-4 pb-1 px-3">
        <p class="text-xs font-semibold text-earth-muted uppercase tracking-wider">Kelola Konten</p>
      </div>
      <x-admin-nav-item route="dashboard.admin.cms.kelola-konten.index" icon="fa-layer-group" label="Kelola Konten" />
    @endrole

    {{-- ── Super Admin ─────────────────────────────────────── --}}
    @role('super_admin')
      <div class="pt-3 pb-1 px-3">
        <p class="text-xs font-semibold text-earth-muted uppercase tracking-wider">Administrasi</p>
      </div>
      <x-admin-nav-item route="dashboard.admin.users" icon="fa-users" label="Manajemen User" />
      <x-admin-nav-item route="dashboard.admin.sync" icon="fa-rotate" label="Sinkronisasi Graph" />
      <x-admin-nav-item route="dashboard.admin.audit" icon="fa-shield-halved" label="Audit Log" />
      <x-admin-nav-item route="dashboard.admin.logs" icon="fa-list-check" label="Access Log" />
      <div class="pt-4 pb-1 px-3">
        <p class="text-xs font-semibold text-earth-muted uppercase tracking-wider">Kelola Konten</p>
      </div>
      <x-admin-nav-item route="dashboard.admin.cms.kelola-konten.index" icon="fa-layer-group" label="Kelola Konten" />
    @endrole

  </nav>

  {{-- User Profile --}}
  <div class="flex-shrink-0 border-t border-white/5 p-3">
    <div class="flex items-center gap-3 px-2 py-2 rounded-lg mb-2">
      <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name ?? '' }}</p>
        <p class="text-earth-muted text-xs truncate">{{ auth()->user()->getRoleNames()->first() ?? '' }}</p>
      </div>
    </div>
    {{-- Logout Button --}}
    <form method="POST" action="{{ route('auth.logout') }}" class="w-full">
      @csrf
      <button type="submit" class="w-full bg-white/5 hover:bg-white/10 text-earth-muted hover:text-white rounded-lg py-2.5 px-3 transition-all duration-200 flex items-center justify-center gap-2 font-medium text-sm group border border-white/5 hover:border-white/10">
        <i class="fas fa-arrow-right-from-bracket group-hover:translate-x-0.5 transition-transform"></i>
        <span>Keluar</span>
      </button>
    </form>
  </div>

</aside>

{{-- ══════════════════════════════════════════════════════════════════════
     MAIN WRAPPER
══════════════════════════════════════════════════════════════════════ --}}
<div class="flex-1 flex flex-col min-w-0 overflow-auto lg:h-[100dvh]">

  {{-- ─── TOP HEADER ─────────────────────────────────────────────────── --}}
  <header class="sticky top-0 z-30 bg-white border-b border-cream shadow-sm lg:hidden">
    <div class="flex items-center gap-4 px-4 sm:px-6 h-14">

      {{-- Hamburger menu button --}}
      @if(auth()->user()->hasRole(['pa_assistant', 'pa_management', 'pa_staff', 'disdukcapil_staff']))
      <button @click="sidebarOpen = true"
              class="lg:hidden -ml-1 p-2 rounded-lg text-earth-muted hover:bg-earth-bg transition">
        <i class="fas fa-bars"></i>
      </button>
      @endif

      {{-- Page Title --}}
      <div class="flex-1 min-w-0">
        <h1 class="text-sm font-semibold text-darktext truncate">@yield('page-title', 'Dashboard')</h1>
        @hasSection('breadcrumb')
        <nav class="flex items-center gap-1.5 text-xs text-gray-400 mt-0.5">
          @yield('breadcrumb')
        </nav>
        @endif
      </div>

      {{-- Right: notifications + user --}}
      <div class="flex items-center gap-2 flex-shrink-0">

        @hasanyrole('pa_management|disdukcapil_staff|pa_staff')
          @php try { $pendingBadge = \App\Models\PublicSubmission::where('status','PENDING')->count(); } catch(\Exception $e) { $pendingBadge = 0; } @endphp
          <a href="{{ route('dashboard.public-inbox.index') }}"
             class="relative p-2 rounded-lg text-earth-muted hover:bg-earth-bg transition"
             title="Pengajuan Publik Masuk">
            <i class="fas fa-bell text-sm"></i>
            @if($pendingBadge > 0)
              <span class="absolute top-1 right-1 w-2 h-2 bg-coral rounded-full"></span>
            @endif
          </a>
        @endhasanyrole

        {{-- User chip --}}
        <div x-data="{ open: false }" class="relative">
          <button @click="open = !open"
                  class="flex items-center gap-2 pl-1 pr-3 py-1.5 rounded-full hover:bg-earth-bg transition">
            <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-white text-xs font-bold">
              {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <span class="hidden sm:block text-sm text-darktext font-medium">{{ auth()->user()->name ?? '' }}</span>
            <i class="fas fa-chevron-down text-earth-muted" style="font-size:10px"></i>
          </button>
          <div x-show="open" @click.outside="open = false"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-cream z-50"
               style="display:none">
            <div class="px-4 py-3 border-b border-cream">
              <p class="text-sm font-semibold text-darktext">{{ auth()->user()->name ?? '' }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ auth()->user()->email ?? '' }}</p>
              <span class="inline-block mt-1.5 bg-primary/10 text-primary text-[10px] font-medium px-2 py-0.5 rounded-full">
                {{ auth()->user()->getRoleNames()->first() ?? '' }}
              </span>
            </div>
            @role('super_admin')
            <a href="{{ route('dashboard.admin.users') }}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-darktext hover:bg-earth-bg transition">
              <i class="fas fa-users w-4 text-center text-earth-muted"></i> Manajemen User
            </a>
            @else
            <a href="{{ route('dashboard.index') }}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-darktext hover:bg-earth-bg transition">
              <i class="fas fa-gauge-high w-4 text-center text-earth-muted"></i> Dashboard
            </a>
            @endrole
            <div class="border-t border-cream"></div>
            <form method="POST" action="{{ route('auth.logout') }}">
              @csrf
              <button type="submit"
                      class="w-full flex items-center gap-2 px-4 py-2 text-sm font-semibold text-coral-dark hover:bg-coral hover:text-white rounded-b-xl transition-all">
                <i class="fas fa-arrow-right-from-bracket w-4 text-center"></i> Keluar
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </header>
  {{-- ─── FLASH MESSAGES ─────────────────────────────────────────────── --}}
  @if(session('success'))
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
      <i class="fas fa-circle-check text-green-500 flex-shrink-0"></i>
      <span>{{ session('success') }}</span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  @endif
  @if(session('info'))
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
      <i class="fas fa-circle-info text-blue-500 flex-shrink-0"></i>
      <span>{{ session('info') }}</span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-blue-400 hover:text-blue-600">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  @endif
  @if(session('error'))
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
      <i class="fas fa-circle-exclamation text-red-500 flex-shrink-0"></i>
      <span>{{ session('error') }}</span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  @endif
  @if($errors->any())
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
      <div class="flex items-center gap-3 mb-1">
        <i class="fas fa-circle-exclamation text-red-500 flex-shrink-0"></i>
        <strong>Terdapat kesalahan:</strong>
      </div>
      <ul class="list-disc list-inside space-y-0.5 ml-6">
        @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
      </ul>
    </div>
  </div>
  @endif

  {{-- ─── SESSION TIMEOUT WARNING MODAL ─────────────────────────────── --}}
  <div id="sessionTimeoutModal"
       x-data="sessionTimeout()"
       x-show="showModal"
       x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm"
       style="display:none">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden">
      <div class="bg-yellow-50 px-6 py-4 border-b border-yellow-200 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center flex-shrink-0">
          <i class="fas fa-clock text-white"></i>
        </div>
        <div>
          <h3 class="font-semibold text-yellow-900 text-sm">Sesi Akan Berakhir</h3>
          <p class="text-xs text-yellow-700">Aktivitas terdeteksi terakhir</p>
        </div>
      </div>
      <div class="p-6 text-center">
        <p class="text-sm text-gray-600 mb-1">Sesi Anda akan berakhir dalam</p>
        <div class="text-4xl font-bold text-gray-900 mb-1" x-text="remainingSeconds"></div>
        <p class="text-xs text-gray-400 mb-5">detik</p>
        <p class="text-xs text-gray-500 mb-5">Klik tombol di bawah untuk tetap masuk.</p>
        <div class="flex gap-3">
          <button @click="logout"
                  class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
            Keluar
          </button>
          <button @click="extend"
                  class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition">
            <i class="fas fa-check mr-1"></i> Tetap Masuk
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ─── MAIN CONTENT ────────────────────────────────────────────────── --}}
  <main class="flex-1 px-4 sm:px-6 py-6 lg:pl-[17rem] lg:pr-6">
    @yield('content')
  </main>

  </div>{{-- main wrapper --}}

</div>{{-- outer flex h-screen --}}

{{-- Alpine.js --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
  function adminLayout() {
    return {
      sidebarOpen: false,
    }
  }

  function sessionTimeout() {
    const SESSION_LIFETIME = {{ config('session.lifetime', 60) * 60 }}; // seconds
    const WARNING_BEFORE  = 60; // show modal this many seconds before expiry
    const KEEPALIVE_URL   = '{{ route("keepalive") }}';
    const LOGOUT_URL      = '{{ route("auth.logout") }}';
    const LOGOUT_AFTER_URL = '{{ route("auth.login") }}';
    const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
      showModal: false,
      remainingSeconds: 0,
      _interval: null,
      _lastPing: Date.now(),

      init() {
        this._resetTimer();
        ['mousemove','keydown','click','scroll','touchstart'].forEach(evt => {
          document.addEventListener(evt, () => this._resetTimer(), { passive: true });
        });
      },

      _resetTimer() {
        clearInterval(this._interval);
        this.showModal = false;
        this.remainingSeconds = SESSION_LIFETIME;

        this._interval = setInterval(() => {
          if (--this.remainingSeconds <= WARNING_BEFORE && !this.showModal) {
            this.showModal = true;
          }
          if (this.remainingSeconds <= 0) {
            this.logout();
          }
        }, 1000);
      },

      extend() {
        // Re-fetch a protected page to bump Laravel session lifetime
        fetch(KEEPALIVE_URL, {
          headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'text/html' },
          credentials: 'same-origin'
        }).then(() => {
          // Update last activity server-side
          fetch('/api/keepalive', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            credentials: 'same-origin'
          }).catch(() => {});
        }).catch(() => {});

        this.showModal = false;
        this._resetTimer();
      },

      logout() {
        clearInterval(this._interval);
        fetch(LOGOUT_URL, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        }).finally(() => {
          window.location.href = LOGOUT_AFTER_URL;
        });
      }
    }
  }
</script>

@stack('scripts')
</body>
</html>
