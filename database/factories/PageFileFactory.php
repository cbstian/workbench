<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageFile>
 */
class PageFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'uploaded_by_id' => User::factory(),
            'disk' => 'local',
            'path' => 'pages/'.fake()->uuid(),
            'original_name' => 'notas.md',
            'mime_type' => 'text/markdown',
            'size_bytes' => 480,
        ];
    }
}
