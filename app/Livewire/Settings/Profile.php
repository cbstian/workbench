<?php

namespace App\Livewire\Settings;

use App\Livewire\Forms\PasswordForm;
use App\Livewire\Forms\ProfileForm;
use App\Support\Impersonation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Account settings: profile and password.
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

    public function mount(): void
    {
        $this->profile->setUser(auth()->user());
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
     * person's name, email or password.
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
