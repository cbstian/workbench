<x-ui.page width="narrow">
    <x-ui.page-header title="Ajustes" description="Tu perfil, tu contraseña y la apariencia del panel." />

    <div>
        <form wire:submit="saveProfile">
            <x-ui.form-section title="Perfil" description="Tu nombre se muestra en el panel y en la actividad de las tareas.">
                <x-ui.form-grid>
                    <x-input label="Nombre *" wire:model="profile.name" autocomplete="name" />
                    <x-input label="Correo *" type="email" wire:model="profile.email" autocomplete="email" />
                </x-ui.form-grid>

                <x-slot:actions>
                    <x-ui.form-actions>
                        <x-button type="submit" text="Guardar perfil" sm loading="saveProfile" />
                    </x-ui.form-actions>
                </x-slot:actions>
            </x-ui.form-section>
        </form>

        <form wire:submit="savePassword">
            <x-ui.form-section title="Contraseña" description="Usa al menos 8 caracteres. Necesitas tu contraseña actual para cambiarla.">
                <x-ui.form-grid>
                    <div class="sm:col-span-2">
                        <x-password label="Contraseña actual *" wire:model="passwordForm.current_password" autocomplete="current-password" />
                    </div>
                    <x-password label="Nueva contraseña *" wire:model="passwordForm.password" autocomplete="new-password" />
                    <x-password label="Confirmar contraseña *" wire:model="passwordForm.password_confirmation" autocomplete="new-password" />
                </x-ui.form-grid>

                <x-slot:actions>
                    <x-ui.form-actions>
                        <x-button type="submit" text="Cambiar contraseña" sm loading="savePassword" />
                    </x-ui.form-actions>
                </x-slot:actions>
            </x-ui.form-section>
        </form>

        <x-ui.form-section title="Apariencia" description="La paleta se guarda en tu cuenta. El modo claro u oscuro se guarda en este navegador; «Sistema» sigue la preferencia de tu equipo.">
            <div class="space-y-6">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-ink">Paleta</legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach (\App\Enums\ColorPalette::cases() as $paletteOption)
                            <x-theme.palette-option :palette="$paletteOption" name="palette" wire:model.live="palette" wire:key="palette-{{ $paletteOption->value }}" />
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <p class="mb-2 text-sm font-medium text-ink">Modo</p>
                    <x-theme-switch block />
                </div>
                <p class="text-sm text-ink-muted">
                    Contrae la barra lateral con <x-kbd text="Ctrl" /> + <x-kbd :text="'\\'" /> para usar todo el ancho.
                </p>
            </div>
        </x-ui.form-section>
    </div>
</x-ui.page>
