<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Login — SiPadu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            sidebar: '#1a1a1a',
            'sidebar-hover': '#2a2a2a',
            primary: { DEFAULT: '#6b7c5e', dark: '#4e5c45', light: '#8a9e76' },
            coral: { DEFAULT: '#c9956a', light: '#d4ad8b', dark: '#a07050' },
            'earth-bg': '#f5f0e8',
            'darktext': '#1a1a1a',
            'earth-muted': '#8a8578',
            'cream': '#e8e0d0',
          }
        }
      }
    }
  </script>
</head>
<body class="h-full bg-sidebar">

<div class="min-h-full flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
      <img src="{{ asset('assets/logo dark.png') }}" alt="SiPadu" class="w-24 h-24 mx-auto mb-4 rounded-2xl shadow-lg" />
      <h1 class="text-2xl font-bold text-white">Sistem Integrasi</h1>
      <p class="text-earth-muted text-sm">PA Painan — Disdukcapil Pessel</p>
    </div>

    {{-- Login Card --}}
    <div class="bg-white rounded-2xl shadow-2xl p-8 border border-cream/50">
      <h2 class="text-lg font-semibold text-darktext mb-6 text-center">Masuk ke Akun Anda</h2>

      @if($errors->any())
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-5">
        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
      </div>
      @endif

      <form method="POST" action="{{ route('auth.login.post') }}" class="space-y-5">
        @csrf

        <div>
          <label class="block text-sm font-medium text-darktext mb-1">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" required autofocus
                 placeholder="nama@instansi.go.id"
                 class="w-full border border-cream bg-earth-bg rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary transition" />
        </div>

        <div>
          <label class="block text-sm font-medium text-darktext mb-1">Password</label>
          <div class="relative">
            <input type="password" name="password" id="pwd" required
                   placeholder="••••••••"
                   class="w-full border border-cream bg-earth-bg rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary transition pr-12" />
            <button type="button" onclick="togglePwd()" class="absolute right-3 top-1/2 -translate-y-1/2 text-earth-muted hover:text-darktext">
              <i id="eyeIcon" class="fas fa-eye text-sm"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-sm text-darktext">
            <input type="checkbox" name="remember" class="rounded" /> Ingat saya
          </label>
        </div>

        <button type="submit"
                class="w-full bg-primary text-white rounded-xl py-3 font-semibold hover:bg-primary-dark transition flex items-center justify-center gap-2 shadow-sm">
          <i class="fas fa-sign-in-alt"></i>
          Masuk
        </button>
      </form>
    </div>

  </div>
</div>

<script>
function togglePwd() {
  const pwd = document.getElementById('pwd');
  const icon = document.getElementById('eyeIcon');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    pwd.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}
</script>
</body>
</html>
