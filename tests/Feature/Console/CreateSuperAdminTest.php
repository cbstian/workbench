<?php

use App\Models\User;

it('creates a super admin with their account from the console (F-01)', function () {
    $this->artisan('workbench:super-admin', [
        '--name' => 'Sebastián',
        '--email' => 'Seba@Example.com',
        '--password' => 'secreto123',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'seba@example.com')->sole();

    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->account_id)->toBe($user->ownedAccount->id)
        ->and($user->ownedAccount->name)->toBe('Sebastián');
});

it('rejects a taken email or a short password', function () {
    User::factory()->create(['email' => 'tomado@example.com']);

    $this->artisan('workbench:super-admin', ['--name' => 'X', '--email' => 'tomado@example.com', '--password' => 'secreto123'])->assertFailed();
    $this->artisan('workbench:super-admin', ['--name' => 'X', '--email' => 'nuevo@example.com', '--password' => 'corta'])->assertFailed();

    expect(User::query()->where('email', 'nuevo@example.com')->exists())->toBeFalse();
});
