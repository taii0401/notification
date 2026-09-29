<?php

namespace Tests\Feature;

use App\Models\NotificationMessage;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardDeliveryChartTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_daily_notification_and_success_counts_for_the_selected_month(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        NotificationMessage::factory()
            ->for($project)
            ->sent()
            ->count(2)
            ->create(['created_at' => '2026-09-02 10:00:00']);

        NotificationMessage::factory()
            ->for($project)
            ->failed()
            ->create(['created_at' => '2026-09-02 12:00:00']);

        NotificationMessage::factory()
            ->for($project)
            ->queued()
            ->create(['created_at' => '2026-09-04 09:00:00']);

        NotificationMessage::factory()
            ->for($project)
            ->sent()
            ->create(['created_at' => '2026-10-01 09:00:00']);

        NotificationMessage::factory()
            ->for($otherProject)
            ->sent()
            ->create(['created_at' => '2026-09-02 09:00:00']);

        $response = $this->getJson(
            "/api/projects/{$project->uuid}/dashboard/delivery-chart?month=2026-09"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.month', '2026-09')
            ->assertJsonCount(30, 'data.daily')
            ->assertJsonPath('data.daily.0', [
                'date' => '2026-09-01',
                'total' => 0,
                'success' => 0,
            ])
            ->assertJsonPath('data.daily.1', [
                'date' => '2026-09-02',
                'total' => 3,
                'success' => 2,
            ])
            ->assertJsonPath('data.daily.3', [
                'date' => '2026-09-04',
                'total' => 1,
                'success' => 0,
            ]);
    }

    public function test_it_rejects_an_invalid_month(): void
    {
        $project = Project::factory()->create();

        $this->getJson(
            "/api/projects/{$project->uuid}/dashboard/delivery-chart?month=2026-13"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('month');
    }
}
