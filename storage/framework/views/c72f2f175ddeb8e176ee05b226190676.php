<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
  <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — SiPadu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            sidebar: '#0f172a',
            'sidebar-hover': '#1e293b',
            'sidebar-active': '#1d4ed8',
            primary: { DEFAULT: '#1d4ed8', light: '#3b82f6', dark: '#1e3a8a' },
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    [x-cloak] { display: none !important; }
  </style>
  <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body class="bg-slate-100" x-data="adminLayout()" @keydown.escape="sidebarOpen = false">

<div class="flex h-screen overflow-hidden">


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


<aside id="sidebar"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar flex flex-col transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 lg:flex-shrink-0">

  
  <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10 flex-shrink-0">
    <div class="w-9 h-9 bg-primary rounded-lg flex items-center justify-center flex-shrink-0">
      <i class="fas fa-landmark text-white text-sm"></i>
    </div>
    <div class="min-w-0">
      <p class="text-white font-bold text-sm truncate">SiPadu</p>
      <p class="text-slate-400 text-xs truncate">Sistem Integrasi PA–Disdukcapil</p>
    </div>
    
    <button @click="sidebarOpen = false" class="ml-auto text-slate-400 hover:text-white lg:hidden flex-shrink-0">
      <i class="fas fa-times"></i>
    </button>
  </div>

  
  <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

    
    <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.index','icon' => 'fa-gauge-high','label' => 'Dashboard']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.index','icon' => 'fa-gauge-high','label' => 'Dashboard']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'pa_assistant')): ?>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.cases','icon' => 'fa-folder-open','label' => 'Daftar Kasus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.cases','icon' => 'fa-folder-open','label' => 'Daftar Kasus']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'pa_management')): ?>
      <div class="pt-3 pb-1 px-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Review & Keputusan</p>
      </div>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.cases','icon' => 'fa-folder-open','label' => 'Daftar Kasus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.cases','icon' => 'fa-folder-open','label' => 'Daftar Kasus']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
      <?php try { $pendingValidations = \App\Models\OcrValidation::where('is_reviewed', false)->count(); } catch(\Exception $e) { $pendingValidations = 0; } ?>
      <a href="<?php echo e(route('dashboard.review.cases')); ?>"
         class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
                <?php echo e(request()->routeIs('dashboard.review*')
                   ? 'bg-sidebar-active text-white font-medium'
                   : 'text-slate-300 hover:bg-sidebar-hover hover:text-white'); ?>">
        <i class="fas fa-microscope w-5 text-center"></i>
        <span class="flex-1">Validasi OCR</span>
        <?php if($pendingValidations > 0): ?>
          <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold bg-yellow-500 text-white">
            <?php echo e($pendingValidations); ?>

          </span>
        <?php endif; ?>
      </a>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.review.statistics','icon' => 'fa-chart-bar','label' => 'Statistik OCR']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.review.statistics','icon' => 'fa-chart-bar','label' => 'Statistik OCR']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'pa_staff')): ?>
      <div class="pt-3 pb-1 px-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kasus & Arsip</p>
      </div>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.cases','icon' => 'fa-folder-open','label' => 'Daftar Kasus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.cases','icon' => 'fa-folder-open','label' => 'Daftar Kasus']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.upload','icon' => 'fa-upload','label' => 'Upload Dokumen']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.upload','icon' => 'fa-upload','label' => 'Upload Dokumen']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'disdukcapil_staff')): ?>
      <div class="pt-3 pb-1 px-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Validasi</p>
      </div>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.cases','icon' => 'fa-check-double','label' => 'Kasus Validasi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.cases','icon' => 'fa-check-double','label' => 'Kasus Validasi']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'pa_assistant|pa_management|disdukcapil_staff|pa_staff')): ?>
      <div class="pt-3 pb-1 px-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Publik</p>
      </div>
      <?php try { $pendingCount = \App\Models\PublicSubmission::where('status','PENDING')->count(); } catch(\Exception $e) { $pendingCount = 0; } ?>
      <a href="<?php echo e(route('dashboard.public-inbox.index')); ?>"
         class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
                <?php echo e(request()->routeIs('dashboard.public-inbox*')
                   ? 'bg-sidebar-active text-white font-medium'
                   : 'text-slate-300 hover:bg-sidebar-hover hover:text-white'); ?>">
        <i class="fas fa-inbox w-5 text-center"></i>
        <span class="flex-1">Pengajuan Publik</span>
        <?php if($pendingCount > 0): ?>
          <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold bg-red-500 text-white">
            <?php echo e($pendingCount > 99 ? '99+' : $pendingCount); ?>

          </span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'super_admin')): ?>
      <div class="pt-3 pb-1 px-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Administrasi</p>
      </div>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.admin.users','icon' => 'fa-users','label' => 'Manajemen User']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.admin.users','icon' => 'fa-users','label' => 'Manajemen User']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.admin.sync','icon' => 'fa-rotate','label' => 'Sinkronisasi Graph']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.admin.sync','icon' => 'fa-rotate','label' => 'Sinkronisasi Graph']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.admin.audit','icon' => 'fa-shield-halved','label' => 'Audit Log']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.admin.audit','icon' => 'fa-shield-halved','label' => 'Audit Log']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.admin.logs','icon' => 'fa-list-check','label' => 'Access Log']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.admin.logs','icon' => 'fa-list-check','label' => 'Access Log']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
      <div class="pt-2 pb-1 px-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monitoring OCR</p>
      </div>
      <?php try { $pendingValidations = \App\Models\OcrValidation::where('is_reviewed', false)->count(); } catch(\Exception $e) { $pendingValidations = 0; } ?>
      <a href="<?php echo e(route('dashboard.review.cases')); ?>"
         class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
                <?php echo e(request()->routeIs('dashboard.review*')
                   ? 'bg-sidebar-active text-white font-medium'
                   : 'text-slate-300 hover:bg-sidebar-hover hover:text-white'); ?>">
        <i class="fas fa-microscope w-5 text-center"></i>
        <span class="flex-1">Validasi OCR</span>
        <?php if($pendingValidations > 0): ?>
          <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold bg-yellow-500 text-white">
            <?php echo e($pendingValidations); ?>

          </span>
        <?php endif; ?>
      </a>
      <?php if (isset($component)) { $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-nav-item','data' => ['route' => 'dashboard.review.statistics','icon' => 'fa-chart-bar','label' => 'Statistik OCR']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'dashboard.review.statistics','icon' => 'fa-chart-bar','label' => 'Statistik OCR']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $attributes = $__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__attributesOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f)): ?>
<?php $component = $__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f; ?>
<?php unset($__componentOriginalc17a041c7cfa47ad2e8570c22c580c7f); ?>
<?php endif; ?>
    <?php endif; ?>

  </nav>

  
  <div class="flex-shrink-0 border-t border-white/10 p-3">
    <div class="flex items-center gap-3 px-2 py-2 rounded-lg mb-2">
      <div class="w-9 h-9 rounded-full bg-primary-light flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
        <?php echo e(strtoupper(substr(auth()->user()->name ?? 'A', 0, 1))); ?>

      </div>
      <div class="flex-1 min-w-0">
        <p class="text-white text-sm font-medium truncate"><?php echo e(auth()->user()->name ?? ''); ?></p>
        <p class="text-slate-400 text-xs truncate"><?php echo e(auth()->user()->getRoleNames()->first() ?? ''); ?></p>
      </div>
    </div>
    
    <form method="POST" action="<?php echo e(route('auth.logout')); ?>" class="w-full">
      <?php echo csrf_field(); ?>
      <button type="submit"
              class="w-full bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white rounded-lg py-2.5 px-3 transition-all duration-200 flex items-center justify-center gap-2 font-medium text-sm group border border-red-500/20 hover:border-red-500">
        <i class="fas fa-arrow-right-from-bracket group-hover:translate-x-0.5 transition-transform"></i>
        <span>Keluar</span>
      </button>
    </form>
  </div>

</aside>


<div class="flex-1 flex flex-col min-w-0 overflow-auto">

  
  <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center gap-4 px-4 sm:px-6 h-14">

      
      <button @click="sidebarOpen = true"
              class="lg:hidden -ml-1 p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition">
        <i class="fas fa-bars"></i>
      </button>

      
      <div class="flex-1 min-w-0">
        <h1 class="text-sm font-semibold text-gray-800 truncate"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h1>
        <?php if (! empty(trim($__env->yieldContent('breadcrumb')))): ?>
        <nav class="flex items-center gap-1.5 text-xs text-gray-400 mt-0.5">
          <?php echo $__env->yieldContent('breadcrumb'); ?>
        </nav>
        <?php endif; ?>
      </div>

      
      <div class="flex items-center gap-2 flex-shrink-0">

        <?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'pa_assistant|pa_management|disdukcapil_staff|pa_staff')): ?>
          <?php try { $pendingBadge = \App\Models\PublicSubmission::where('status','PENDING')->count(); } catch(\Exception $e) { $pendingBadge = 0; } ?>
          <a href="<?php echo e(route('dashboard.public-inbox.index')); ?>"
             class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition"
             title="Pengajuan Publik Masuk">
            <i class="fas fa-bell text-sm"></i>
            <?php if($pendingBadge > 0): ?>
              <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>

        
        <div x-data="{ open: false }" class="relative">
          <button @click="open = !open"
                  class="flex items-center gap-2 pl-1 pr-3 py-1.5 rounded-full hover:bg-gray-100 transition">
            <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-white text-xs font-bold">
              <?php echo e(strtoupper(substr(auth()->user()->name ?? 'A', 0, 1))); ?>

            </div>
            <span class="hidden sm:block text-sm text-gray-700 font-medium"><?php echo e(auth()->user()->name ?? ''); ?></span>
            <i class="fas fa-chevron-down text-gray-400" style="font-size:10px"></i>
          </button>
          <div x-show="open" @click.outside="open = false"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-gray-100 z-50"
               style="display:none">
            <div class="px-4 py-3 border-b border-gray-100">
              <p class="text-sm font-semibold text-gray-800"><?php echo e(auth()->user()->name ?? ''); ?></p>
              <p class="text-xs text-gray-500 mt-0.5"><?php echo e(auth()->user()->email ?? ''); ?></p>
              <span class="inline-block mt-1.5 bg-primary/10 text-primary text-[10px] font-medium px-2 py-0.5 rounded-full">
                <?php echo e(auth()->user()->getRoleNames()->first() ?? ''); ?>

              </span>
            </div>
            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'super_admin')): ?>
            <a href="<?php echo e(route('dashboard.admin.users')); ?>"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
              <i class="fas fa-users w-4 text-center text-gray-400"></i> Manajemen User
            </a>
            <?php else: ?>
            <a href="<?php echo e(route('dashboard.index')); ?>"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
              <i class="fas fa-gauge-high w-4 text-center text-gray-400"></i> Dashboard
            </a>
            <?php endif; ?>
            <div class="border-t border-gray-100"></div>
            <form method="POST" action="<?php echo e(route('auth.logout')); ?>">
              <?php echo csrf_field(); ?>
              <button type="submit"
                      class="w-full flex items-center gap-2 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-500 hover:text-white rounded-b-xl transition-all">
                <i class="fas fa-arrow-right-from-bracket w-4 text-center"></i> Keluar
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </header>

  
  <?php if(session('success')): ?>
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
      <i class="fas fa-circle-check text-green-500 flex-shrink-0"></i>
      <span><?php echo e(session('success')); ?></span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <?php endif; ?>
  <?php if(session('info')): ?>
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
      <i class="fas fa-circle-info text-blue-500 flex-shrink-0"></i>
      <span><?php echo e(session('info')); ?></span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-blue-400 hover:text-blue-600">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <?php endif; ?>
  <?php if(session('error')): ?>
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
      <i class="fas fa-circle-exclamation text-red-500 flex-shrink-0"></i>
      <span><?php echo e(session('error')); ?></span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <?php endif; ?>
  <?php if($errors->any()): ?>
  <div class="mx-4 sm:mx-6 mt-4">
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
      <div class="flex items-center gap-3 mb-1">
        <i class="fas fa-circle-exclamation text-red-500 flex-shrink-0"></i>
        <strong>Terdapat kesalahan:</strong>
      </div>
      <ul class="list-disc list-inside space-y-0.5 ml-6">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li><?php echo e($err); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  
  <main class="flex-1 px-4 sm:px-6 py-6">
    <?php echo $__env->yieldContent('content'); ?>
  </main>

  
  <footer class="px-6 py-3 text-center text-xs text-gray-400 border-t border-gray-200 bg-white">
    © <?php echo e(date('Y')); ?> SiPadu — Sistem Integrasi PA &amp; Disdukcapil &nbsp;|&nbsp; v1.0.0
  </footer>

</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
  function adminLayout() {
    return {
      sidebarOpen: false,
    }
  }
</script>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\ProyekTA\resources\views/layouts/admin.blade.php ENDPATH**/ ?>