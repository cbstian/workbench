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
