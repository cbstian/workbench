<?php

namespace App\Enums;

/**
 * Whether a board note is still planned or was done (E11).
 *
 * A note may be created already done, to log work that was not planned.
 */
enum BoardEntryStatus: string
{
    case Planned = 'planned';
    case Done = 'done';

    /**
     * Spanish label shown in the legend and the note menu.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planificada',
            self::Done => 'Hecha',
        };
    }
}
