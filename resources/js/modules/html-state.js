/**
 * Keeps the client-side state stored on <html> across wire:navigate.
 *
 * On every navigation Livewire copies the attributes of the new page's <html>
 * onto the current one and drops the ones the server did not render, which
 * removes the `dark` class and `data-sidebar`. Both live only in the browser
 * (localStorage), so they are re-applied right after the swap, before paint.
 *
 * Same rules as <x-theme.init /> and <x-app.sidebar-init />.
 */
import { SIDEBAR_STORAGE_KEY } from './sidebar';

const THEME_STORAGE_KEY = 'dark-theme';

function read(key) {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
}

export function applyStoredTheme() {
    const stored = read(THEME_STORAGE_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = stored === 'dark' || stored === 'true' || ((stored === null || stored === 'system') && prefersDark);

    document.documentElement.classList.toggle('dark', isDark);
}

export function applyStoredSidebar() {
    const state = read(SIDEBAR_STORAGE_KEY) === 'collapsed' ? 'collapsed' : 'expanded';

    document.documentElement.dataset.sidebar = state;
}

function restore() {
    applyStoredTheme();
    applyStoredSidebar();
}

export function registerHtmlState() {
    document.addEventListener('livewire:navigating', (event) => {
        event.detail?.onSwap?.(restore);
    });

    document.addEventListener('livewire:navigated', restore);
}
