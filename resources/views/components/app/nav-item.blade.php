{{--
    Sidebar link.

    Props:
    - href:   destination URL (use route()).
    - icon:   Heroicons name rendered through TallStackUI <x-icon>.
    - active: highlights the item for the current section.
--}}
@props([
    'href',
    'icon' => null,
    'active' => false,
])

<a href="{{ $href }}"
   wire:navigate
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       'group flex h-8 items-center gap-2.5 rounded-md px-2 text-sm transition-colors',
       'bg-active font-medium text-ink' => $active,
       'text-ink-muted hover:bg-hover hover:text-ink' => ! $active,
   ]) }}>
    @if ($icon)
        <x-icon :icon="$icon" @class(['size-[18px] shrink-0', 'text-ink' => $active, 'text-ink-subtle group-hover:text-ink-muted' => ! $active]) />
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
