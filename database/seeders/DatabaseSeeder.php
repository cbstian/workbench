<?php

namespace Database\Seeders;

use App\Actions\Authorization\SyncRolePermissions;
use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a demo super admin and demo data.
     *
     * Model events stay on: workspace memberships sync the spatie roles.
     *
     * Demo credentials (local only): demo@workbench.test / password
     * (WorkspaceSeeder adds admin, members and a client user, same password).
     */
    public function run(): void
    {
        app(SyncRolePermissions::class)->handle();

        $owner = User::query()
            ->firstOrNew(['email' => 'demo@workbench.test']);

        $owner->forceFill([
            'name' => 'Sebastián Aguilera',
            'password' => 'password',
            'email_verified_at' => now(),
        ])->save();

        $account = $owner->ownedAccount ?? Account::openFor($owner);

        Workspace::query()->firstOrCreate(
            [
                'account_id' => $account->id,
                'name' => 'Personal',
            ],
            [
                'created_by_id' => $owner->id,
            ],
        );

        $this->call([ProjectSeeder::class, WorkspaceSeeder::class]);
    }
}
