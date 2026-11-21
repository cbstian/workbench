{{--
    Footer row of a form: secondary actions first, the primary (submit) last,
    aligned to the right. Put a status message in the `status` slot.

    Usage:
        <x-ui.form-actions>
            <x-button flat color="secondary" text="Cancelar" sm />
            <x-button type="submit" text="Guardar" sm loading="save" />
        </x-ui.form-actions>
--}}
<div {{ $attributes->class('flex flex-wrap items-center justify-end gap-2') }}>
    @isset($status)
        <div class="mr-auto text-sm text-ink-muted">{{ $status }}</div>
    @endisset

    {{ $slot }}
</div>
