{{--
    Root wrapper of every panel page. Sets the width and the vertical rhythm
    between the header and the sections, so all pages line up.

    Props:
    - width: full (default; lists, boards, dashboards — uses the whole screen)
             | wide (max-w-7xl; detail pages with side panels)
             | narrow (max-w-3xl; forms, settings and reading pages like the wiki)

    Usage:
        <x-ui.page>
            <x-ui.page-header title="Proyectos" />
            <x-ui.section title="…">…</x-ui.section>
        </x-ui.page>
--}}
@props([
    'width' => 'full',
])

<div {{ $attributes->class([
    'w-full space-y-8',
    'mx-auto max-w-7xl' => $width === 'wide',
    'mx-auto max-w-3xl' => $width === 'narrow',
]) }}>
    {{ $slot }}
</div>
