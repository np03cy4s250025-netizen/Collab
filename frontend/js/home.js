/**
 * frontend/js/home.js
 * Home page interactive behaviour:
 * - Navbar scroll effect
 * - Mobile hamburger menu toggle
 * - Video play/pause toggle
 * - Video fallback handling (shows placeholder when no video file exists)
 * - Scroll-reveal animations
 */

(function () {
    'use strict';

    /* ---- Navbar scroll effect ---- */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.classList.toggle('nav-scrolled', window.scrollY > 60);
        }, { passive: true });
    }

    /* ---- Hamburger / mobile menu ---- */
    const hamburger = document.getElementById('hamburger-btn');
    const navLinks  = document.getElementById('nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function () {
            navLinks.classList.toggle('mobile-open');
            const icon = hamburger.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });

        // Close menu when a link is clicked
        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('mobile-open');
                const icon = hamburger.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            });
        });
    }

    /* ---- Background Video handling ---- */
    const video = document.getElementById('promo-video');
    if (video) {
        // Ensure video is playing (some browsers need an explicit play call even with autoplay)
        const playPromise = video.play();
        if (playPromise !== undefined) {
            playPromise.catch(() => {
                // Autoplay was prevented
                console.log("Background video autoplay blocked or failed.");
            });
        }
    }

    /* ---- Scroll-reveal (light Intersection Observer) ---- */
    const revealTargets = document.querySelectorAll(
        '.category-card, .step-card, .pop-vehicle-card, .city-card, .trust-item'
    );

    if ('IntersectionObserver' in window && revealTargets.length) {
        // Set initial state
        revealTargets.forEach(function (el, i) {
            el.style.opacity   = '0';
            el.style.transform = 'translateY(28px)';
            el.style.transition = 'opacity 0.55s ease ' + (i % 4) * 80 + 'ms, transform 0.55s ease ' + (i % 4) * 80 + 'ms';
        });

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity   = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealTargets.forEach(function (el) { observer.observe(el); });
    }

    /* ---- Smooth scroll for anchor links ---- */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const hash = this.getAttribute('href');
            if (hash === '#') return;
            const target = document.querySelector(hash);
            if (target) {
                e.preventDefault();
                const offset = navbar ? navbar.offsetHeight : 0;
                const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

})();
