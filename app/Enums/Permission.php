<?php

namespace App\Enums;

/**
 * Permissions granted to workspace roles through spatie/laravel-permission
 * (docs/permisos.md). Roles are global and assigned per workspace (team).
 *
 * The super admin does not need them: owning the workspace's account gives
 * every ability (see App\Policies). What only the super admin may do is listed
 * in SUPER_ADMIN_ONLY and is never granted to a role.
 */
enum Permission: string
{
    case ProjectsUpdate = 'projects.update';
    case ProjectsConfigure = 'projects.configure';
    case MembersCreate = 'members.create';
    case TasksView = 'tasks.view';
    case TasksCreate = 'tasks.create';
    case TasksUpdateAny = 'tasks.update-any';
    case TasksUpdateAssigned = 'tasks.update-assigned';
    case ProjectWiki = 'wiki.project';
    case ProjectTrash = 'trash.project';
    case BoardUpdate = 'board.update';

    /**
     * Abilities reserved to the super admin (RN-14, RN-24, RN-25, RN-26).
     *
     * @var list<string>
     */
    public const array SUPER_ADMIN_ONLY = [
        'Crear, renombrar y eliminar espacios de trabajo',
        'Crear, editar y eliminar clientes',
        'Crear y eliminar proyectos',
        'Crear admins, miembros y usuarios cliente',
        'Agregar usuarios existentes a un espacio o proyecto',
        'Desactivar, eliminar y restaurar usuarios',
        'Ver y editar la wiki del espacio de trabajo',
        'Ver y restaurar toda la papelera del espacio',
    ];

    /**
     * Spanish description shown in the roles and permissions page.
     */
    public function label(): string
    {
        return match ($this) {
            self::ProjectsUpdate => 'Editar los datos de sus proyectos',
            self::ProjectsConfigure => 'Configurar estados, prioridades y etiquetas de sus proyectos',
            self::MembersCreate => 'Crear miembros nuevos con acceso a sus proyectos',
            self::TasksView => 'Ver todas las tareas de sus proyectos',
            self::TasksCreate => 'Crear tareas',
            self::TasksUpdateAny => 'Editar cualquier tarea de sus proyectos',
            self::TasksUpdateAssigned => 'Editar las tareas que tiene asignadas',
            self::ProjectWiki => 'Ver y editar la wiki de sus proyectos',
            self::ProjectTrash => 'Ver y restaurar la papelera de sus proyectos',
            self::BoardUpdate => 'Crear y editar notas en la pizarra de sus proyectos',
        };
    }
}
