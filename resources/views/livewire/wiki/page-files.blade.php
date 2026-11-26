<div>
    @if ($editing || $this->files->isNotEmpty())
    <x-ui.section title="Archivos">
        <div class="space-y-3">
            @if ($this->files->isNotEmpty())
                <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                    @foreach ($this->files as $file)
                        <li wire:key="page-file-{{ $file->id }}" class="flex items-center gap-3 px-3 py-2">
                            <x-icon icon="document" class="size-4 shrink-0 text-ink-subtle" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('page-files.show', $file) }}" class="block truncate text-sm text-ink hover:underline">{{ $file->original_name }}</a>
                                <p class="truncate text-xs text-ink-subtle">{{ $file->humanSize() }} · {{ $file->uploader?->name ?? '—' }} · {{ $file->created_at->diffForHumans() }}</p>
                            </div>
                            <x-button.circle icon="arrow-down-tray" sm flat color="secondary" :href="route('page-files.show', $file)" :aria-label="'Descargar '.$file->original_name" />
                            @if ($editing)
                                <x-button.circle icon="trash" sm flat color="secondary" wire:click="delete({{ $file->id }})"
                                                 wire:confirm="¿Enviar «{{ $file->original_name }}» a la papelera?"
                                                 :aria-label="'Eliminar '.$file->original_name" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($editing)
                <x-upload wire:model="uploads" multiple :preview="false" close-after-upload
                          placeholder="Subir archivos a esta página…" tip="Hasta 10 archivos de 10 MB cada uno" />
            @endif
        </div>
    </x-ui.section>
    @endif
</div>
