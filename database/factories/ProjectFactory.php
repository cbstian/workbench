<?php

namespace Database\Factories;

use App\Enums\ProjectPriorityLevel;
use App\Models\Project;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Realistic Spanish project names, built from a kind of work and a subject.
     *
     * @var list<string>
     */
    private const KINDS = ['Rediseño', 'Migración', 'Portal', 'App', 'Integración', 'Campaña', 'Auditoría', 'Panel', 'API', 'Sitio', 'Automatización', 'Wiki'];

    /**
     * @var list<string>
     */
    private const SUBJECTS = ['de clientes', 'de facturación', 'ecommerce', 'interna', 'de reservas', 'de inventario', 'corporativa', 'de pagos', 'de RR. HH.', 'de soporte', 'de marketing', 'de logística', 'de ventas', 'de onboarding'];

    /**
     * @var list<string>
     */
    private const DESCRIPTIONS = [
        'Levantamiento de requerimientos y prototipo navegable para validar con el cliente.',
        'Mejoras de rendimiento, accesibilidad y SEO sobre la versión actual.',
        'Conexión con el ERP para sincronizar productos, stock y precios.',
        'Nueva arquitectura de información y sistema de diseño compartido.',
        'Migración de datos históricos y retiro del sistema anterior.',
        'Automatización de reportes semanales y alertas operativas.',
        'Documentación técnica y funcional para el equipo de soporte.',
        'Implementación por etapas con entregas quincenales.',
    ];

    /**
     * @var list<string>
     */
    private const ICONS = ['📁', '🚀', '🧭', '🛠️', '📊', '🧩', '📦', '🗂️', '💡', '🧪', '📝', '🌱'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(self::KINDS).' '.fake()->randomElement(self::SUBJECTS),
            'description' => fake()->optional(0.85)->randomElement(self::DESCRIPTIONS),
            'icon' => fake()->randomElement(self::ICONS),
            'priority' => fake()->randomElement(ProjectPriorityLevel::cases()),
            'due_on' => fake()->optional(0.4)->dateTimeBetween('-1 week', '+6 weeks')?->format('Y-m-d'),
            'is_visible_to_client' => fake()->boolean(35),
            'created_by_id' => fn (array $attributes): int => Workspace::query()->findOrFail($attributes['workspace_id'])->account->owner_id,
            'created_at' => $createdAt = fake()->dateTimeBetween('-8 months'),
            'updated_at' => fake()->dateTimeBetween($createdAt),
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Project $project): void {
            if ($project->statuses()->exists()) {
                return;
            }

            $project->statuses()->createMany(ProjectStatus::DEFAULTS);
            $project->priorities()->createMany(ProjectPriority::DEFAULTS);
        });
    }

    /**
     * Mark the project as visible to its client.
     */
    public function visibleToClient(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_visible_to_client' => true,
        ]);
    }

    /**
     * Set the project-level priority (comparable across projects).
     */
    public function priority(ProjectPriorityLevel $level): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => $level,
        ]);
    }

    /**
     * Pause the project. Pausing is reversible and is not archiving.
     */
    public function paused(): static
    {
        return $this->afterCreating(function (Project $project): void {
            $project->forceFill([
                'paused_at' => now(),
                'paused_by_id' => $project->created_by_id,
            ])->save();
        });
    }
}
