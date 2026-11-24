<?php

namespace Database\Factories;

use App\Enums\BoardEntryStatus;
use App\Models\BoardEntry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardEntry>
 */
class BoardEntryFactory extends Factory
{
    /**
     * Short notes, the kind written on a whiteboard cell.
     *
     * @var list<string>
     */
    private const NOTES = ['Update', 'API', 'Backup', 'Reporte', 'Revisión', 'Deploy', 'Diseño', 'Llamada', 'Datos', 'Fix'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_id' => null,
            'occurs_on' => today()->toDateString(),
            'body' => fake()->randomElement(self::NOTES),
            'status' => BoardEntryStatus::Planned,
            'position' => 1024,
            'created_by_id' => fn (array $attributes): int => Project::query()->findOrFail($attributes['project_id'])->created_by_id,
            'completed_at' => null,
            'completed_by_id' => null,
        ];
    }

    /**
     * Record the note as already done. The creator is also who completed it
     * unless the caller sets completed_by_id.
     */
    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => BoardEntryStatus::Done,
            'completed_at' => now(),
        ])->afterCreating(function (BoardEntry $entry): void {
            if ($entry->completed_by_id === null) {
                $entry->forceFill(['completed_by_id' => $entry->created_by_id])->save();
            }
        });
    }
}
