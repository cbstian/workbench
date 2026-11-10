{{--
    "workbench" wordmark set in Space Grotesk (the brand typeface).
    Inherits color and size from its context: <x-brand.wordmark class="text-7xl" />
--}}
<span {{ $attributes->class('font-display font-semibold tracking-tight lowercase') }}>{{ config('app.name') }}</span>
