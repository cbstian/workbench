{{--
    Initials avatar in the brand tone (no external services like Gravatar).
--}}
@props([
    'name',
])

@php($initials = str($name)->explode(' ')->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''))

<span {{ $attributes->class('inline-flex size-7 items-center justify-center rounded-md bg-primary-100 text-xs font-semibold text-primary-800 uppercase dark:bg-primary-900 dark:text-primary-200') }}
      title="{{ $name }}">
    {{ $initials }}
</span>
