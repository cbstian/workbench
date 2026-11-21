<div>
    <x-ui.section title="Adjuntos">
        <div class="space-y-3">
            @if ($this->attachments->isNotEmpty())
                <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                    @foreach ($this->attachments as $attachment)
                        <li wire:key="attachment-{{ $attachment->id }}" class="flex items-center gap-3 px-3 py-2">
                            <x-icon icon="paper-clip" class="size-4 shrink-0 text-ink-subtle" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('attachments.show', $attachment) }}" class="block truncate text-sm text-ink hover:underline">{{ $attachment->original_name }}</a>
                                <p class="truncate text-xs text-ink-subtle">
                                    {{ $attachment->humanSize() }} · {{ $attachment->uploader?->name ?? '—' }} · {{ $attachment->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <x-button.circle icon="arrow-down-tray" sm flat color="secondary" :href="route('attachments.show', $attachment)" :aria-label="'Descargar '.$attachment->original_name" />
                            @if ($this->canDelete($attachment))
                                <x-button.circle icon="trash" sm flat color="secondary" wire:click="delete({{ $attachment->id }})"
                                                 wire:confirm="¿Enviar «{{ $attachment->original_name }}» a la papelera?"
                                                 :aria-label="'Eliminar '.$attachment->original_name" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            @elseif (! $this->canAttach)
                <p class="text-sm text-ink-muted">Sin adjuntos.</p>
            @endif

            @if ($this->canAttach)
                <x-upload wire:model="uploads" multiple :preview="false" close-after-upload
                          placeholder="Adjuntar archivos…" tip="Hasta 10 archivos de 10 MB cada uno" />
            @endif
        </div>
    </x-ui.section>
</div>
