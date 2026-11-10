{{--
    Compact light/dark toggle (icon button) built on TallStackUI's theme switch.
    The full light/dark/system selector lives in the user menu.
--}}
<div {{ $attributes->class('inline-flex size-8 items-center justify-center rounded-md transition-colors hover:bg-hover') }}>
    <x-theme-switch simple only-icons sm />
</div>
