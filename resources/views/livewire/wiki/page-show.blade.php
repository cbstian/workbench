@php
    $project = $page->project;
    $wikiTitle = $project ? 'Wiki de '.$project->name : 'Wiki del espacio';
    $wikiUrl = $project ? route('projects.wiki', $project) : route('wiki.index');
    $breadcrumbs = $project
        ? [['label' => 'Proyectos', 'link' => route('projects.index')], ['label' => $project->name, 'link' => route('projects.show', $project)], ['label' => 'Wiki', 'link' => $wikiUrl]]
        : [['label' => 'Wiki del espacio', 'link' => $wikiUrl]];

    foreach ($this->ancestors as $ancestor) {
        $breadcrumbs[] = ['label' => $ancestor->title, 'link' => route('pages.show', $ancestor)];
    }

    $breadcrumbs[] = ['label' => $page->title];
@endphp

<x-ui.page>
    <x-ui.page-header :title="$page->title" :icon="$page->icon ?? '📄'" :breadcrumbs="$breadcrumbs"
                      :description="'Actualizada '.$page->updated_at->diffForHumans()">
        <x-slot:actions>
            @if ($page->is_visible_to_client)
                <x-badge text="Visible para el cliente" color="primary" light round />
            @endif
            @unless ($editing)
                <x-button icon="trash" text="Papelera" sm flat color="secondary" wire:click="confirmDelete" />
                <x-button icon="pencil-square" text="Editar" sm wire:click="startEditing" />
            @endunless
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)] xl:grid-cols-[16rem_minmax(0,1fr)_18rem]">
        <aside class="min-w-0">
            @include('livewire.wiki.partials.sidebar', ['wikiUrl' => $wikiUrl, 'wikiTitle' => $wikiTitle, 'activeId' => $page->id])
        </aside>

        <div class="min-w-0 space-y-8">
            @if ($editing)
                <form wire:submit="save" class="space-y-5">
                    <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-4">
                        <x-ui.icon-picker wire:model="icon" />
                        <x-input label="Título *" wire:model="title" maxlength="255" />
                    </div>

                    <x-editor label="Contenido" wire:model="body" markdown min-height="18rem" max-height="70vh"
                              :toolbar="['style', 'bold', 'italic', 'strikethrough', 'unordered-list', 'ordered-list', 'blockquote', 'code', 'code-block', 'link', 'hr', 'undo', 'redo', 'fullscreen']" />

                    <x-ui.form-grid cols="2">
                        <x-select.styled label="Dentro de" wire:model="parentId" :options="$this->parentOptions($page->id)" searchable placeholder="Raíz de la wiki" />

                        <div class="pt-6">
                            <x-toggle label="Visible para el cliente" wire:model="isVisibleToClient" />
                            <p class="mt-1 text-xs text-ink-subtle">Se registra; por ahora el usuario cliente no ve nada.</p>
                        </div>

                        <div class="sm:col-span-2">
                            @include('livewire.wiki.partials.label-picker')
                        </div>
                    </x-ui.form-grid>

                    <x-ui.form-actions>
                        <x-button flat color="secondary" text="Cancelar" sm wire:click="cancelEditing" />
                        <x-button type="submit" text="Guardar página" sm loading="save" />
                    </x-ui.form-actions>
                </form>
            @else
                @if ($page->labels->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($page->labels as $pageLabel)
                            <a href="{{ $wikiUrl }}?label={{ $pageLabel->id }}" wire:navigate>
                                <x-app.color-tag :color="$pageLabel->color" :text="$pageLabel->name" class="hover:bg-hover" />
                            </a>
                        @endforeach
                    </div>
                @endif

                @if (filled($page->body))
                    <article class="wb-prose rounded-xl border border-line bg-surface px-6 py-5 shadow-xs sm:px-8 sm:py-7">{!! $page->bodyHtml() !!}</article>
                @else
                    <x-ui.empty-state icon="document-text" title="Esta página está vacía">
                        <x-button icon="pencil-square" text="Escribir" sm wire:click="startEditing" />
                    </x-ui.empty-state>
                @endif
            @endif

            @if ($editing || $this->subpages->isNotEmpty())
                <x-ui.section title="Subpáginas">
                    @if ($this->subpages->isNotEmpty())
                        <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                            @foreach ($this->subpages as $subpage)
                                <li wire:key="subpage-{{ $subpage->id }}">
                                    <a href="{{ route('pages.show', $subpage) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 hover:bg-hover">
                                        <span aria-hidden="true">{{ $subpage->icon ?? '📄' }}</span>
                                        <span class="min-w-0 flex-1 truncate text-sm text-ink">{{ $subpage->title }}</span>
                                        <span class="text-xs text-ink-subtle">{{ $subpage->updated_at->diffForHumans() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($editing)
                        <form wire:submit="addSubpage" class="flex items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <x-input wire:model="newSubpageTitle" placeholder="Nueva subpágina…" maxlength="255" />
                            </div>
                            <x-button type="submit" icon="plus" text="Agregar" sm loading="addSubpage" class="h-9 shrink-0" />
                        </form>
                    @endif
                </x-ui.section>
            @endif

            <livewire:wiki.page-files :page="$page" :editing="$editing" wire:key="page-files-{{ $page->id }}" />

            @unless ($editing)
                <livewire:comments.thread :commentable="$page" wire:key="page-comments-{{ $page->id }}" />
            @endunless
        </div>

        <aside class="min-w-0 lg:col-start-2 xl:col-start-auto">
            <x-ui.section title="Historial">
                @if ($this->activities->isEmpty())
                    <p class="text-sm text-ink-muted">Sin cambios registrados.</p>
                @else
                    <ol class="space-y-3 border-l border-line pl-4">
                        @foreach ($this->activities as $activity)
                            <li wire:key="page-activity-{{ $activity->id }}" class="relative text-sm">
                                <span class="absolute top-1.5 -left-[21px] size-2 rounded-full border border-line-strong bg-surface" aria-hidden="true"></span>
                                <p class="text-ink">
                                    <span class="font-medium">{{ $activity->user?->name ?? 'Sistema' }}</span>
                                    <span class="text-ink-muted">{{ $activity->description() }}</span>
                                </p>
                                <time datetime="{{ $activity->created_at->toIso8601String() }}" class="text-xs text-ink-subtle" title="{{ $activity->created_at->translatedFormat('j M Y, H:i') }}">
                                    {{ $activity->created_at->diffForHumans() }}
                                </time>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.section>
        </aside>
    </div>
</x-ui.page>
