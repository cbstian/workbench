<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Filters of a project's task views (RN-11, docs/arquitectura/modelo-de-datos.md 5.20.1):
 * status, priority, assignee, label, date range and search.
 *
 * Stored whole in project_task_views.filters (with "v": 1). Ids that no longer
 * belong to the project (deleted status, user out of the team) are dropped
 * when read.
 */
final readonly class TaskFilters
{
    public const int VERSION = 1;

    /**
     * @param  list<int>  $statusIds
     * @param  list<int>  $priorityIds
     * @param  list<int>  $assigneeIds
     * @param  list<int>  $labelIds
     */
    public function __construct(
        public array $statusIds = [],
        public array $priorityIds = [],
        public array $assigneeIds = [],
        public array $labelIds = [],
        public ?string $from = null,
        public ?string $to = null,
        public ?string $search = null,
    ) {}

    /**
     * Read stored or submitted filters, keeping only valid values of the project.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function fromArray(array $filters, Project $project): self
    {
        $keep = function (mixed $ids, array $valid): array {
            $ids = array_map('intval', array_filter((array) $ids, 'is_numeric'));

            return array_values(array_unique(array_intersect($ids, $valid)));
        };

        return new self(
            statusIds: $keep($filters['status_ids'] ?? [], $project->statuses()->pluck('id')->map(fn ($id): int => (int) $id)->all()),
            priorityIds: $keep($filters['priority_ids'] ?? [], $project->priorities()->pluck('id')->map(fn ($id): int => (int) $id)->all()),
            assigneeIds: $keep($filters['assignee_ids'] ?? [], $project->members()->pluck('users.id')->map(fn ($id): int => (int) $id)->all()),
            labelIds: $keep($filters['label_ids'] ?? [], $project->taskLabels()->pluck('id')->map(fn ($id): int => (int) $id)->all()),
            from: self::date(data_get($filters, 'date_range.from')),
            to: self::date(data_get($filters, 'date_range.to')),
            search: filled($filters['search'] ?? null) ? mb_substr(trim((string) $filters['search']), 0, 100) : null,
        );
    }

    /**
     * @return array{v: int, status_ids: list<int>, priority_ids: list<int>, assignee_ids: list<int>, label_ids: list<int>, date_range: array{from: string|null, to: string|null}, search: string|null}
     */
    public function toArray(): array
    {
        return [
            'v' => self::VERSION,
            'status_ids' => $this->statusIds,
            'priority_ids' => $this->priorityIds,
            'assignee_ids' => $this->assigneeIds,
            'label_ids' => $this->labelIds,
            'date_range' => ['from' => $this->from, 'to' => $this->to],
            'search' => $this->search,
        ];
    }

    /**
     * Number of active filters, search excluded.
     */
    public function count(): int
    {
        return count(array_filter([
            $this->statusIds,
            $this->priorityIds,
            $this->assigneeIds,
            $this->labelIds,
            $this->from !== null || $this->to !== null,
        ]));
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0 && $this->search === null;
    }

    /**
     * Restrict a task query to the filters. A date range keeps the tasks whose
     * dates overlap it; tasks without dates are left out.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->statusIds !== [], fn (Builder $query) => $query->whereIn('status_id', $this->statusIds))
            ->when($this->priorityIds !== [], fn (Builder $query) => $query->whereIn('priority_id', $this->priorityIds))
            ->when($this->assigneeIds !== [], fn (Builder $query) => $query->whereHas('assignees', fn (Builder $query) => $query->whereIn('users.id', $this->assigneeIds)))
            ->when($this->labelIds !== [], fn (Builder $query) => $query->whereHas('labels', fn (Builder $query) => $query->whereIn('task_labels.id', $this->labelIds)))
            ->when($this->from !== null || $this->to !== null, fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNotNull('starts_on')->orWhereNotNull('ends_on')))
            ->when($this->from !== null, fn (Builder $query) => $query->whereRaw('coalesce(ends_on, starts_on) >= ?', [$this->from]))
            ->when($this->to !== null, fn (Builder $query) => $query->whereRaw('coalesce(starts_on, ends_on) <= ?', [$this->to]))
            ->when($this->search !== null, function (Builder $query): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

                $query->where(fn (Builder $query) => $query
                    ->whereRaw("search_vector @@ plainto_tsquery('public.es_unaccent', ?)", [$this->search])
                    ->orWhereLike('name', $like, caseSensitive: false));
            });
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
