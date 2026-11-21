{{--
    A group of sidebar links with an optional heading. Items follow the shape
    documented in config/navigation.php; items whose `can` ability the user
    lacks, or whose route does not exist, are skipped.

    Props:
    - label: group heading (null renders no heading).
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
@endphp

@if ($visibleItems->isNotEmpty())
    <div {{ $attributes->class('space-y-0.5') }}>
        @if ($label)
            <p class="px-2 pb-1 text-xs font-medium text-ink-subtle">{{ $label }}</p>
        @endif

        @foreach ($visibleItems as $item)
            <x-app.nav-item :href="route($item['route'])"
                            :icon="$item['icon'] ?? null"
                            :active="request()->routeIs(...(array) ($item['active'] ?? $item['route']))">
                {{ $item['label'] }}
            </x-app.nav-item>
        @endforeach
    </div>
@endif
