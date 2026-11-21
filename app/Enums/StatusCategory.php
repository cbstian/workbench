<?php

namespace App\Enums;

/**
 * Fixed meaning behind a configurable project status (docs/arquitectura/modelo-de-datos.md, P6).
 *
 * Lets the application tell open from closed tasks even when a project
 * renames its statuses.
 */
enum StatusCategory: string
{
    case Backlog = 'backlog';
    case Unstarted = 'unstarted';
    case Started = 'started';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Whether tasks in this category count as closed.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
