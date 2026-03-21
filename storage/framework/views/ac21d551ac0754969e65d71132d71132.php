<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
  <title>Login – PA Disdukcapil System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { primary: { DEFAULT: '#1d4ed8', dark: '#1e3a8a' } } } }
    }
  </script>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">

<div class="min-h-full flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">

    
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-4">
        <svg class="w-10 h-10 text-primary" fill="currentColor" viewBox="0 0 24 24">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">Sistem Integrasi</h1>
      <p class="text-blue-300 text-sm">PA – Disdukcapil</p>
    </div>

    
    <div class="bg-white rounded-2xl shadow-2xl p-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-6 text-center">Masuk ke Akun Anda</h2>

      <?php if($errors->any()): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-5">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <p><?php echo e($e); ?></p> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('auth.login.post')); ?>" class="space-y-5">
        <?php echo csrf_field(); ?>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
                 placeholder="nama@instansi.go.id"
                 class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary transition" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <div class="relative">
            <input type="password" name="password" id="pwd" required
                   placeholder="••••••••"
                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary transition pr-12" />
            <button type="button" onclick="togglePwd()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <i id="eyeIcon" class="fas fa-eye text-sm"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="rounded" /> Ingat saya
          </label>
        </div>

        <button type="submit"
                class="w-full bg-primary text-white rounded-xl py-3 font-semibold hover:bg-primary-dark transition flex items-center justify-center gap-2">
          <i class="fas fa-sign-in-alt"></i>
          Masuk
        </button>
      </form>
    </div>

    <p class="text-center text-blue-400 text-xs mt-6">
      © <?php echo e(date('Y')); ?> Sistem Integrasi PA–Disdukcapil
    </p>
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
<?php /**PATH D:\ProyekTA\resources\views/auth/login.blade.php ENDPATH**/ ?>