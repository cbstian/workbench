<?php

namespace App\Enums;

/**
 * Ways to look at the tasks of a project (docs/flujo-canonico.md, RN-11).
 */
enum TaskView: string
{
    case Kanban = 'kanban';
    case List = 'list';
    case Calendar = 'calendar';

    /**
     * Spanish name shown in the view switcher.
     */
    public function label(): string
    {
        return match ($this) {
            self::Kanban => 'Kanban',
            self::List => 'Listado',
            self::Calendar => 'Calendario',
        };
    }

    /**
     * Heroicon of the view switcher.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Kanban => 'view-columns',
            self::List => 'list-bullet',
            self::Calendar => 'calendar-days',
        };
    }
}
