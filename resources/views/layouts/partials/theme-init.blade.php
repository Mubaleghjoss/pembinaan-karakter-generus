<script>
    (function () {
        var root = document.documentElement;
        var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

        function resolveThemePreference() {
            var stored = localStorage.getItem('darkMode');
            if (stored === 'true') return true;
            if (stored === 'false') return false;
            return media ? media.matches : false;
        }

        function applyTheme(isDark) {
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = isDark ? 'dark' : 'light';
            root.dataset.theme = isDark ? 'dark' : 'light';
        }

        applyTheme(resolveThemePreference());

        window.pkgTheme = {
            get: function () {
                return root.classList.contains('dark');
            },
            set: function (isDark) {
                localStorage.setItem('darkMode', isDark ? 'true' : 'false');
                applyTheme(isDark);
                window.dispatchEvent(new CustomEvent('pkg:theme-change', {
                    detail: { dark: isDark },
                }));
            },
            toggle: function () {
                this.set(!this.get());
            },
        };

        if (media && typeof media.addEventListener === 'function') {
            media.addEventListener('change', function (event) {
                if (localStorage.getItem('darkMode') !== null) {
                    return;
                }

                applyTheme(event.matches);
                window.dispatchEvent(new CustomEvent('pkg:theme-change', {
                    detail: { dark: event.matches },
                }));
            });
        }
    })();
</script>
