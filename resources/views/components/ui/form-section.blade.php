{{--
    One block of a form: title and description on the left, fields on the
    right (stacked on mobile). Several sections in a row form a settings-style
    page; each section may be its own <form> with its own save button.

    Props:
    - title:       section title.
    - description: optional explanation of what the fields affect.

    Slots:
    - default: the fields (TallStackUI <x-input>, <x-select.styled>, <x-textarea>…),
               usually inside <x-ui.form-grid>.
    - actions: optional footer (see <x-ui.form-actions>).

    Usage:
        <form wire:submit="saveProfile">
            <x-ui.form-section title="Perfil" description="…">
                <x-ui.form-grid>
                    <x-input label="Nombre *" wire:model="profile.name" />
                </x-ui.form-grid>
                <x-slot:actions>
                    <x-ui.form-actions><x-button type="submit" text="Guardar" sm loading="saveProfile" /></x-ui.form-actions>
                </x-slot:actions>
            </x-ui.form-section>
        </form>
--}}
@props([
    'title',
    'description' => null,
])

<section {{ $attributes->class('grid gap-x-10 gap-y-4 border-b border-line py-8 first:pt-0 last:border-b-0 lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)]') }}>
    <div class="space-y-1">
        <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
        @if ($description)
            <p class="text-sm text-ink-muted">{{ $description }}</p>
        @endif
    </div>

    <div class="min-w-0 space-y-6">
        {{ $slot }}

        {{ $actions ?? '' }}
    </div>
</section>
