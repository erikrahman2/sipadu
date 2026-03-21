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
            }
          }
        }
      }
    </script>
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
              <form method="POST" action="{{ route('auth.logout') }}">
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

<style>
  .nav-link {
    @apply text-white text-sm px-3 py-2 rounded-lg hover:bg-white/10 transition flex items-center;
  }
</style>

@stack('scripts')
</body>
</html>
