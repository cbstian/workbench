<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TallStackUi\Facades\TallStackUi;

/**
 * Soft customizations of TallStackUI components.
 *
 * Colors come from the design tokens (resources/css/theme/*.css); this
 * provider only adjusts class blocks that tokens cannot reach, such as the
 * playful default icon colors of the theme switch. Keep every override here
 * so the look of the UI kit is defined in one place (docs/arquitectura/diseno.md).
 */
class TallStackUiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->customizeCard();
        $this->customizeSlide();
        $this->customizeDialog();
        $this->customizeModal();
        $this->customizeThemeSwitch();
    }

    /**
     * Flat, Notion-like cards: thin token border, surface background, no shadow.
     */
    private function customizeCard(): void
    {
        TallStackUi::customize()
            ->card()
            ->block([
                'wrapper.second' => 'flex w-full flex-col overflow-hidden rounded-lg border border-line bg-surface',
                'header.wrapper.border' => 'border-b border-line',
                'header.text.color' => 'text-ink',
                'body' => 'grow px-4 py-4 text-ink',
                'footer.wrapper' => 'border-t border-line p-4 text-ink',
            ]);
    }

    /**
     * Side panels (create/edit forms): a darker, slightly blurred backdrop and
     * a raised panel with a visible left edge, so its border stands out from
     * the page in light and dark themes.
     */
    private function customizeSlide(): void
    {
        TallStackUi::customize()
            ->slide()
            ->block([
                'wrapper.first' => 'fixed inset-0 bg-overlay backdrop-blur-[2px] transform transition-opacity',
                'wrapper.fifth' => 'flex flex-col bg-elevated py-6 shadow-2xl ring-1 ring-edge',
                'title.text' => 'whitespace-normal text-base font-semibold text-ink',
                'title.close' => 'h-5 w-5 cursor-pointer text-ink-subtle hover:text-ink',
                'body' => 'soft-scrollbar grow overflow-y-auto px-6 py-5 text-ink',
                'header.divider' => 'border-b border-b-line pb-4',
                'footer.wrapper' => 'border-t border-t-line px-4 pt-4',
            ]);
    }

    /**
     * Confirmation dialogs: same backdrop and raised panel as the slide.
     */
    private function customizeDialog(): void
    {
        TallStackUi::customize()
            ->dialog()
            ->block([
                'background' => 'fixed inset-0 bg-overlay backdrop-blur-[2px] transform transition-opacity',
                'wrapper.third' => 'relative w-full max-w-sm transform overflow-hidden rounded-xl bg-elevated p-4 text-left shadow-2xl ring-1 ring-edge transition-all sm:my-8',
                'text.title' => 'text-lg font-semibold leading-6 text-ink',
                'text.description.text' => 'text-sm text-ink-muted',
                'buttons.close.icon' => 'h-5 w-5 cursor-pointer text-ink-subtle hover:text-ink',
            ]);
    }

    /**
     * Modals: same backdrop and raised panel as the slide.
     */
    private function customizeModal(): void
    {
        TallStackUi::customize()
            ->modal()
            ->block([
                'wrapper.first' => 'fixed inset-0 bg-overlay backdrop-blur-[2px] transform transition-opacity',
                'wrapper.fourth' => 'relative flex w-full transform flex-col rounded-t-xl bg-elevated text-left shadow-2xl ring-1 ring-edge transition-all sm:rounded-xl',
                'title.wrapper' => 'flex items-center justify-between border-b border-b-line px-4 py-2.5',
                'title.text' => 'whitespace-normal text-base font-semibold text-ink',
                'title.close' => 'h-5 w-5 cursor-pointer text-ink-subtle hover:text-ink',
                'body' => 'grow rounded-b-xl px-4 py-5 text-ink',
                'footer.wrapper' => 'rounded-b-xl border-t border-t-line p-4 text-ink',
                'footer.scrollable' => 'sticky bottom-0 z-10 bg-elevated',
            ]);
    }

    /**
     * Neutral icons for the light/dark switch, matching the sober palette.
     */
    private function customizeThemeSwitch(): void
    {
        TallStackUi::customize()
            ->themeSwitch()
            ->block([
                'colors.moon' => 'text-ink-muted',
                'colors.sun' => 'text-ink-muted',
                'segmented.colors.moon' => 'text-ink-muted',
                'segmented.colors.sun' => 'text-ink-muted',
                'segmented.colors.system' => 'text-ink-muted',
                'segmented.wrapper' => 'inline-flex items-center gap-1 rounded-lg bg-hover p-1',
                'segmented.active' => 'bg-surface shadow-xs',
                'segmented.inactive' => 'hover:bg-hover',
            ]);
    }
}
