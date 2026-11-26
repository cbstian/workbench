<?php

namespace App\Support;

use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Month grid of the task calendars (project tasks and all tasks): weeks
 * Monday first, each day with the tasks whose dates cover it.
 */
final class TaskCalendar
{
    /**
     * First day of the month given as Y-m, or of the current month when invalid.
     */
    public static function month(string $month): Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * @param  iterable<int, Task>  $tasks
     * @return list<list<array{date: Carbon, inMonth: bool, isToday: bool, tasks: Collection<int, Task>}>>
     */
    public static function weeks(Carbon $month, iterable $tasks): array
    {
        $start = $month->copy()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $dated = collect($tasks)->filter(fn (Task $task): bool => $task->starts_on !== null || $task->ends_on !== null);

        $weeks = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $weeks[intdiv((int) $start->diffInDays($day), 7)][] = [
                'date' => $day->copy(),
                'inMonth' => $day->month === $month->month,
                'isToday' => $day->isToday(),
                'tasks' => $dated->filter(function (Task $task) use ($day): bool {
                    $from = $task->starts_on ?? $task->ends_on;
                    $to = $task->ends_on ?? $task->starts_on;

                    return $day->betweenIncluded($from->copy()->startOfDay(), $to->copy()->endOfDay());
                })->values(),
            ];
        }

        return array_values($weeks);
    }

    /**
     * Tasks without dates, which the calendar cannot place.
     *
     * @param  iterable<int, Task>  $tasks
     */
    public static function undatedCount(iterable $tasks): int
    {
        return collect($tasks)->filter(fn (Task $task): bool => $task->starts_on === null && $task->ends_on === null)->count();
    }
}
