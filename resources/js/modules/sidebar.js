/**
 * Sidebar state shared by the panel layout (Alpine store `sidebar`).
 *
 * - Desktop (md and up): the sidebar can be collapsed; the choice is kept in
 *   localStorage and mirrored on <html data-sidebar="collapsed|expanded">, which
 *   <x-app.sidebar-init /> applies before the first paint to avoid a jump.
 * - Mobile: the sidebar is an off-canvas panel (`mobileOpen`), closed again on
 *   every wire:navigate.
 * - Shortcut: Ctrl/Cmd + \ toggles it, like Notion.
 */
export const SIDEBAR_STORAGE_KEY = 'wb-sidebar';

const DESKTOP_QUERY = '(min-width: 768px)';

export function registerSidebar(Alpine) {
    Alpine.store('sidebar', {
        collapsed: document.documentElement.dataset.sidebar === 'collapsed',
        mobileOpen: false,

        toggle() {
            if (window.matchMedia(DESKTOP_QUERY).matches) {
                this.setCollapsed(!this.collapsed);
            } else {
                this.mobileOpen = !this.mobileOpen;
            }
        },

        setCollapsed(collapsed) {
            this.collapsed = collapsed;
            document.documentElement.dataset.sidebar = collapsed ? 'collapsed' : 'expanded';

            try {
                localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed ? 'collapsed' : 'expanded');
            } catch {
                // Storage can be unavailable (private mode); the state still works for this page.
            }
        },
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key === '\\') {
            event.preventDefault();
            Alpine.store('sidebar').toggle();
        }
    });

    document.addEventListener('livewire:navigated', () => {
        Alpine.store('sidebar').mobileOpen = false;
    });
}
