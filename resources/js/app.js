let scrollObserver;

function initializeScrollAnimations() {
    scrollObserver?.disconnect();

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.removeAttribute('data-scroll-animations');

        return;
    }

    document.documentElement.setAttribute('data-scroll-animations', '');

    scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            scrollObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.08,
        rootMargin: '0px 0px -8% 0px',
    });

    document.querySelectorAll('.scroll-reveal, .reveal-card').forEach((element) => {
        scrollObserver.observe(element);
    });
}

document.addEventListener('DOMContentLoaded', initializeScrollAnimations);
document.addEventListener('livewire:navigated', initializeScrollAnimations);
