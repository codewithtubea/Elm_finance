// js/theme-manager.js - UPDATED VERSION
(function () {
    const THEME_KEY = 'elm-theme'; // Changed from 'elm_theme' to 'elm-theme'
    const themeToggleId = 'themeToggle';
    const themeIconId = 'themeIcon'; // Added for icon management

    function getStoredTheme() {
        try {
            return localStorage.getItem(THEME_KEY);
        } catch (e) {
            return null;
        }
    }

    function storeTheme(theme) {
        try {
            localStorage.setItem(THEME_KEY, theme);
        } catch (e) {
            console.error('Could not store theme:', e);
        }
    }

    function applyTheme(theme) {
        // Apply theme using data-theme attribute (what your CSS expects)
        document.documentElement.setAttribute('data-theme', theme);

        // Update toggle button and icon
        const toggle = document.getElementById(themeToggleId);
        const icon = document.getElementById(themeIconId);

        if (theme === 'light') {
            if (toggle) {
                toggle.setAttribute('aria-pressed', 'true');
                toggle.setAttribute('aria-label', 'Switch to dark theme');
            }
            if (icon) {
                icon.textContent = '☀️'; // Sun icon for light mode
            }
        } else {
            if (toggle) {
                toggle.setAttribute('aria-pressed', 'false');
                toggle.setAttribute('aria-label', 'Switch to light theme');
            }
            if (icon) {
                icon.textContent = '🌙'; // Moon icon for dark mode
            }
        }
    }

    function setTheme(theme) {
        storeTheme(theme);
        applyTheme(theme);
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const next = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(next);
    }

    function handleToggleClick(e) {
        e.preventDefault();
        toggleTheme();
    }

    function handleToggleKey(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleTheme();
        }
    }

    function init() {
        const toggle = document.getElementById(themeToggleId);
        const stored = getStoredTheme();

        // Initialize with stored theme, system preference, or default dark
        if (stored) {
            applyTheme(stored);
        } else if (window.matchMedia) {
            const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
            applyTheme(prefersLight ? 'light' : 'dark');
        } else {
            applyTheme('dark');
        }

        // Setup event listeners for toggle button
        if (toggle) {
            toggle.setAttribute('role', 'button');
            toggle.setAttribute('tabindex', '0');

            // Set initial aria attributes
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            toggle.setAttribute('aria-pressed', isLight ? 'true' : 'false');
            toggle.setAttribute('aria-label', isLight ? 'Switch to dark theme' : 'Switch to light theme');

            toggle.addEventListener('click', handleToggleClick);
            toggle.addEventListener('keydown', handleToggleKey);
        }

        // Listen for system theme changes (only if user hasn't set a preference)
        if (window.matchMedia) {
            const mq = window.matchMedia('(prefers-color-scheme: light)');
            const listener = (e) => {
                if (!getStoredTheme()) {
                    applyTheme(e.matches ? 'light' : 'dark');
                }
            };

            if (typeof mq.addEventListener === 'function') {
                mq.addEventListener('change', listener);
            } else if (typeof mq.addListener === 'function') {
                mq.addListener(listener);
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose API for other scripts
    window.elmTheme = {
        set: setTheme,
        toggle: toggleTheme,
        get: getStoredTheme,
        getCurrent: function () {
            return document.documentElement.getAttribute('data-theme');
        }
    };
})();