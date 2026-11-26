/**
 * Applies a newly chosen color palette without reloading the page.
 *
 * The server renders the palette as <html data-palette="…"> on every page
 * (so it survives reloads and wire:navigate). When the user picks another
 * one in Ajustes, the Livewire component dispatches `palette-changed` and the
 * attribute is swapped here; the CSS in resources/css/theme/palettes/ and the
 * charts (resources/js/modules/chart.js) react to it.
 */
export function registerPalette() {
    window.addEventListener('palette-changed', (event) => {
        const palette = event.detail?.palette;

        if (typeof palette === 'string' && palette !== '') {
            document.documentElement.dataset.palette = palette;
        }
    });
}
