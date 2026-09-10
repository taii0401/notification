<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\ApiKey;
use App\Models\NotificationMessage;
use App\Models\Project;

class NotificationQueryTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private string $plainKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create([
            'status' => 'active',
        ]);

        $this->plainKey = 'nfs_test_query';

        ApiKey::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Query Key',
            'key_prefix' => 'nfs_test_',
            'key_hash' => hash('sha256', $this->plainKey),
            'status' => 'active',
        ]);
    }

    public function test_project_can_list_its_notifications(): void
    {
        NotificationMessage::factory()
            ->count(3)
            ->create([
                'project_id' => $this->project->id,
            ]);

        $response = $this
            ->withToken($this->plainKey)
            ->getJson('/api/notifications');

        $response->assertOk();

        $response->assertJsonCount(
            3,
            'data'
        );
    }

    public function test_project_cannot_see_other_project_notifications(): void
    {
        $otherProject = Project::factory()->create();

        NotificationMessage::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        $response = $this
            ->withToken($this->plainKey)
            ->getJson('/api/notifications');

        $response->assertOk();

        $response->assertJsonCount(
            0,
            'data'
        );
    }

    public function test_notification_can_be_filtered_by_status(): void
    {
        NotificationMessage::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'sent',
        ]);

        NotificationMessage::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $response = $this
            ->withToken($this->plainKey)
            ->getJson('/api/notifications?status=failed');

        $response->assertOk();

        $response->assertJsonCount(
            1,
            'data'
        );

        $response->assertJsonPath(
            'data.0.status',
            'failed'
        );
    }
}