/**
 * frontend/js/app.js
 * Core application logic for UI interactions.
 */

document.addEventListener("DOMContentLoaded", () => {
    /* ---- Navigation / Hamburger ---- */
    const navToggle = document.getElementById("hamburger-btn");
    const navLinks  = document.getElementById("nav-links");
    
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            navLinks.classList.toggle('mobile-open');
        });

        // Close menu on link click
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('mobile-open');
            });
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!navLinks.contains(e.target) && !navToggle.contains(e.target)) {
                navLinks.classList.remove('mobile-open');
            }
        });
    }

    /* ---- Navbar Scroll Effect ---- */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        const handleScroll = () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        };
        window.addEventListener('scroll', handleScroll);
        // Initial check
        handleScroll();
    }

    /* ---- Confirmation Messages Auto-hide ---- */
    const alerts = document.querySelectorAll('.booking-alert, .av-alert, .dash-alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease, margin 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    }
});
