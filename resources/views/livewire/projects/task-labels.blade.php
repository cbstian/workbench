<div>
    <x-ui.form-section title="Etiquetas" description="Clasifican las tareas de este proyecto. No hay etiquetas por defecto.">
        <div class="space-y-3">
            @if ($this->labels->isEmpty())
                <p class="text-sm text-ink-muted">Este proyecto aún no tiene etiquetas.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->labels as $label)
                        <span wire:key="label-{{ $label->id }}" class="inline-flex items-center gap-1">
                            <button type="button" wire:click="edit({{ $label->id }})" title="Editar «{{ $label->name }}»">
                                <x-app.color-tag :color="$label->color" :text="$label->name" class="hover:bg-hover" />
                            </button>
                            <button type="button" wire:click="delete({{ $label->id }})"
                                    class="inline-flex size-6 items-center justify-center rounded text-ink-subtle hover:bg-hover hover:text-ink"
                                    aria-label="Eliminar «{{ $label->name }}»">
                                <x-icon icon="x-mark" class="size-3.5" />
                            </button>
                        </span>
                    @endforeach
                </div>
            @endif

            <x-button icon="plus" text="Agregar etiqueta" sm flat wire:click="create" />
        </div>
    </x-ui.form-section>

    <x-slide id="labels-slide" wire="showForm" size="md" :title="$editingId ? 'Editar etiqueta' : 'Nueva etiqueta'">
        <form id="labels-form" wire:submit="save" class="space-y-5">
            <x-input label="Nombre *" wire:model="name" maxlength="64" />
            <x-color label="Color" wire:model="color" picker clearable />
        </form>

        <x-slot:footer>
            <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
            <x-button type="submit" form="labels-form" text="Guardar" sm loading="save" />
        </x-slot:footer>
    </x-slide>
</div>
