{{--
    Fields of App\Livewire\Forms\ProjectForm, shared by the create slide and
    the project settings page. Expects $clientOptions.
--}}
<x-ui.form-grid cols="2">
    <div class="sm:col-span-2 grid grid-cols-[auto_minmax(0,1fr)] gap-4">
        <x-ui.icon-picker wire:model="form.icon" placeholder="📁" />
        <x-input label="Nombre *" wire:model="form.name" />
    </div>

    <div class="sm:col-span-2">
        <x-textarea label="Descripción" wire:model="form.description" rows="3" />
    </div>

    <x-select.styled label="Cliente" wire:model="form.client_id" :options="$clientOptions"
                     searchable :placeholder="'Sin cliente'"
                     hint="Opcional: un proyecto puede no tener cliente." />

    <div class="pt-6">
        <x-toggle label="Visible para el cliente" wire:model="form.is_visible_to_client" />
        <p class="mt-1 text-xs text-ink-subtle">Se registra; por ahora el usuario cliente no ve nada.</p>
    </div>

    <x-select.styled label="Prioridad" wire:model="form.priority"
                     :options="\App\Enums\ProjectPriorityLevel::options()"
                     hint="Comparable entre proyectos. No es la prioridad de las tareas." />

    <x-input label="Término" type="date" wire:model="form.due_on"
             hint="Si ya pasó y el proyecto no está en pausa, queda vencido en la pizarra." />

    <div class="sm:col-span-2">
        <x-toggle label="En pausa" wire:model="form.is_paused" />
        <p class="mt-1 text-xs text-ink-subtle">Se puede reanudar. No archiva ni envía a la papelera. En la pizarra la fila queda rayada.</p>
    </div>
</x-ui.form-grid>
