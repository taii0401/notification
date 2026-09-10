<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\ApiKey;
use App\Models\Project;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'key_prefix' => 'nfs_test_',
            'key_hash' => hash(
                'sha256',
                fake()->uuid()
            ),
            'status' => 'active',
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
