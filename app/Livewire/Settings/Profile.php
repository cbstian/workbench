<?php

namespace App\Livewire\Settings;

use App\Enums\ColorPalette;
use App\Livewire\Forms\PasswordForm;
use App\Livewire\Forms\ProfileForm;
use App\Support\Impersonation;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Account settings: profile, password and appearance (color palette).
 *
 * Reference page for forms (narrow page + <x-ui.form-section> + Form objects
 * + toast feedback). Logic lives here and in the Form objects; markup lives in
 * resources/views/livewire/settings/profile.blade.php.
 */
#[Title('Ajustes')]
class Profile extends Component
{
    use Interactions;

    public ProfileForm $profile;

    public PasswordForm $passwordForm;

    /**
     * Selected color palette (App\Enums\ColorPalette value), saved on change.
     */
    public string $palette = '';

    public function mount(): void
    {
        $this->profile->setUser(auth()->user());
        $this->palette = (auth()->user()->palette ?? ColorPalette::default())->value;
    }

    /**
     * Save the palette as soon as a card is picked and repaint the page
     * without reloading (resources/js/modules/palette.js).
     */
    public function updatedPalette(): void
    {
        $user = auth()->user();

        if ($this->blockedWhileImpersonating()) {
            $this->palette = ($user->palette ?? ColorPalette::default())->value;

            return;
        }

        $this->validate(
            ['palette' => ['required', Rule::enum(ColorPalette::class)]],
            attributes: ['palette' => 'paleta'],
        );

        $user->update(['palette' => $this->palette]);

        $this->dispatch('palette-changed', palette: $this->palette);

        $this->toast()->success('Paleta actualizada', ColorPalette::from($this->palette)->label().' se aplica en todo el panel.')->send();
    }

    /**
     * Save the name and email.
     */
    public function saveProfile(): void
    {
        if ($this->blockedWhileImpersonating()) {
            return;
        }

        $this->profile->update(auth()->user());

        $this->toast()->success('Perfil actualizado', 'Tus datos se guardaron.')->send();
    }

    /**
     * Change the password.
     */
    public function savePassword(): void
    {
        if ($this->blockedWhileImpersonating()) {
            return;
        }

        $this->passwordForm->update(auth()->user());

        $this->toast()->success('Contraseña actualizada', 'Usa la nueva contraseña la próxima vez que ingreses.')->send();
    }

    /**
     * A super admin seeing the app as someone else cannot change that
     * person's name, email, password or palette.
     */
    private function blockedWhileImpersonating(): bool
    {
        if (! app(Impersonation::class)->isActive()) {
            return false;
        }

        $this->toast()->warning('No disponible al ver como otro usuario', 'Vuelve a tu perfil para cambiar datos de cuenta.')->send();

        return true;
    }

    public function render(): View
    {
        return view('livewire.settings.profile');
    }
}
