{{--
    Fields of App\Livewire\Forms\TaskForm. Expects a component using
    App\Livewire\Concerns\WithTaskOptions and an optional $readonly flag.
--}}
@php($readonly = $readonly ?? false)

<div class="space-y-5">
    <x-input label="Nombre *" wire:model="form.name" maxlength="255" :readonly="$readonly" />
    <x-textarea label="Descripción" wire:model="form.description" rows="5" :readonly="$readonly" />

    <x-ui.form-grid cols="2">
        <x-select.styled label="Estado *" wire:model="form.status_id" :options="$this->statusOptions" required :disabled="$readonly" />
        <x-select.styled label="Prioridad *" wire:model="form.priority_id" :options="$this->priorityOptions" required :disabled="$readonly" />
        <x-input label="Inicio" type="date" wire:model="form.starts_on" :readonly="$readonly" />
        <x-input label="Término" type="date" wire:model="form.ends_on" :readonly="$readonly" />
    </x-ui.form-grid>

    <x-select.styled label="Asignados" wire:model="form.assignee_ids" :options="$this->assigneeOptions"
                     multiple searchable :disabled="$readonly"
                     hint="Integrantes del equipo del proyecto." />

    <x-select.styled label="Etiquetas" wire:model="form.label_ids" :options="$this->labelOptions"
                     multiple searchable :disabled="$readonly"
                     :hint="$this->labelOptions === [] ? 'Este proyecto aún no tiene etiquetas.' : null" />
</div>
