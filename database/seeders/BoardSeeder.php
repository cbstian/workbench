<?php

namespace Database\Seeders;

use App\Enums\BoardEntryStatus;
use App\Enums\ProjectPriorityLevel;
use App\Models\BoardEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * A week of shared board notes for the demo workspace, plus priorities,
 * due dates and a couple of paused projects.
 */
class BoardSeeder extends Seeder
{
    /**
     * Notes in the style of a physical whiteboard.
     *
     * @var list<string>
     */
    private const NOTES = ['Update', 'API', 'Backup', 'Reporte', 'Revisión', 'Deploy', 'Diseño', 'Llamada', 'Datos', 'Fix'];

    /**
     * @var list<string>
     */
    private const TASKS = ['Informe semanal', 'Integración API', 'Respaldo', 'Diseño', 'Revisión', 'Publicación'];

    public function run(): void
    {
        $owner = User::query()->where('email', 'demo@workbench.test')->first();

        if ($owner === null) {
            return;
        }

        $workspace = $owner->ownedWorkspaces()->first();

        if ($workspace === null || BoardEntry::query()->where('workspace_id', $workspace->id)->exists()) {
            return;
        }

        $projects = Project::query()->where('workspace_id', $workspace->id)->orderBy('id')->get();
        $levels = ProjectPriorityLevel::cases();
        $today = today()->startOfDay();
        $monday = $today->copy()->startOfWeek(Carbon::MONDAY);

        foreach ($projects as $index => $project) {
            $project->forceFill([
                'priority' => $levels[$index % count($levels)],
                'board_position' => ($index + 1) * 1024,
                'due_on' => null,
                'paused_at' => null,
                'paused_by_id' => null,
            ])->save();
        }

        $projects->get(1)?->forceFill([
            'priority' => ProjectPriorityLevel::Urgent,
            'due_on' => $today->copy()->subDays(3)->toDateString(),
        ])->save();

        $projects->get(2)?->forceFill([
            'priority' => ProjectPriorityLevel::High,
            'due_on' => $today->copy()->subDay()->toDateString(),
        ])->save();

        $projects->get(4)?->forceFill([
            'priority' => ProjectPriorityLevel::Medium,
            'due_on' => $today->copy()->addDays(12)->toDateString(),
        ])->save();

        foreach ([3, 8] as $pausedIndex) {
            $projects->get($pausedIndex)?->forceFill([
                'paused_at' => now(),
                'paused_by_id' => $owner->id,
            ])->save();
        }

        foreach ($projects->take(12)->values() as $index => $project) {
            $task = null;

            if ($index < count(self::TASKS)) {
                $task = Task::factory()->for($project)->create([
                    'name' => self::TASKS[$index],
                    'created_by_id' => $owner->id,
                    'ends_on' => $index === 1 ? $today->copy()->subDays(2)->toDateString() : null,
                ]);

                if ($index === 0) {
                    Task::factory()->subtaskOf($task)->create([
                        'name' => 'Borrador',
                        'created_by_id' => $owner->id,
                    ]);
                }
            }

            foreach (range(0, 4) as $dayOffset) {
                if (($index + $dayOffset) % 3 === 0) {
                    continue;
                }

                $date = $monday->copy()->addDays($dayOffset);
                $done = $date->lt($today) ? $index % 4 !== 0 : $date->isSameDay($today) && $index % 2 === 0;

                BoardEntry::factory()->for($project)->create([
                    'occurs_on' => $date->toDateString(),
                    'body' => self::NOTES[($index + $dayOffset) % count(self::NOTES)],
                    'status' => $done ? BoardEntryStatus::Done : BoardEntryStatus::Planned,
                    'task_id' => $task !== null && $dayOffset % 2 === 0 ? $task->id : null,
                    'created_by_id' => $owner->id,
                    'completed_at' => $done ? $date->copy()->setTime(18, 0) : null,
                    'completed_by_id' => $done ? $owner->id : null,
                    'position' => ($dayOffset + 1) * 1024,
                ]);
            }
        }
    }
}
