<script>
    (function () {
        var key = 'ziifra-theme';
        var preference = 'system';

        try {
            preference = localStorage.getItem(key) || 'system';
        } catch (e) {}

        var dark = preference === 'dark'
            || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    })();
</script>
