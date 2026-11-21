<?php

use App\Models\User;

it('renders the sidebar links registered in the navigation config', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('projects.index'), escape: false)
        ->assertSee(route('settings.profile'), escape: false)
        ->assertSeeText('Espacio de trabajo');
});

it('hides items whose ability the user lacks', function () {
    config()->set('navigation.groups.1.items.0.can', 'manage-everything');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Espacio de trabajo');
});

it('skips items whose route does not exist', function () {
    config()->set('navigation.groups.1.items', [
        ['label' => 'Módulo pendiente', 'route' => 'missing.index', 'icon' => 'cube'],
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Módulo pendiente');
});
