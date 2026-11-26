{{--
    Wiki pages visible to the client, indented by depth.
    Variables: $pages (with `depth`), $activeId (page open, or null).
--}}
<ul class="space-y-0.5">
    @foreach ($pages as $page)
        <li wire:key="portal-page-{{ $page->id }}">
            <a href="{{ route('portal.pages.show', $page) }}" wire:navigate
               style="padding-left: {{ 0.5 + $page->depth * 1 }}rem"
               @class([
                   'flex h-8 items-center gap-2 rounded-md pr-2 text-sm transition-colors',
                   'bg-active font-medium text-ink' => $activeId === $page->id,
                   'text-ink-muted hover:bg-hover hover:text-ink' => $activeId !== $page->id,
               ])>
                <span class="shrink-0" aria-hidden="true">{{ $page->icon ?? '📄' }}</span>
                <span class="truncate">{{ $page->title }}</span>
            </a>
        </li>
    @endforeach
</ul>
