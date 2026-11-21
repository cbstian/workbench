<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panel home: greeting, a few project figures and the latest projects.
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

    /**
     * Headline figures shown as stat cards.
     *
     * @return list<array{label: string, value: int, hint: string}>
     */
    #[Computed]
    public function stats(): array
    {
        return [
            [
                'label' => 'Proyectos',
                'value' => Project::query()->count(),
                'hint' => 'Activos en el espacio de trabajo',
            ],
            [
                'label' => 'Visibles para el cliente',
                'value' => Project::query()->where('is_visible_to_client', true)->count(),
                'hint' => 'Marcados como visibles',
            ],
            [
                'label' => 'Nuevos este mes',
                'value' => Project::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                'hint' => now()->translatedFormat('F Y'),
            ],
        ];
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

        $countsByMonth = Project::query()
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
        $visible = Project::query()->where('is_visible_to_client', true)->count();
        $hidden = Project::query()->where('is_visible_to_client', false)->count();

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
        return Project::query()->latest('updated_at')->limit(6)->get();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
