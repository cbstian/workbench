<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with a demo user and demo data.
     *
     * Demo credentials (local only): demo@workbench.test / password
     */
    public function run(): void
    {
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

        $this->call(ProjectSeeder::class);
    }
}
