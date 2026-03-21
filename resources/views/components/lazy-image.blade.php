@props(['src', 'alt' => '', 'class' => '', 'width' => '', 'height' => ''])

<img 
    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
    data-src="{{ $src }}"
    alt="{{ $alt }}"
    class="lazy-image {{ $class }}"
    {{ $attributes->except('src', 'alt', 'class')->merge(['width' => $width, 'height' => $height]) }}
/>

<script>
    // Lazy load images bila belum ada observer
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLazyLoad);
    } else {
        initLazyLoad();
    }

    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy-image');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.lazy-image').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback untuk browser lama
            document.querySelectorAll('img.lazy-image').forEach(img => {
                img.src = img.dataset.src;
            });
        }
    }
</script>

<style>
    .lazy-image {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    
    .lazy-image[src*='data:image'] {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }
</style>
