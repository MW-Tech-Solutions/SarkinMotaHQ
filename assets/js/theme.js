/**
 * Sarkin Mota HQ Global Theme & Appearance Engine
 * Flash-free Light / Dark / System Preference Controller
 */
(function() {
    'use strict';

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function getSavedTheme() {
        return localStorage.getItem('sarkinmota_theme') || 'system';
    }

    function applyTheme(theme) {
        const root = document.documentElement;
        let effectiveTheme = theme;

        if (theme === 'system') {
            effectiveTheme = getSystemTheme();
        }

        root.setAttribute('data-theme', effectiveTheme);

        if (effectiveTheme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        // Update single-button theme toggle icons dynamically
        const themeIcons = {
            'light': 'bi-sun',
            'dark': 'bi-moon-stars',
            'system': 'bi-circle-half'
        };
        const currentIconClass = themeIcons[theme] || 'bi-circle-half';

        document.querySelectorAll('.single-theme-toggle-icon').forEach(iconEl => {
            iconEl.className = 'bi ' + currentIconClass;
        });

        // Update any theme switcher buttons/dropdowns in DOM
        document.querySelectorAll('.theme-switcher-btn').forEach(btn => {
            const mode = btn.getAttribute('data-theme-mode');
            const isActive = mode === theme;
            btn.setAttribute('aria-pressed', String(isActive));
            if (isActive) {
                btn.classList.add('active', 'border-amber-500', 'bg-amber-500/10', 'text-amber-500');
            } else {
                btn.classList.remove('active', 'border-amber-500', 'bg-amber-500/10', 'text-amber-500');
            }
        });

        document.querySelectorAll('select.theme-select').forEach(select => {
            select.value = theme;
        });
    }

    // Expose global controller
    window.setThemeMode = function(mode) {
        if (['light', 'dark', 'system'].includes(mode)) {
            localStorage.setItem('sarkinmota_theme', mode);
            applyTheme(mode);

            // Sync with backend session if user is logged in
            if (window.fetch) {
                fetch('api/theme-preference.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: mode })
                }).catch(() => {});
            }
        }
    };

    window.cycleThemeMode = function() {
        const current = getSavedTheme();
        const nextMap = { 'light': 'dark', 'dark': 'system', 'system': 'light' };
        window.setThemeMode(nextMap[current] || 'light');
    };

    window.toggleThemeDropdown = function(dropdownId) {
        const dropdown = document.getElementById(dropdownId || 'mobile-theme-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    };

    // Close theme dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const themeBtn = e.target.closest('.theme-dropdown-btn');
        const themeMenu = e.target.closest('.theme-dropdown-menu');
        if (!themeBtn && !themeMenu) {
            document.querySelectorAll('.theme-dropdown-menu').forEach(menu => menu.classList.add('hidden'));
        }
    });

    // Initialize immediately to prevent flash
    const initialTheme = getSavedTheme();
    applyTheme(initialTheme);

    // Listen for OS system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getSavedTheme() === 'system') {
            applyTheme('system');
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(getSavedTheme());
    });
})();

