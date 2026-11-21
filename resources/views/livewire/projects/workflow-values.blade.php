<div>
    <x-ui.form-section :title="$this->isStatuses() ? 'Estados' : 'Prioridades'"
                       :description="$this->isStatuses()
                           ? 'Etapas de avance de las tareas, en el orden del kanban. La categoría indica si una tarea queda abierta o cerrada.'
                           : 'Niveles de urgencia, de menor a mayor.'">
        <div class="space-y-3">
            <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                @foreach ($this->values as $value)
                    <li wire:key="{{ $type }}-{{ $value->id }}" class="flex items-center gap-3 px-3 py-2">
                        <div class="flex flex-col">
                            <button type="button" wire:click="move({{ $value->id }}, -1)" @disabled($loop->first)
                                    class="text-ink-subtle hover:text-ink disabled:opacity-30" aria-label="Subir «{{ $value->name }}»">
                                <x-icon icon="chevron-up" class="size-3.5" />
                            </button>
                            <button type="button" wire:click="move({{ $value->id }}, 1)" @disabled($loop->last)
                                    class="text-ink-subtle hover:text-ink disabled:opacity-30" aria-label="Bajar «{{ $value->name }}»">
                                <x-icon icon="chevron-down" class="size-3.5" />
                            </button>
                        </div>

                        <span class="size-2.5 shrink-0 rounded-full bg-ink-subtle" @if ($value->color) style="background-color: {{ $value->color }}" @endif aria-hidden="true"></span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-ink first-letter:uppercase">{{ $value->name }}</p>
                            @if ($this->isStatuses())
                                <p class="text-xs text-ink-subtle">{{ $value->category->label() }}</p>
                            @endif
                        </div>

                        @if ($value->is_initial)
                            <x-badge text="Inicial" color="primary" light round />
                        @endif

                        <x-dropdown icon="ellipsis-horizontal" static>
                            <x-dropdown.items icon="pencil-square" text="Editar" wire:click="edit({{ $value->id }})" />
                            @unless ($value->is_initial)
                                <x-dropdown.items icon="star" text="Usar como inicial" wire:click="makeInitial({{ $value->id }})" />
                                <x-dropdown.items icon="trash" text="Eliminar" separator wire:click="confirmDelete({{ $value->id }})" />
                            @endunless
                        </x-dropdown>
                    </li>
                @endforeach
            </ul>

            <x-button icon="plus" :text="$this->isStatuses() ? 'Agregar estado' : 'Agregar prioridad'" sm flat wire:click="create" />
        </div>
    </x-ui.form-section>

    <x-slide id="{{ $type }}-slide" wire="showForm" size="md"
             :title="($editingId ? 'Editar ' : 'Nuevo ').($this->isStatuses() ? 'estado' : 'prioridad')">
        <form id="{{ $type }}-form" wire:submit="save" class="space-y-5">
            <x-input label="Nombre *" wire:model="name" maxlength="64" />
            @if ($this->isStatuses())
                <x-select.styled label="Categoría *" wire:model="category" :options="$this->categoryOptions" required
                                 hint="Completada y Cancelada cierran la tarea." />
            @endif
            <x-color label="Color" wire:model="color" picker clearable />
        </form>

        <x-slot:footer>
            <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
            <x-button type="submit" form="{{ $type }}-form" text="Guardar" sm loading="save" />
        </x-slot:footer>
    </x-slide>
</div>
