{{--
    Client logo, or its initial over the client's color when it has no logo.

    Props:
    - client: App\Models\Client.
    - size:   sm | md (default) | lg.
--}}
@props([
    'client',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-6 text-[11px]',
        'lg' => 'size-12 text-lg',
        default => 'size-9 text-sm',
    };
@endphp

@if ($client->logoUrl())
    <img src="{{ $client->logoUrl() }}" alt="Logo de {{ $client->name }}"
         {{ $attributes->class([$sizeClasses, 'shrink-0 rounded-md border border-line bg-surface object-contain']) }} />
@else
    <span {{ $attributes->class([$sizeClasses, 'flex shrink-0 items-center justify-center rounded-md border border-line bg-hover font-semibold text-ink']) }}
          @if ($client->color) style="background-color: {{ $client->color }}; color: #fff; border-color: transparent" @endif
          aria-hidden="true">
        {{ str($client->name)->substr(0, 1)->upper() }}
    </span>
@endif
