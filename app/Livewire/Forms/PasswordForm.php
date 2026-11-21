<?php

namespace App\Livewire\Forms;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Livewire\Form;

/**
 * Password change for the signed-in user (requires the current password).
 */
class PasswordForm extends Form
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'current_password' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ];
    }

    /**
     * Validate and store the new password, then clear the fields.
     */
    public function update(User $user): void
    {
        $validated = $this->validate();

        $user->update(['password' => $validated['password']]);

        $this->reset();
    }
}
