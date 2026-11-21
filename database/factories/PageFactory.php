<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const TITLES = ['Inicio', 'Bitácora', 'Decisiones', 'Onboarding', 'Arquitectura'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'project_id' => null,
            'parent_id' => null,
            'title' => fake()->randomElement(self::TITLES),
            'body' => 'Notas del equipo.',
            'is_visible_to_client' => false,
            'position' => 0,
        ];
    }

    /**
     * Place the page in a project's wiki, keeping the project's workspace.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
        ]);
    }

    /**
     * Nest the page under another page of the same wiki.
     */
    public function childOf(Page $page): static
    {
        return $this->state(fn (): array => [
            'workspace_id' => $page->workspace_id,
            'project_id' => $page->project_id,
            'parent_id' => $page->id,
        ]);
    }
}
