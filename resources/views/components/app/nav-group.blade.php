{{--
    A group of sidebar links with an optional, collapsible heading. Items follow
    the shape documented in config/navigation.php; items whose `can` ability the
    user lacks, or whose route does not exist, are skipped.

    - A group with a label collapses from its heading (Notion-like).
    - An item with `children` (a Livewire component name) shows a chevron that
      expands or collapses its sub-list (e.g. Proyectos → each project).
    - Open/closed states are remembered per browser with Alpine's $persist.

    Props:
    - label: group heading (null renders no heading and no collapse).
    - items: list of navigation items.
--}}
@props([
    'label' => null,
    'items' => [],
])

@php
    $visibleItems = collect($items)->filter(
        fn (array $item): bool => \Illuminate\Support\Facades\Route::has($item['route'])
            && (! isset($item['can']) || auth()->user()?->can($item['can']))
    );
    $groupKey = 'wb-nav-group-'.str($label ?? 'main')->slug();
@endphp

@if ($visibleItems->isNotEmpty())
    <div {{ $attributes->class('space-y-0.5') }}
         @if ($label) x-data="{ open: $persist(true).as(@js($groupKey)) }" @endif>
        @if ($label)
            <button type="button"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open"
                    class="group/heading flex h-6 w-full items-center gap-1 rounded-md px-2 text-left text-xs font-medium text-ink-subtle transition-colors hover:bg-hover hover:text-ink-muted">
                <span class="truncate">{{ $label }}</span>
                <x-icon icon="chevron-right"
                        class="size-3 shrink-0 opacity-0 transition group-hover/heading:opacity-100 group-focus-visible/heading:opacity-100"
                        x-bind:class="open && 'rotate-90'" />
            </button>
        @endif

        <div class="space-y-0.5" @if ($label) x-show="open" x-collapse @endif>
            @foreach ($visibleItems as $item)
                @php($isActive = request()->routeIs(...(array) ($item['active'] ?? $item['route'])))

                @if (isset($item['children']))
                    <div x-data="{ expanded: $persist(true).as(@js('wb-nav-item-'.$item['route'])) }">
                        <div class="group/item relative">
                            <x-app.nav-item :href="route($item['route'])" :icon="$item['icon'] ?? null" :active="$isActive" class="pr-8">
                                {{ $item['label'] }}
                            </x-app.nav-item>

                            <button type="button"
                                    x-on:click="expanded = ! expanded"
                                    x-bind:aria-expanded="expanded"
                                    x-bind:aria-label="expanded ? @js('Contraer '.$item['label']) : @js('Expandir '.$item['label'])"
                                    class="absolute inset-y-0 right-1 my-auto inline-flex size-6 items-center justify-center rounded text-ink-subtle transition hover:bg-hover hover:text-ink">
                                <x-icon icon="chevron-right" class="size-3.5 transition-transform" x-bind:class="expanded && 'rotate-90'" />
                            </button>
                        </div>

                        <div x-show="expanded" x-collapse class="mt-0.5 ml-[17px] border-l border-line pl-1.5">
                            <livewire:is :component="$item['children']" />
                        </div>
                    </div>
                @else
                    <x-app.nav-item :href="route($item['route'])" :icon="$item['icon'] ?? null" :active="$isActive">
                        {{ $item['label'] }}
                    </x-app.nav-item>
                @endif
            @endforeach
        </div>
    </div>
@endif
