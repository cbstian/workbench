<x-ui.page>
    <x-ui.page-header :title="$project->name"
                      :icon="$project->icon"
                      :description="$project->description"
                      :breadcrumbs="[
                          ['label' => 'Proyectos', 'link' => route('projects.index')],
                          ['label' => $project->name],
                      ]">
        <x-slot:actions>
            @if ($project->is_visible_to_client)
                <x-badge text="Visible para el cliente" color="primary" light round />
            @else
                <x-badge text="Oculto para el cliente" color="secondary" light round />
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.section title="Tareas">
        <x-ui.empty-state icon="check-circle" title="Aún no hay tareas" description="Las tareas, sus vistas (kanban, listado y calendario) y la wiki del proyecto se mostrarán aquí." />
    </x-ui.section>
</x-ui.page>
