@props(['icon', 'label', 'prefix'])

@php
    $prefix  = $prefix ?? '';
    $current = request()->route() ? request()->route()->getName() : '';
    $active  = $prefix !== '' && $current !== '' && str_starts_with($current, $prefix);
@endphp

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="space-y-1">
    <button type="button"
            @click="open = !open"
            class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm transition
                   {{ $active
                      ? 'bg-white/15 text-white font-medium'
                      : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
        <span class="flex items-center gap-3">
            <i class="fas {{ $icon }} w-5 text-center text-sm"></i>
            <span>{{ $label }}</span>
        </span>
        <i class="fas fa-chevron-right text-[10px] transition-transform"
           :class="{ 'rotate-90': open }"></i>
    </button>

    <div x-show="open" x-transition.duration.150ms class="pl-4 space-y-1 border-l border-white/10 ml-5">
        {{ $slot }}
    </div>
</div>
