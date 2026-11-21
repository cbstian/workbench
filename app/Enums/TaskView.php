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
}
