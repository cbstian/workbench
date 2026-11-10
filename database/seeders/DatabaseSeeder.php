<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::query()
            ->firstOrNew(['email' => 'demo@workbench.test'])
            ->forceFill([
                'name' => 'Sebastián Aguilera',
                'password' => 'password',
                'email_verified_at' => now(),
            ])
            ->save();

        $this->call(ProjectSeeder::class);
    }
}
