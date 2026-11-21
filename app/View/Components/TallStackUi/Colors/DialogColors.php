<?php

namespace App\View\Components\TallStackUi\Colors;

use Illuminate\View\Component;

/**
 * Colors of TallStackUI dialogs (published stub, see `php artisan tallstackui:setup-color`).
 *
 * The cancel button is neutral: red belongs to the destructive action, not to
 * backing out of it. Null keeps the package default.
 */
class DialogColors
{
    public function cancelColors(Component $component): ?string
    {
        return 'secondary';
    }
}
