<x-ui.page>
    <x-ui.page-header title="Papelera"
                      :description="'Todo lo que se borró en '.$this->workspace->name.'. Puedes restaurarlo; por ahora la papelera no se vacía.'" />

    <div class="max-w-xs">
        <x-select.styled wire:model.live="type" :options="$this->typeOptions" placeholder="Todos los tipos" />
    </div>

    @if ($this->items->isEmpty())
        <x-ui.empty-state icon="trash" title="La papelera está vacía" description="Lo que borres en este espacio aparecerá aquí." />
    @else
        <div class="overflow-x-auto rounded-lg border border-line bg-surface">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs text-ink-muted">
                        <th class="px-4 py-2.5 font-medium">Elemento</th>
                        <th class="px-4 py-2.5 font-medium">Tipo</th>
                        <th class="px-4 py-2.5 font-medium">Proyecto</th>
                        <th class="px-4 py-2.5 font-medium">Eliminado</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($this->items as $item)
                        <tr wire:key="trash-{{ $item->type }}-{{ $item->id }}">
                            <td class="max-w-xs px-4 py-3"><p class="truncate font-medium text-ink">{{ $item->title }}</p></td>
                            <td class="px-4 py-3"><x-badge :text="\App\Livewire\Trash\Index::TYPE_LABELS[$item->type] ?? $item->type" color="secondary" light round /></td>
                            <td class="px-4 py-3 text-ink-muted">{{ $item->type === 'project' ? '—' : ($this->projectNames[$item->project_id] ?? '—') }}</td>
                            <td class="px-4 py-3 text-ink-muted">
                                <time datetime="{{ $item->deleted_at->toIso8601String() }}">{{ $item->deleted_at->diffForHumans() }}</time>
                                @if ($item->deleter)
                                    <span class="text-ink-subtle">· {{ $item->deleter->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-button text="Restaurar" icon="arrow-uturn-left" sm flat
                                          wire:click="restore('{{ $item->type }}', {{ $item->id }})"
                                          loading="restore('{{ $item->type }}', {{ $item->id }})" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $this->items->links() }}
    @endif
</x-ui.page>
