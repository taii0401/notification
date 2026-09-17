<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\NotificationMessage;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_api_key(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertUnauthorized();
    }

    public function test_dashboard_returns_correct_statistics(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $plainTextKey = 'dashboard-test-'.fake()->uuid();

        ApiKey::factory()
            ->for($project)
            ->state([
                'key_hash' => hash('sha256', $plainTextKey),
            ])
            ->create();

        NotificationMessage::factory()
            ->for($project)
            ->count(6)
            ->sent()
            ->create();

        NotificationMessage::factory()
            ->for($project)
            ->count(2)
            ->failed()
            ->create();

        NotificationMessage::factory()
            ->for($project)
            ->count(2)
            ->processing()
            ->create();

        // Act
        $response = $this
            ->withToken($plainTextKey)
            ->getJson('/api/dashboard');

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('data.total', 10)
            ->assertJsonPath('data.pending', 0)
            ->assertJsonPath('data.queued', 0)
            ->assertJsonPath('data.processing', 2)
            ->assertJsonPath('data.sent', 6)
            ->assertJsonPath('data.failed', 2)
            ->assertJsonPath('data.success_rate', 75);
    }

    public function test_dashboard_only_counts_notifications_of_current_project(): void
    {
        // Arrange
        $projectA = Project::factory()->create();

        $plainTextKey = 'dashboard-test-'.fake()->uuid();

        ApiKey::factory()
            ->for($projectA)
            ->state([
                'key_hash' => hash('sha256', $plainTextKey),
            ])
            ->create();

        $projectB = Project::factory()->create();

        // Project A
        NotificationMessage::factory()
            ->for($projectA)
            ->count(3)
            ->sent()
            ->create();

        NotificationMessage::factory()
            ->for($projectA)
            ->count(1)
            ->failed()
            ->create();

        // Project B
        NotificationMessage::factory()
            ->for($projectB)
            ->count(100)
            ->sent()
            ->create();

        // Act
        $response = $this
            ->withToken($plainTextKey)
            ->getJson('/api/dashboard');

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.sent', 3)
            ->assertJsonPath('data.failed', 1);
    }
}
