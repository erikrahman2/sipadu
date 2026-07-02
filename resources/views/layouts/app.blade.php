<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'PA-Disdukcapil System') | Sistem Integrasi Kependudukan</title>
    <!-- Tailwind CSS CDN (production: replace with compiled asset) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary:  { DEFAULT: '#1d4ed8', light: '#3b82f6', dark: '#1e3a8a' },
              success:  '#16a34a',
              warning:  '#d97706',
              danger:   '#dc2626',
            },
            fontFamily: {
              display: ['Playfair Display', 'Georgia', 'serif'],
              sans: ['Inter', 'system-ui', 'sans-serif'],
            }
          }
        }
      }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    @stack('styles')
</head>
<body class="h-full">

<div class="min-h-full flex flex-col">

  {{-- ─── TOP NAV ─────────────────────────────────────────────────────── --}}
  <nav class="bg-primary-dark shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">

        {{-- Brand --}}
        <a href="{{ route('home') }}" class="flex items-center gap-3">
          <div class="flex-shrink-0 bg-white rounded-lg p-1">
            <svg class="h-8 w-8 text-primary" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <span class="text-white font-bold text-sm leading-tight">
            Sistem Integrasi<br>
            <span class="text-blue-200 text-xs font-normal">PA &amp; Disdukcapil</span>
          </span>
        </a>

        {{-- Nav items --}}
        <div class="hidden md:flex items-center gap-1">
          <a href="{{ route('public.submit.create') }}" class="nav-link">
            <i class="fas fa-file-alt mr-1"></i> Pengajuan
          </a>
          <a href="{{ route('tentang') }}" class="nav-link">
            <i class="fas fa-info-circle mr-1"></i> Tentang
          </a>
          <a href="{{ route('berita') }}" class="nav-link">
            <i class="fas fa-newspaper mr-1"></i> Berita
          </a>

          {{-- PA Assistant: kasus, upload --}}
          @role('pa_assistant')
            <a href="{{ route('dashboard.cases') }}" class="nav-link {{ request()->routeIs('dashboard.cases*') ? 'bg-white/20' : '' }}">
              <i class="fas fa-folder mr-1"></i> Kasus
            </a>
            <a href="{{ route('dashboard.upload') }}" class="nav-link {{ request()->routeIs('dashboard.upload') ? 'bg-white/20' : '' }}">
              <i class="fas fa-upload mr-1"></i> Upload
            </a>
          @endrole

          {{-- PA Management: kasus (review & approve) --}}
          @role('pa_management')
            <a href="{{ route('dashboard.cases') }}" class="nav-link {{ request()->routeIs('dashboard.cases*') ? 'bg-white/20' : '' }}">
              <i class="fas fa-tasks mr-1"></i> Review Kasus
            </a>
          @endrole

          {{-- PA Staff: kasus, arsip --}}
          @role('pa_staff')
            <a href="{{ route('dashboard.cases') }}" class="nav-link {{ request()->routeIs('dashboard.cases*') ? 'bg-white/20' : '' }}">
              <i class="fas fa-archive mr-1"></i> Kasus & Arsip
            </a>
          @endrole

          {{-- Disdukcapil Staff: kasus validasi --}}
          @role('disdukcapil_staff')
            <a href="{{ route('dashboard.cases') }}" class="nav-link {{ request()->routeIs('dashboard.cases*') ? 'bg-white/20' : '' }}">
              <i class="fas fa-check-double mr-1"></i> Validasi
            </a>
          @endrole

          {{-- Super Admin: panel admin --}}
          @role('super_admin')
            <a href="{{ route('dashboard.admin.users') }}" class="nav-link {{ request()->routeIs('dashboard.admin.users') ? 'bg-white/20' : '' }}">
              <i class="fas fa-users mr-1"></i> Manajemen User
            </a>
            <a href="{{ route('dashboard.admin.audit') }}" class="nav-link {{ request()->routeIs('dashboard.admin.audit') ? 'bg-white/20' : '' }}">
              <i class="fas fa-shield-halved mr-1"></i> Audit Log
            </a>
          @endrole

          {{-- Kotak Masuk Pengajuan Publik (petugas lapangan saja, bukan super_admin) --}}
          @hasanyrole('pa_assistant|pa_management|disdukcapil_staff|pa_staff')
            @php try { $pendingCount = \App\Models\PublicSubmission::where('status','PENDING')->count(); } catch(\Exception $e) { $pendingCount = 0; } @endphp
            <a href="{{ route('dashboard.public-inbox.index') }}"
              class="nav-link {{ request()->routeIs('dashboard.public-inbox*') ? 'bg-white/20' : '' }} relative">
              <i class="fas fa-inbox mr-1"></i> Pengajuan Publik
              @if($pendingCount > 0)
                <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white">
                  {{ $pendingCount > 99 ? '99+' : $pendingCount }}
                </span>
              @endif
            </a>
          @endhasanyrole
        </div>

        {{-- User dropdown --}}
        <div class="flex items-center gap-3">
          @auth
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 text-white hover:bg-white/10 rounded-lg px-3 py-2 transition">
              <div class="w-8 h-8 bg-blue-400 rounded-full flex items-center justify-center text-sm font-bold">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
              </div>
              <span class="hidden md:block text-sm">{{ auth()->user()->name }}</span>
              <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div x-show="open" @click.outside="open = false"
                 class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-100 z-50">
              <div class="px-4 py-3 border-b">
                <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500">{{ auth()->user()->getRoleNames()->first() }}</p>
              </div>
              <form method="POST" action="{{ route('auth.logout') }}" class="contents">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-xl transition">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </button>
              </form>
            </div>
          </div>
          @else
          <a href="{{ route('auth.login') }}" class="flex items-center gap-2 text-white hover:bg-white/10 rounded-lg px-3 py-2 transition text-sm">
            <i class="fas fa-sign-in-alt"></i>
            <span class="hidden md:block">Masuk</span>
          </a>
          @endauth
        </div>

      </div>
    </div>
  </nav>

  {{-- ─── BREADCRUMB ──────────────────────────────────────────────────── --}}
  @hasSection('breadcrumb')
  <div class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
      <nav class="text-sm text-gray-500 flex items-center gap-2">
        @yield('breadcrumb')
      </nav>
    </div>
  </div>
  @endif

  {{-- ─── FLASH MESSAGES ─────────────────────────────────────────────── --}}
  @if(session('success'))
  <div class="max-w-7xl mx-auto px-4 pt-4 w-full">
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-center gap-2">
      <i class="fas fa-check-circle text-green-500"></i>
      {{ session('success') }}
    </div>
  </div>
  @endif
  @if(session('error') || $errors->any())
  <div class="max-w-7xl mx-auto px-4 pt-4 w-full">
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-start gap-2">
      <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
      <div>
        {{ session('error') }}
        @if($errors->any())
          <ul class="list-disc list-inside mt-1 text-sm">
            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>
  @endif

  {{-- ─── SESSION TIMEOUT WARNING MODAL ─────────────────────────────── --}}
  @auth
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
                  class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-primary hover:bg-primary-dark rounded-xl transition">
            <i class="fas fa-check mr-1"></i> Tetap Masuk
          </button>
        </div>
      </div>
    </div>
  </div>
  @endauth

  {{-- ─── MAIN CONTENT ────────────────────────────────────────────────── --}}
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @yield('content')
  </main>

  {{-- ─── FOOTER ──────────────────────────────────────────────────────── --}}
  <footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 py-4 text-center text-xs text-gray-400">
      © {{ date('Y') }} Sistem Integrasi PA – Disdukcapil &nbsp;|&nbsp; v1.0.0 &nbsp;|&nbsp; Powered by Laravel 10
    </div>
  </footer>

</div>

{{-- Alpine.js for dropdowns --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
  function sessionTimeout() {
    const SESSION_LIFETIME = {{ config('session.lifetime', 60) * 60 }};
    const WARNING_BEFORE  = 60;
    const KEEPALIVE_URL   = '{{ route("keepalive") }}';
    const LOGOUT_URL      = '{{ route("auth.logout") }}';
    const LOGOUT_AFTER_URL = '{{ route("auth.login") }}';
    const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
      showModal: false,
      remainingSeconds: 0,
      _interval: null,

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
        fetch(KEEPALIVE_URL, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
          credentials: 'same-origin'
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

<style>
  .nav-link {
    @apply text-white text-sm px-3 py-2 rounded-lg hover:bg-white/10 transition flex items-center;
  }
</style>

@stack('scripts')
</body>
</html>
