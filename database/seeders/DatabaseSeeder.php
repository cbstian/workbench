<?php

namespace Database\Seeders;

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

        Workspace::query()->firstOrCreate(
            [
                'owner_id' => $owner->id,
                'name' => 'Personal',
            ],
        );

        $this->call(ProjectSeeder::class);
    }
}
