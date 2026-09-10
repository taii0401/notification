<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Project;

class NotificationTemplateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_can_be_created(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson(
            "/api/projects/{$project->uuid}/notification-templates",
            [
                'code' => 'order_paid',
                'name' => '訂單付款成功',
                'channel' => 'email',
                'subject' => '訂單 {{order_no}} 付款成功',
                'content' => '您好 {{customer_name}}',
                'status' => 'active',
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'notification_templates',
            [
                'project_id' => $project->id,
                'code' => 'order_paid',
            ]
        );
    }

    public function test_template_code_must_be_unique_within_project(): void
    {
        $project = Project::factory()->create();

        $payload = [
            'code' => 'order_paid',
            'name' => '訂單付款成功',
            'channel' => 'email',
            'subject' => '付款成功',
            'content' => '內容',
        ];

        $this->postJson(
            "/api/projects/{$project->uuid}/notification-templates",
            $payload
        )->assertCreated();

        $this->postJson(
            "/api/projects/{$project->uuid}/notification-templates",
            $payload
        )->assertUnprocessable();
    }
}