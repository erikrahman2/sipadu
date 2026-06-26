@props(['route', 'icon', 'label'])
@php
  try {
    $href   = route($route);
    $active = request()->routeIs($route);
  } catch (\Exception $e) {
    $href   = '#';
    $active = false;
  }
@endphp
<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
          {{ $active
             ? 'bg-brand text-white font-medium'
             : 'text-slate-300 hover:bg-sidebar-hover hover:text-white' }}">
  <i class="fas {{ $icon }} w-5 text-center text-sm"></i>
  <span>{{ $label }}</span>
</a>
