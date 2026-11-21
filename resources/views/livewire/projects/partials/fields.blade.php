{{--
    Fields of App\Livewire\Forms\ProjectForm, shared by the create slide and
    the project settings page. Expects $clientOptions.
--}}
<x-ui.form-grid cols="2">
    <div class="sm:col-span-2 grid grid-cols-[5rem_minmax(0,1fr)] gap-4">
        <x-input label="Icono" wire:model="form.icon" maxlength="32" hint="Un emoji." />
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
</x-ui.form-grid>
