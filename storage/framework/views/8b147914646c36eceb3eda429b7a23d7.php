<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['route', 'icon', 'label']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['route', 'icon', 'label']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
  try {
    $href   = route($route);
    $active = request()->routeIs($route);
  } catch (\Exception $e) {
    $href   = '#';
    $active = false;
  }
?>
<a href="<?php echo e($href); ?>"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
          <?php echo e($active
             ? 'bg-sidebar-active text-white font-medium'
             : 'text-slate-300 hover:bg-sidebar-hover hover:text-white'); ?>">
  <i class="fas <?php echo e($icon); ?> w-5 text-center text-sm"></i>
  <span><?php echo e($label); ?></span>
</a>
<?php /**PATH D:\ProyekTA\resources\views/components/admin-nav-item.blade.php ENDPATH**/ ?>