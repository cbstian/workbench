<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Name and email of the signed-in user.
 *
 * Reference Livewire Form object: fields + rules + persistence live here,
 * the page component only orchestrates (docs/arquitectura/interfaz.md).
 */
class ProfileForm extends Form
{
    public string $name = '';

    public string $email = '';

    /**
     * Fill the fields from the user being edited.
     */
    public function setUser(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore(auth()->id()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
        ];
    }

    /**
     * Validate and save the profile of the given user.
     */
    public function update(User $user): void
    {
        $validated = $this->validate();

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
