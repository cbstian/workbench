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
        $this->customizeThemeSwitch();
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
