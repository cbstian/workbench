/**
 * Workbench — JavaScript entry point.
 *
 * Alpine.js is provided by Livewire and TallStackUI is loaded through
 * <tallstackui:script />, so only app-specific modules live here.
 * See docs/arquitectura/frontend.md.
 */
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

/*
 * Let Vite process (hash and version) every image in resources/images
 * (except the untouched brand originals) so
 * Blade can reference them with Vite::asset('resources/images/...').
 */
import.meta.glob(['../images/**', '!../images/brand/originales/**']);
