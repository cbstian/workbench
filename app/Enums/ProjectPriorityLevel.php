<?php

namespace App\Enums;

/**
 * Priority of a project, comparable across the workspace board (E11).
 *
 * Distinct from the per-project task priority catalog (ProjectPriority).
 */
enum ProjectPriorityLevel: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Spanish label shown on the board and in forms.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'Ninguna',
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    /**
     * Tailwind class of the priority dot. A complete literal so the build keeps it.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::None => 'bg-priority-none',
            self::Low => 'bg-priority-low',
            self::Medium => 'bg-priority-medium',
            self::High => 'bg-priority-high',
            self::Urgent => 'bg-priority-urgent',
        };
    }

    /**
     * Options for a TallStackUI select.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $level): array => [
            'label' => $level->label(),
            'value' => $level->value,
        ], self::cases());
    }
}
