<?php

/*
|--------------------------------------------------------------------------
| Panel navigation
|--------------------------------------------------------------------------
|
| Single source of truth for the sidebar. A new module adds its entry here
| instead of editing the sidebar markup (docs/arquitectura/interfaz.md).
|
| Item keys:
| - label:  visible text, in Spanish.
| - route:  route name the item links to.
| - icon:   Heroicons (outline) name rendered with TallStackUI <x-icon>.
| - active: route name pattern(s) that highlight the item (default: route).
| - can:    optional ability; the item is hidden when the user lacks it.
| - children: optional Livewire component name that renders the item's
|           sub-list (e.g. each project); the item gets an expand chevron.
|
| Groups render in order; a null label renders the group without a heading.
| Labelled groups and items with children collapse, and the sidebar remembers
| their state per browser.
| `footer` items are pinned to the bottom of the sidebar.
|
*/

return [

    'groups' => [
        [
            'label' => null,
            'items' => [
                ['label' => 'Inicio', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Pizarra', 'route' => 'board.index', 'icon' => 'view-columns', 'can' => 'view-projects'],
                ['label' => 'Tareas', 'route' => 'tasks.index', 'icon' => 'rectangle-stack', 'can' => 'view-projects'],
            ],
        ],
        [
            'label' => 'Administración',
            'items' => [
                ['label' => 'Clientes', 'route' => 'clients.index', 'icon' => 'building-office', 'can' => 'manage-workspace'],
                ['label' => 'Usuarios', 'route' => 'users.index', 'icon' => 'users', 'can' => 'view-members'],
                ['label' => 'Papelera', 'route' => 'trash.index', 'icon' => 'trash', 'can' => 'view-trash'],
                ['label' => 'Espacios de trabajo', 'route' => 'workspaces.index', 'icon' => 'squares-2x2', 'can' => 'manage-account'],
                ['label' => 'Roles y permisos', 'route' => 'roles.index', 'icon' => 'shield-check', 'can' => 'manage-account'],
            ],
        ],
        [
            'label' => 'Espacio de trabajo',
            'items' => [
                [
                    'label' => 'Proyectos',
                    'route' => 'projects.index',
                    'icon' => 'folder',
                    'active' => 'projects.index',
                    'can' => 'view-projects',
                    'children' => 'navigation.project-links',
                ],
                ['label' => 'Wiki del espacio', 'route' => 'wiki.index', 'icon' => 'book-open', 'active' => 'wiki.index', 'can' => 'manage-workspace'],
            ],
        ],
    ],

    'footer' => [
        ['label' => 'Ajustes', 'route' => 'settings.profile', 'icon' => 'cog-6-tooth', 'active' => 'settings.*'],
    ],

];
