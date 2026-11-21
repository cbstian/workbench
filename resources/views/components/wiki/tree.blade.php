{{--
    Recursive page tree of a wiki.

    Props:
    - nodes:    list of ['page' => Page, 'children' => [...]] (App\Livewire\Concerns\WithWikiTree).
    - activeId: id of the open page, highlighted.
    - depth:    nesting level (internal).
--}}
@props([
    'nodes' => [],
    'activeId' => null,
    'depth' => 0,
])

<ul @class(['space-y-0.5', 'ml-3 border-l border-line pl-1.5' => $depth > 0])>
    @foreach ($nodes as $node)
        <li wire:key="tree-{{ $node['page']->id }}">
            <a href="{{ route('pages.show', $node['page']) }}" wire:navigate
               @if ($activeId === $node['page']->id) aria-current="page" @endif
               @class([
                   'flex h-7 min-w-0 items-center gap-2 rounded-md px-2 text-sm transition-colors',
                   'bg-active font-medium text-ink' => $activeId === $node['page']->id,
                   'text-ink-muted hover:bg-hover hover:text-ink' => $activeId !== $node['page']->id,
               ])>
                <span class="w-4 shrink-0 text-center text-[13px] leading-none" aria-hidden="true">{{ $node['page']->icon ?? '📄' }}</span>
                <span class="truncate">{{ $node['page']->title }}</span>
            </a>

            @if ($node['children'] !== [])
                <x-wiki.tree :nodes="$node['children']" :active-id="$activeId" :depth="$depth + 1" />
            @endif
        </li>
    @endforeach
</ul>
