document.addEventListener('DOMContentLoaded', function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Tandai menu navbar halaman aktif
    try {
        var path = window.location.pathname.split('/').pop().split('?')[0] || 'index.php';
        document.querySelectorAll('.auto-pill-nav a').forEach(function (a) {
            var href = (a.getAttribute('href') || '').split('/').pop().split('?')[0];
            if (href && href === path) {
                a.classList.add('active');
                a.setAttribute('aria-current', 'page');
            }
        });
    } catch (e) {}

    // Reveal on scroll (progressive enhancement: hanya aktif bila class .js ada)
    var revealEls = document.querySelectorAll('.reveal');
    if (reduceMotion || !('IntersectionObserver' in window)) {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    } else {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

        revealEls.forEach(function (el) {
            var siblings = el.parentElement
                ? el.parentElement.querySelectorAll(':scope > .reveal')
                : [el];
            var idx = Array.prototype.indexOf.call(siblings, el);
            if (idx > 0) el.style.transitionDelay = Math.min(idx, 8) * 60 + 'ms';
            revealObserver.observe(el);
        });
    }

    // Count-up angka statistik
    function runCount(el) {
        var target = parseFloat(el.getAttribute('data-count'));
        if (isNaN(target)) return;
        var suffix = el.getAttribute('data-suffix') || '';
        if (reduceMotion) {
            el.textContent = target.toLocaleString('id-ID') + suffix;
            return;
        }
        var duration = 1100;
        var startTime = null;
        function tick(now) {
            if (startTime === null) startTime = now;
            var p = Math.min((now - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('id-ID') + suffix;
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    var counters = document.querySelectorAll('[data-count]');
    if (counters.length) {
        if (reduceMotion || !('IntersectionObserver' in window)) {
            counters.forEach(runCount);
        } else {
            var countObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        runCount(entry.target);
                        countObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            counters.forEach(function (el) { countObserver.observe(el); });
        }
    }

    // Smooth scroll untuk tautan internal
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var id = a.getAttribute('href').slice(1);
            if (!id) return;
            var target = document.getElementById(id);
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
        });
    });

    // Feedback loading saat submit form
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.dataset.loading) {
                btn.dataset.loading = 'true';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
                setTimeout(function () {
                    delete btn.dataset.loading;
                    btn.style.opacity = '';
                    btn.style.pointerEvents = '';
                }, 3000);
            }
        });
    });
});
