{{-- Performance Optimization Directives --}}

{{-- Preload critical resources --}}
@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    
    {{-- Preload critical fonts --}}
    <link rel="preload" as="font" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" crossorigin>
@endpush

{{-- Prefetch API endpoints --}}
@if(!auth()->check())
    <link rel="prefetch" href="{{ route('auth.login') }}">
    <link rel="prefetch" href="{{ route('public.submit.create') }}">
@else
    <link rel="prefetch" href="{{ route('dashboard.index') }}">
@endif

{{-- Enable service worker for offline support --}}
@if(env('APP_ENV') === 'production')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js').catch(() => {});
            });
        }
    </script>
@endif
