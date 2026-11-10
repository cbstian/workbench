<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed demo projects for the projects table.
     */
    public function run(): void
    {
        Project::factory()->count(36)->create();
    }
}
