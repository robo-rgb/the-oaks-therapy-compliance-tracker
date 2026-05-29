(function () {
    const root = document.documentElement;
    const toggle = document.getElementById('themeToggle');

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function getCurrentTheme() {
        return root.dataset.theme || localStorage.getItem('oaks-theme') || getSystemTheme();
    }

    function setTheme(theme, persist) {
        root.dataset.theme = theme;

        if (persist) {
            localStorage.setItem('oaks-theme', theme);
        }

        if (toggle) {
            const isDark = theme === 'dark';
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');

            const icon = toggle.querySelector('.theme-toggle__icon');
            const text = toggle.querySelector('.theme-toggle__text');

            if (icon) {
                icon.textContent = isDark ? '☾' : '☼';
            }

            if (text) {
                text.textContent = isDark ? 'Dark' : 'Light';
            }

            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }
    }

    setTheme(getCurrentTheme(), false);

    if (toggle) {
        toggle.addEventListener('click', function () {
            const nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
            setTheme(nextTheme, true);
        });
    }

    const media = window.matchMedia('(prefers-color-scheme: dark)');

    if (typeof media.addEventListener === 'function') {
        media.addEventListener('change', function () {
            if (!localStorage.getItem('oaks-theme')) {
                setTheme(getSystemTheme(), false);
            }
        });
    }
})();