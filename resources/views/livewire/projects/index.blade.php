<div class="space-y-8">
    <x-ui.page-header title="Proyectos" description="Todos los proyectos del espacio de trabajo." />

    <x-table :headers="$this->headers"
             :rows="$this->rows"
             :sort="$sort"
             :quantity="\App\Livewire\Projects\Index::PAGE_SIZES"
             :placeholders="['search' => 'Buscar proyectos…', 'empty' => 'No encontramos proyectos con ese criterio.']"
             filter
             paginate
             loading
             striped>
        @interact('column_name', $row)
            <div class="flex items-center gap-2.5">
                <span class="text-base leading-none" aria-hidden="true">{{ $row->icon }}</span>
                <span class="font-medium text-ink">{{ $row->name }}</span>
            </div>
        @endinteract

        @interact('column_description', $row)
            <span class="block max-w-72 truncate text-ink-muted">{{ $row->description ?? '—' }}</span>
        @endinteract

        @interact('column_is_visible_to_client', $row)
            @if ($row->is_visible_to_client)
                <x-badge text="Visible" color="primary" light round />
            @else
                <x-badge text="Oculto" color="secondary" light round />
            @endif
        @endinteract

        @interact('column_updated_at', $row)
            <time datetime="{{ $row->updated_at->toIso8601String() }}" class="whitespace-nowrap text-ink-muted">
                {{ $row->updated_at->translatedFormat('j M Y') }}
            </time>
        @endinteract
    </x-table>
</div>
