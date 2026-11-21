{{--
    Wiki sidebar: link to the wiki home and the page tree.
    Expects $wikiUrl, $wikiTitle, the component's $this->tree and an optional $activeId.
--}}
<nav class="space-y-2 lg:sticky lg:top-16" aria-label="Páginas de la wiki">
    <a href="{{ $wikiUrl }}" wire:navigate class="flex items-center gap-2 px-2 text-xs font-medium text-ink-subtle hover:text-ink">
        <x-icon icon="book-open" class="size-4" />
        {{ $wikiTitle }}
    </a>

    @if ($this->tree === [])
        <p class="px-2 text-sm text-ink-muted">Sin páginas todavía.</p>
    @else
        <div class="scrollbar-subtle max-h-[70vh] overflow-y-auto pr-1">
            <x-wiki.tree :nodes="$this->tree" :active-id="$activeId ?? null" />
        </div>
    @endif
</nav>
