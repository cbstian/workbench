@php
    $wikiTitle = $project ? 'Wiki de '.$project->name : 'Wiki del espacio';
    $wikiUrl = $project ? route('projects.wiki', $project) : route('wiki.index');
    $breadcrumbs = $project
        ? [['label' => 'Proyectos', 'link' => route('projects.index')], ['label' => $project->name, 'link' => route('projects.show', $project)], ['label' => 'Wiki']]
        : [['label' => 'Inicio', 'link' => route('dashboard')], ['label' => 'Wiki del espacio']];
@endphp

<x-ui.page>
    <x-ui.page-header :title="$wikiTitle" :icon="$project?->icon ?? '📚'" :breadcrumbs="$breadcrumbs"
                      :description="$project
                          ? 'Documentación del proyecto. La ven y editan su equipo y el Super admin.'
                          : 'Documentación del espacio de trabajo. Solo la ve y edita el Super admin.'">
        <x-slot:actions>
            <x-button icon="plus" text="Nueva página" sm wire:click="create" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="min-w-0">
            @include('livewire.wiki.partials.sidebar', ['wikiUrl' => $wikiUrl, 'wikiTitle' => $wikiTitle])
        </aside>

        <div class="min-w-0 space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="w-full max-w-sm">
                    <x-input icon="magnifying-glass" placeholder="Buscar en la wiki…" wire:model.live.debounce.400ms="search" />
                </div>

                @foreach ($this->labels as $wikiLabel)
                    <button type="button" wire:key="label-filter-{{ $wikiLabel->id }}" wire:click="filterByLabel({{ $wikiLabel->id }})" aria-pressed="{{ $label === $wikiLabel->id ? 'true' : 'false' }}">
                        <x-app.color-tag :color="$wikiLabel->color" :text="$wikiLabel->name" @class(['ring-2 ring-ring' => $label === $wikiLabel->id]) />
                    </button>
                @endforeach
            </div>

            @if ($this->results !== null)
                <x-ui.section title="Resultados">
                    @if ($this->results->isEmpty())
                        <x-ui.empty-state icon="magnifying-glass" title="No encontramos páginas con ese criterio." />
                    @else
                        <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                            @foreach ($this->results as $result)
                                <li wire:key="result-{{ $result->id }}">
                                    <a href="{{ route('pages.show', $result) }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 hover:bg-hover">
                                        <span aria-hidden="true">{{ $result->icon ?? '📄' }}</span>
                                        <span class="min-w-0 flex-1 truncate text-sm text-ink">{{ $result->title }}</span>
                                        @foreach ($result->labels as $resultLabel)
                                            <x-app.color-tag :color="$resultLabel->color" :text="$resultLabel->name" />
                                        @endforeach
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.section>
            @elseif ($this->tree === [])
                <x-ui.empty-state icon="book-open" title="La wiki está vacía" description="Crea la primera página; luego puedes agregarle subpáginas, archivos y etiquetas.">
                    <x-button icon="plus" text="Nueva página" sm wire:click="create" />
                </x-ui.empty-state>
            @else
                <x-ui.empty-state icon="document-text" title="Elige una página" description="Abre una página del árbol, búscala o fíltrala por etiqueta." />
            @endif
        </div>
    </div>

    <x-slide id="page-slide" wire="showForm" title="Nueva página" size="md">
        <form id="page-form" wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-[5rem_minmax(0,1fr)] gap-4">
                <x-input label="Icono" wire:model="icon" maxlength="32" />
                <x-input label="Título *" wire:model="title" maxlength="255" />
            </div>
            <x-select.styled label="Dentro de" wire:model="parentId" :options="$this->parentOptions()" searchable placeholder="Raíz de la wiki" />
        </form>

        <x-slot:footer>
            <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
            <x-button type="submit" form="page-form" text="Crear página" sm loading="save" />
        </x-slot:footer>
    </x-slide>
</x-ui.page>
