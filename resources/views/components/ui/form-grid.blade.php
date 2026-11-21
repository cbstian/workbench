{{--
    Responsive grid for form fields. Fields stack on mobile and spread into
    `cols` columns from the sm breakpoint. To make a field wider, wrap it in
    <div class="sm:col-span-2"> (or sm:col-span-full); TallStackUI passes a
    `class` given to the field to the inner <input>, not to its wrapper.

    Props:
    - cols: 1 | 2 (default) | 3.
--}}
@props([
    'cols' => 2,
])

<div {{ $attributes->class([
    'grid grid-cols-1 gap-x-4 gap-y-5',
    'sm:grid-cols-2' => (int) $cols === 2,
    'sm:grid-cols-2 lg:grid-cols-3' => (int) $cols === 3,
]) }}>
    {{ $slot }}
</div>
