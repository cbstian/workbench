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
|
| Groups render in order; a null label renders the group without a heading.
| `footer` items are pinned to the bottom of the sidebar.
|
*/

return [

    'groups' => [
        [
            'label' => null,
            'items' => [
                ['label' => 'Inicio', 'route' => 'dashboard', 'icon' => 'home'],
            ],
        ],
        [
            'label' => 'Espacio de trabajo',
            'items' => [
                ['label' => 'Proyectos', 'route' => 'projects.index', 'icon' => 'folder', 'active' => 'projects.*'],
            ],
        ],
    ],

    'footer' => [
        ['label' => 'Ajustes', 'route' => 'settings.profile', 'icon' => 'cog-6-tooth', 'active' => 'settings.*'],
    ],

];
