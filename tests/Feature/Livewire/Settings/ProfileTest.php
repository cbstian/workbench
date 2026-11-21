<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('settings.profile'))->assertRedirect(route('login'));
});

it('renders the settings page with the current profile', function () {
    $this->actingAs(User::factory()->create(['name' => 'Ada Lovelace']))
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertSeeLivewire(Profile::class)
        ->assertSee('Ada Lovelace');
});

it('updates the name and email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('profile.name', 'Grace Hopper')
        ->set('profile.email', 'grace@example.com')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh())
        ->name->toBe('Grace Hopper')
        ->email->toBe('grace@example.com')
        ->email_verified_at->toBeNull();
});

it('rejects an email that belongs to another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('profile.email', 'taken@example.com')
        ->call('saveProfile')
        ->assertHasErrors(['profile.email' => 'unique']);
});

it('changes the password when the current one is correct', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'old-password')
        ->set('passwordForm.password', 'new-secret-password')
        ->set('passwordForm.password_confirmation', 'new-secret-password')
        ->call('savePassword')
        ->assertHasNoErrors()
        ->assertSet('passwordForm.current_password', '');

    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();
});

it('keeps the password when the current one is wrong', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'not-my-password')
        ->set('passwordForm.password', 'new-secret-password')
        ->set('passwordForm.password_confirmation', 'new-secret-password')
        ->call('savePassword')
        ->assertHasErrors(['passwordForm.current_password' => 'current_password']);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
