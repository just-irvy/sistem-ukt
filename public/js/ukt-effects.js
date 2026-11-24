/*
 * Interaksi antarmuka Sistem UKT.
 * Progressive enhancement: jika CDN gagal, konten tetap terlihat dan
 * browser tetap memakai native scrolling.
 * Tidak menyentuh request form, pembayaran, maupun webhook.
 */
(() => {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const desktopPointer = window.matchMedia('(hover: hover) and (pointer: fine)');

    if (reducedMotion.matches) return;

    async function boot() {
        // Muat terpisah supaya kegagalan salah satu pustaka tidak
        // mematikan interaksi atau menyembunyikan konten.
        const [motionResult, lenisResult] = await Promise.allSettled([
            import('https://cdn.jsdelivr.net/npm/motion@14.11.1/+esm'),
            desktopPointer.matches
                ? import('https://cdn.jsdelivr.net/npm/lenis@1.3.27/+esm')
                : Promise.resolve(null),
        ]);

        if (motionResult.status === 'fulfilled') {
            const { animate, inView } = motionResult.value;
            if (typeof animate === 'function' && typeof inView === 'function') {
                const seen = new WeakSet();
                const selector = [
                    '.dashboard-hero',
                    '.auth-intro',
                    '.auth-card',
                    '.page-heading',
                    '.container > .kartu',
                    '.container > .panel',
                    '.container > .summary',
                    '.container > .rr-grid',
                    '.menu-card',
                    '.summary-card',
                    '.rr-metric',
                    '.details .detail',
                    '.tabel-bungkus',
                    '.site-footer-inner',
                ].join(', ');

                const elements = [...document.querySelectorAll(selector)].filter(
                    (el) => !el.closest('[hidden], [aria-hidden="true"]')
                );

                inView(elements, (element) => {
                    if (seen.has(element)) return;
                    seen.add(element);

                    const group = element.parentElement;
                    const siblings = group
                        ? [...group.children].filter((child) => child.matches('.menu-card, .summary-card, .rr-metric, .detail'))
                        : [];
                    const index = siblings.indexOf(element);
                    const delay = index >= 0 ? Math.min(index * 0.065, 0.24) : 0;

                    animate(
                        element,
                        { opacity: [0, 1], y: [14, 0] },
                        { duration: 0.52, delay, ease: [0.22, 1, 0.36, 1] }
                    );
                }, { amount: 0.10, margin: '0px 0px -20px 0px' });
            }
        }

        if (lenisResult.status === 'fulfilled' && lenisResult.value?.default) {
            const Lenis = lenisResult.value.default;
            const lenis = new Lenis({
                autoRaf: true,
                smoothWheel: true,
                syncTouch: false,
                lerp: 0.10,
                wheelMultiplier: 0.9,
                anchors: { offset: -12 },
                prevent: (node) => Boolean(node.closest('[data-lenis-prevent]')),
            });

            // Bebaskan instance ketika halaman ditinggalkan.
            window.addEventListener('pagehide', () => lenis.destroy(), { once: true });
            reducedMotion.addEventListener('change', (event) => {
                if (event.matches) lenis.destroy();
            }, { once: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
