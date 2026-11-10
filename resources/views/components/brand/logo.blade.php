{{--
    Full logo: isotype + wordmark. Use this anywhere the brand appears in the UI.

    Props:
    - markClass: classes for the isotype size (default "size-6").
--}}
@props([
    'markClass' => 'size-6',
])

<span {{ $attributes->class('inline-flex items-center gap-2 text-ink') }}>
    <x-brand.mark :class="$markClass" />
    <x-brand.wordmark />
</span>
