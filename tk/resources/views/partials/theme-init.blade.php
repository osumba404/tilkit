<script>
    (function () {
        var stored = localStorage.getItem('tk-theme');
        var preferDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (stored === 'dark' || (stored !== 'light' && preferDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
