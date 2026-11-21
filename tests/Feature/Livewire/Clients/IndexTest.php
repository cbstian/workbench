<?php

use App\Enums\WorkspaceRole;
use App\Livewire\Clients\Index;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

it('lists the clients of the current workspace', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Client::factory()->for($workspace)->create(['name' => 'Andes Café']);
    Client::factory()->create(['name' => 'Cliente ajeno']);

    $this->actingAs($owner)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSeeText('Andes Café')
        ->assertDontSeeText('Cliente ajeno');
});

it('creates a client with name, description, logo and color (F-03)', function () {
    Storage::fake('public');
    [$owner, $workspace] = superAdminWithWorkspace();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Río Azul')
        ->set('form.description', 'Agencia de viajes')
        ->set('form.color', '#3366FF')
        ->set('form.logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertHasNoErrors();

    $client = $workspace->clients()->sole();

    expect($client->name)->toBe('Río Azul')
        ->and($client->color)->toBe('#3366ff')
        ->and($client->created_by_id)->toBe($owner->id)
        ->and($client->logo_path)->toStartWith("workspaces/{$workspace->uuid}/clients/{$client->uuid}/");

    Storage::disk('public')->assertExists($client->logo_path);
});

it('validates name uniqueness and color format', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Client::factory()->for($workspace)->create(['name' => 'Casa Mora']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Casa Mora')
        ->set('form.color', 'rojo')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique', 'form.color' => 'regex']);
});

it('edits a client and removes its logo', function () {
    Storage::fake('public');
    [$owner, $workspace] = superAdminWithWorkspace();
    Storage::disk('public')->put('logos/old.png', 'x');
    $client = Client::factory()->for($workspace)->create(['logo_path' => 'logos/old.png']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('edit', $client->uuid)
        ->set('form.name', 'Nuevo nombre')
        ->set('form.removeLogo', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($client->fresh()->name)->toBe('Nuevo nombre')
        ->and($client->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing('logos/old.png');
});

it('sends a client to the trash', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $client = Client::factory()->for($workspace)->create();

    Livewire::actingAs($owner)->test(Index::class)->call('delete', $client->uuid);

    expect($client->fresh()->trashed())->toBeTrue();
});

it('forbids anyone but the super admin (A-20)', function (WorkspaceRole $role) {
    [, $workspace] = superAdminWithWorkspace();
    $user = User::factory()->memberOf($workspace, $role)->create();

    $this->actingAs($user)->get(route('clients.index'))->assertForbidden();
})->with(WorkspaceRole::cases());
