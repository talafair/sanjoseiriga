<script>
    (() => {
        const storageKey = 'talafair-theme';
        const root = document.documentElement;
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

        const getSavedTheme = () => {
            try {
                const theme = window.localStorage.getItem(storageKey);
                return theme === 'light' || theme === 'dark' ? theme : null;
            } catch (error) {
                console.warn('TalaFair could not read the saved appearance preference.', error);
                return null;
            }
        };

        const applyTheme = (theme) => {
            root.dataset.theme = theme;
            root.dataset.bsTheme = theme;
            root.style.colorScheme = theme;
            const themeColor = document.querySelector('meta[name="theme-color"]');
            if (themeColor) themeColor.content = theme === 'dark' ? '#151515' : '#F8F9FA';
        };

        const syncToggle = (theme) => {
            const toggle = document.querySelector('[data-theme-toggle]');
            if (!toggle) return;
            toggle.setAttribute('aria-checked', String(theme === 'dark'));
            toggle.dataset.theme = theme;
        };

        const initialTheme = getSavedTheme() || (systemTheme.matches ? 'dark' : 'light');
        applyTheme(initialTheme);

        const saveTheme = (theme) => {
            applyTheme(theme);
            syncToggle(theme);
            try {
                window.localStorage.setItem(storageKey, theme);
            } catch (error) {
                console.warn('TalaFair could not save the appearance preference.', error);
            }
        };

        const initializeToggle = () => {
            syncToggle(root.dataset.theme);
            document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
                saveTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeToggle, { once: true });
        } else {
            initializeToggle();
        }

        systemTheme.addEventListener('change', (event) => {
            if (!getSavedTheme()) {
                applyTheme(event.matches ? 'dark' : 'light');
                syncToggle(root.dataset.theme);
            }
        });
    })();
</script>