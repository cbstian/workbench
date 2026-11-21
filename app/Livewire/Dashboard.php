<?php

namespace App\Livewire;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panel home: greeting, figures of the current workspace and the latest
 * projects the user may open. A client user sees nothing yet (E10).
 *
 * Logic lives here; markup lives in resources/views/livewire/dashboard.blade.php.
 */
#[Title('Inicio')]
class Dashboard extends Component
{
    /**
     * Time-of-day greeting for the signed-in user, in Spanish.
     */
    #[Computed]
    public function greeting(): string
    {
        $hour = now()->hour;

        $salutation = match (true) {
            $hour < 6 => 'Buenas noches',
            $hour < 13 => 'Buenos días',
            $hour < 20 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        $firstName = str(auth()->user()->name)->before(' ')->toString();

        return "{$salutation}, {$firstName}";
    }

    #[Computed]
    public function workspace(): ?Workspace
    {
        return app(CurrentWorkspace::class)->get();
    }

    #[Computed]
    public function isOwner(): bool
    {
        return $this->workspace !== null && auth()->user()->ownsWorkspace($this->workspace);
    }

    /**
     * Whether the user is a client user of the workspace, who sees nothing for now (RN-04).
     */
    #[Computed]
    public function isClientUser(): bool
    {
        return $this->workspace !== null && auth()->user()->roleIn($this->workspace) === WorkspaceRole::Client;
    }

    /**
     * Names of the clients a client user belongs to in the current workspace.
     *
     * @return list<string>
     */
    #[Computed]
    public function clientNames(): array
    {
        if (! $this->isClientUser) {
            return [];
        }

        return auth()->user()->clients()
            ->where('clients.workspace_id', $this->workspace->id)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Projects of the current workspace the user may open.
     *
     * @return Builder<Project>
     */
    private function projects(): Builder
    {
        if ($this->workspace === null || $this->isClientUser) {
            return Project::query()->whereRaw('false');
        }

        return Project::query()->visibleTo(auth()->user(), $this->workspace);
    }

    /**
     * Headline figures shown as stat cards.
     *
     * @return list<array{label: string, value: int, hint: string}>
     */
    #[Computed]
    public function stats(): array
    {
        $stats = [
            [
                'label' => 'Proyectos',
                'value' => $this->projects()->count(),
                'hint' => $this->isOwner ? 'Activos en el espacio de trabajo' : 'Asignados a ti',
            ],
            [
                'label' => 'Visibles para el cliente',
                'value' => $this->projects()->where('is_visible_to_client', true)->count(),
                'hint' => 'Marcados como visibles',
            ],
            [
                'label' => 'Nuevos este mes',
                'value' => $this->projects()->where('created_at', '>=', now()->startOfMonth())->count(),
                'hint' => now()->translatedFormat('F Y'),
            ],
        ];

        if ($this->isOwner) {
            $stats[] = [
                'label' => 'Clientes',
                'value' => $this->workspace->clients()->count(),
                'hint' => 'En el espacio de trabajo',
            ];
            $stats[] = [
                'label' => 'Usuarios',
                'value' => $this->workspace->members()->count(),
                'hint' => 'Admins, miembros y clientes',
            ];
        }

        return $stats;
    }

    /**
     * ApexCharts options: projects created per month over the last six months.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function projectsPerMonthChart(): array
    {
        $months = collect(range(5, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));

        $countsByMonth = $this->projects()
            ->where('created_at', '>=', $months->first())
            ->pluck('created_at')
            ->countBy(fn ($createdAt): string => $createdAt->format('Y-m'));

        return [
            'chart' => ['type' => 'area'],
            'series' => [[
                'name' => 'Proyectos',
                'data' => $months->map(fn ($month): int => $countsByMonth->get($month->format('Y-m'), 0))->all(),
            ]],
            'xaxis' => [
                'categories' => $months->map(fn ($month): string => str($month->translatedFormat('M'))->rtrim('.')->ucfirst()->toString())->all(),
            ],
            'yaxis' => ['min' => 0, 'forceNiceScale' => true, 'decimalsInFloat' => 0],
            'fill' => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0]],
        ];
    }

    /**
     * ApexCharts options: projects visible to the client vs. hidden.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function visibilityChart(): array
    {
        $visible = $this->projects()->where('is_visible_to_client', true)->count();
        $hidden = $this->projects()->where('is_visible_to_client', false)->count();

        return [
            'chart' => ['type' => 'donut'],
            'series' => [$visible, $hidden],
            'labels' => ['Visibles', 'Ocultos'],
            'legend' => ['position' => 'bottom'],
            'stroke' => ['width' => 0],
        ];
    }

    /**
     * Most recently updated projects.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function recentProjects(): Collection
    {
        return $this->projects()->latest('updated_at')->limit(6)->get();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
