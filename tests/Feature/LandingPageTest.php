<?php

use App\Models\User;

it('shows only the brand name on the landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText(config('app.name'))
        ->assertSee('landing-backdrop', escape: false)
        ->assertSee('data-palette="roble"', escape: false)
        ->assertSee(route('login'), escape: false);
});

it('links the landing page to the dashboard for signed-in users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('dashboard'), escape: false);
});
