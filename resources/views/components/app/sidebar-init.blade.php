{{--
    Applies the stored desktop sidebar state (collapsed/expanded) before the
    first paint, so the panel does not jump on load. Same storage key as the
    Alpine store in resources/js/modules/sidebar.js. Only in layouts::app.
--}}
<script>
    (() => {
        let state = 'expanded';

        try {
            state = localStorage.getItem('wb-sidebar') === 'collapsed' ? 'collapsed' : 'expanded';
        } catch {
            // Storage unavailable: keep the sidebar expanded.
        }

        document.documentElement.dataset.sidebar = state;
    })();
</script>
