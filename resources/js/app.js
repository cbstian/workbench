/**
 * Workbench — JavaScript entry point.
 *
 * Alpine.js is provided by Livewire and TallStackUI is loaded through
 * <tallstackui:script />, so only app-specific modules live here.
 * See docs/arquitectura/frontend.md.
 */
import { registerSidebar } from './modules/sidebar';
import { registerChart } from './modules/chart';
import { registerHtmlState } from './modules/html-state';
import { registerEmojiPicker } from './modules/emoji-picker';
import { registerPalette } from './modules/palette';

registerHtmlState();
registerPalette();

document.addEventListener('alpine:init', () => {
    registerSidebar(window.Alpine);
    registerChart(window.Alpine);
    registerEmojiPicker(window.Alpine);
});

/*
 * Let Vite process (hash and version) every image in resources/images
 * (except the untouched brand originals) so
 * Blade can reference them with Vite::asset('resources/images/...').
 */
import.meta.glob(['../images/**', '!../images/brand/originales/**']);
