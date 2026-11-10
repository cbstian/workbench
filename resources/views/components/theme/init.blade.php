{{--
    Applies the theme before the first paint to avoid a light/dark flash.

    Reads the same localStorage key TallStackUI uses ("dark-theme": light,
    dark or system) and falls back to the operating system preference.
--}}
<script>
    (() => {
        const stored = localStorage.getItem('dark-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = stored === 'dark' || stored === 'true' || ((stored === null || stored === 'system') && prefersDark);

        document.documentElement.classList.toggle('dark', isDark);
    })();
</script>
