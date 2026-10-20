/* ==========================================================================
   Nobatyar — progressive enhancement
   --------------------------------------------------------------------------
   Nothing here is required for the app to work: every feature is an upgrade on
   top of server-rendered HTML. No dependencies, no build step.
     · theme switch (dark ⇄ light, remembered, no flash)
     · scroll reveals disabled under prefers-reduced-motion
     · focusable error summary + inline field errors on failed submit
     · header elevation once the page is scrolled
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var THEME_KEY = 'nobatyar-theme';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    /* ---------------------------------------------------------------- theme */
    function storedTheme() {
        try {
            return window.localStorage.getItem(THEME_KEY);
        } catch (error) {
            return null;
        }
    }

    function applyTheme(theme) {
        if (theme === 'light' || theme === 'dark') {
            root.setAttribute('data-theme', theme);
        }
    }

    function initTheme() {
        var toggle = document.querySelector('[data-theme-toggle]');
        if (!toggle) {
            return;
        }

        var label = toggle.getAttribute('data-label-template') || 'حالت نمایش: %s';

        function current() {
            return root.getAttribute('data-theme') ||
                (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        }

        function sync() {
            var theme = current();
            toggle.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
            var next = theme === 'light' ? 'تیره' : 'روشن';
            toggle.setAttribute('aria-label', label.replace('%s', next));
            toggle.setAttribute('title', 'تغییر به حالت ' + next);
        }

        toggle.hidden = false;
        sync();

        toggle.addEventListener('click', function () {
            var next = current() === 'light' ? 'dark' : 'light';
            applyTheme(next);
            try {
                window.localStorage.setItem(THEME_KEY, next);
            } catch (error) {
                /* private mode: the choice simply is not remembered */
            }
            sync();
        });
    }

    /* --------------------------------------------------------------- reveals */
    function revealAll() {
        Array.prototype.forEach.call(document.querySelectorAll('.reveal'), function (item) {
            item.classList.add('is-visible');
        });
    }

    function initReveals() {
        var items = Array.prototype.slice.call(document.querySelectorAll('.reveal'));

        // No animation when the visitor asked for none, when the browser cannot
        // observe intersections, or while the document is not being painted
        // (background tab / off-screen window): content must never stay hidden.
        if (reduced.matches || !('IntersectionObserver' in window) || document.visibilityState === 'hidden') {
            revealAll();
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var siblings = entry.target.parentElement
                    ? Array.prototype.slice.call(entry.target.parentElement.children)
                    : [];
                var index = siblings.indexOf(entry.target);
                entry.target.style.setProperty('--reveal-delay', Math.min(index, 6) * 70 + 'ms');
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        items.forEach(function (item) {
            observer.observe(item);
        });

        // Last-resort guard: if nothing became visible (unusual embedded
        // browsers), stop hiding content instead of leaving a blank page.
        window.setTimeout(function () {
            if (!document.querySelector('.reveal.is-visible')) {
                revealAll();
            }
        }, 1500);
    }

    /* ------------------------------------------------------- error handling */
    function fieldFor(name) {
        var selector = [
            '[name="' + name + '"]',
            '[name="' + name + '[]"]',
            '[name^="' + name + '["]',
            '#' + name
        ].join(', ');

        return document.querySelector('details[open] ' + selector) ||
            document.querySelector(selector) ||
            document.querySelector('[data-field-target="' + name + '"]');
    }

    function initValidationFeedback() {
        var summary = document.getElementById('error-summary');
        if (!summary) {
            return;
        }

        Array.prototype.forEach.call(summary.querySelectorAll('li[data-field]'), function (item) {
            var name = item.getAttribute('data-field');
            var control = name ? fieldFor(name) : null;
            var message = item.textContent.trim();

            if (!control) {
                return;
            }

            var wrapper = control.closest('.field') || control.parentElement;

            if (wrapper) {
                wrapper.classList.add('has-error');

                if (!wrapper.querySelector('.error-text')) {
                    var note = document.createElement('p');
                    note.className = 'error-text';
                    note.id = 'error-' + name;
                    note.textContent = message;
                    wrapper.appendChild(note);
                }
            }

            var describedBy = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
            if (describedBy.indexOf('error-' + name) === -1) {
                describedBy.push('error-' + name);
                control.setAttribute('aria-describedby', describedBy.join(' '));
            }
            control.setAttribute('aria-invalid', 'true');

            var link = document.createElement('a');
            link.className = 'link';
            link.href = '#' + (control.id || name);
            link.textContent = message;
            item.textContent = '';
            item.appendChild(link);
        });

        // A failed submit must be announced and reachable by keyboard.
        summary.focus({ preventScroll: false });
    }

    /* ---------------------------------------------------------------- header */
    function initHeader() {
        var header = document.querySelector('.site-header');
        if (!header) {
            return;
        }

        var onScroll = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ------------------------------------------------------------- utilities */
    var FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    window.Nobatyar = {
        toFa: function (value) {
            return String(value).replace(/\d/g, function (digit) {
                return FA_DIGITS[Number(digit)];
            });
        },
        groupedFa: function (value) {
            return window.Nobatyar.toFa(Number(value).toLocaleString('en-US'));
        },
        reducedMotion: reduced
    };

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    // The layout applies the stored theme from an inline head script; this keeps
    // the toggle in sync once the DOM is parsed.
    ready(function () {
        applyTheme(storedTheme());
        initTheme();
        initReveals();
        initValidationFeedback();
        initHeader();
    });

    // Follow the OS only while the visitor has not made an explicit choice.
    reduced.addEventListener('change', function () {
        if (reduced.matches) {
            initReveals();
        }
    });
})();
