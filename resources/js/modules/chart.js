/**
 * ApexCharts integration (Alpine component `wbChart`), used by <x-ui.chart>.
 *
 * - ApexCharts is loaded with a dynamic import, so pages without charts never
 *   download it (it lands in its own chunk).
 * - Colors, fonts, grid and tooltip come from the design tokens
 *   (resources/css/theme/) and follow the light/dark mode and the color
 *   palette live.
 * - Options passed from Blade are deep-merged over those defaults, so a chart
 *   only declares what is specific to it (type, series, labels, axes…).
 *
 * ApexCharts docs: https://apexcharts.com/docs/options/
 */
let apexChartsPromise = null;

function loadApexCharts() {
    apexChartsPromise ??= import('apexcharts').then((module) => module.default);

    return apexChartsPromise;
}

function isPlainObject(value) {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

/**
 * Recursively merges `source` into a copy of `target`. Arrays are replaced.
 */
export function deepMerge(target, source) {
    const output = { ...target };

    for (const [key, value] of Object.entries(source ?? {})) {
        output[key] = isPlainObject(value) && isPlainObject(output[key])
            ? deepMerge(output[key], value)
            : value;
    }

    return output;
}

function token(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

/**
 * Defaults derived from the current theme. Called again when the mode or palette changes.
 */
function themeOptions() {
    const isDark = document.documentElement.classList.contains('dark');
    const palette = [1, 2, 3, 4, 5, 6].map((index) => token(`--wb-chart-${index}`));

    return {
        chart: {
            background: 'transparent',
            foreColor: token('--wb-ink-muted'),
            fontFamily: getComputedStyle(document.body).fontFamily,
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches },
        },
        colors: palette,
        theme: { mode: isDark ? 'dark' : 'light' },
        grid: {
            borderColor: token('--wb-line'),
            strokeDashArray: 4,
            padding: { left: 8, right: 8 },
        },
        stroke: { width: 2, curve: 'smooth' },
        dataLabels: { enabled: false },
        legend: {
            fontSize: '12px',
            labels: { colors: token('--wb-ink-muted') },
            markers: { size: 5 },
        },
        tooltip: { theme: isDark ? 'dark' : 'light' },
        xaxis: {
            axisBorder: { color: token('--wb-line') },
            axisTicks: { color: token('--wb-line') },
        },
        plotOptions: {
            pie: { donut: { size: '72%' } },
        },
        states: {
            hover: { filter: { type: 'none' } },
        },
        noData: { text: 'Sin datos' },
    };
}

export function registerChart(Alpine) {
    Alpine.data('wbChart', (options = {}) => ({
        chart: null,
        observer: null,

        async init() {
            const ApexCharts = await loadApexCharts();

            this.chart = new ApexCharts(this.$refs.canvas, deepMerge(themeOptions(), options));
            await this.chart.render();

            this.observer = new MutationObserver(() => {
                this.chart?.updateOptions(deepMerge(themeOptions(), options), false, false);
            });

            this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-palette'] });
        },

        destroy() {
            this.observer?.disconnect();
            this.chart?.destroy();
            this.chart = null;
        },
    }));
}
