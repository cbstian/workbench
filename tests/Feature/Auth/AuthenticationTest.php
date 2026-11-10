<?php

use App\Models\User;

it('renders the login screen', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeText('Inicia sesión')
        ->assertSee(route('password.request'), escape: false);
});

it('logs users in with valid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

it('logs users out and sends them to the login screen', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('redirects authenticated users away from the login screen', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

it('does not offer registration or two-factor authentication', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
    $this->get('/two-factor-challenge')->assertNotFound();
});
