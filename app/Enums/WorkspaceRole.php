<?php

namespace App\Enums;

/**
 * Role of a user inside a workspace (docs/flujo-canonico.md, RN-15).
 *
 * The super admin is the workspace owner, not a role on this enum. Each case
 * is also a global spatie role whose permissions come from permissions().
 */
enum WorkspaceRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Client = 'client';

    /**
     * Spanish name shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Miembro',
            self::Client => 'Cliente',
        };
    }

    /**
     * What the role does, in Spanish.
     */
    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Hace todo dentro de sus proyectos y puede crear miembros para ellos.',
            self::Member => 'Ve todas las tareas de sus proyectos y edita solo las asignadas.',
            self::Client => 'Pertenece a uno o más clientes. Por ahora entra sin ver nada.',
        };
    }

    /**
     * Badge color in TallStackUI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Member => 'secondary',
            self::Client => 'amber',
        };
    }

    /**
     * Whether the role can join project teams and be assigned to tasks (RN-18).
     */
    public function isInternal(): bool
    {
        return $this !== self::Client;
    }

    /**
     * Permissions granted to the role (RN-21, RN-23, RN-24).
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => [
                Permission::ProjectsUpdate,
                Permission::ProjectsConfigure,
                Permission::MembersCreate,
                Permission::TasksView,
                Permission::TasksCreate,
                Permission::TasksUpdateAny,
                Permission::TasksUpdateAssigned,
                Permission::ProjectWiki,
                Permission::ProjectTrash,
            ],
            self::Member => [
                Permission::TasksView,
                Permission::TasksCreate,
                Permission::TasksUpdateAssigned,
                Permission::ProjectWiki,
            ],
            self::Client => [],
        };
    }

    /**
     * Options for a TallStackUI select.
     *
     * @return list<array{label: string, value: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $role): array => [
            'label' => $role->label(),
            'value' => $role->value,
            'description' => $role->description(),
        ], self::cases());
    }
}
