@php
    $project = $page->project;
    $breadcrumbs = [['label' => 'Inicio', 'link' => route('portal.index')]];

    if ($project) {
        $breadcrumbs[] = ['label' => $project->name, 'link' => route('portal.projects.show', ['project' => $project, 'vista' => \App\Livewire\Portal\ProjectShow::DOCS])];
    } else {
        $breadcrumbs[] = ['label' => 'Documentación', 'link' => route('portal.index').'#documentacion'];
    }

    foreach ($this->ancestors as $ancestor) {
        $breadcrumbs[] = ['label' => $ancestor->title, 'link' => route('portal.pages.show', $ancestor)];
    }

    $breadcrumbs[] = ['label' => $page->title];
@endphp

<x-ui.page>
    <div class="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="min-w-0 lg:sticky lg:top-16 lg:self-start">
            <x-ui.section :title="$project ? 'Documentación de '.$project->name : 'Documentación'">
                @include('livewire.portal.partials.page-tree', ['pages' => $this->tree, 'activeId' => $page->id])
            </x-ui.section>
        </aside>

        <div class="min-w-0 space-y-8">
            <x-ui.page-header :title="$page->title" :icon="$page->icon ?? '📄'" :breadcrumbs="$breadcrumbs"
                              :description="'Actualizada '.$page->updated_at->diffForHumans().($page->updater ? ' por '.$page->updater->name : '')" />

            <div class="mx-auto max-w-3xl space-y-8 lg:mx-0">
                @if (filled($page->body))
                    <article class="wb-prose">{!! $page->bodyHtml() !!}</article>
                @else
                    <p class="text-sm text-ink-muted">Esta página no tiene contenido todavía.</p>
                @endif

                @if ($this->children->isNotEmpty())
                    <x-ui.section title="Subpáginas">
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($this->children as $child)
                                <a href="{{ route('portal.pages.show', $child) }}" wire:navigate wire:key="portal-child-{{ $child->id }}"
                                   class="flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink transition-colors hover:bg-hover">
                                    <span aria-hidden="true">{{ $child->icon ?? '📄' }}</span>
                                    <span class="truncate">{{ $child->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-ui.section>
                @endif

                <livewire:comments.thread :commentable="$page" :thread="\App\Enums\CommentThread::Client"
                                          wire:key="portal-page-thread-{{ $page->id }}" />
            </div>
        </div>
    </div>
</x-ui.page>
