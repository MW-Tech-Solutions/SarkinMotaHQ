/**
 * Main Client-Side Functionality for Sarkin Mota HQ
 * Enterprise Edition
 */

document.addEventListener('DOMContentLoaded', () => {
    // Appearance is managed centrally by theme.js.
    // 2. Shrinking Header Navigation on Scroll
    const header = document.getElementById('main-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('py-3', 'shadow-lg');
                header.classList.remove('py-5');
            } else {
                header.classList.add('py-5');
                header.classList.remove('py-3', 'shadow-lg');
            }
        });
    }

    // 3. Mobile Hamburger Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // 4. Stats Counter Animation
    // Stats Counter Animation
    const counters = document.querySelectorAll('.stat-counter');
    const speed = 200;

    const runCounters = () => {
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = Math.max(1, Math.ceil(target / speed));

                if (count < target) {
                    counter.innerText = Math.min(target, count + inc);
                    setTimeout(updateCount, 20);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });
    };

    // Trigger counters only when visible in screen
    if (counters.length > 0) {
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    runCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        
        // Observe first counter container
        const statsSection = document.querySelector('.stats-section') || counters[0];
        if (statsSection) {
            observer.observe(statsSection);
        }
    }

    // 5. Scroll Reveal Animation using IntersectionObserver
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    entry.target.classList.remove('opacity-0');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        revealElements.forEach(el => {
            el.classList.add('opacity-0');
            revealObserver.observe(el);
        });
    }
});
