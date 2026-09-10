<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use App\Jobs\SendNotificationJob;

use App\Models\ApiKey;
use App\Models\Project;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private string $plainKey;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->project = Project::factory()->create([
            'status' => 'active',
        ]);

        $this->plainKey = 'nfs_test_abc123';

        ApiKey::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Testing Key',
            'key_prefix' => 'nfs_test_',
            'key_hash' => hash('sha256', $this->plainKey),
            'status' => 'active',
            'expires_at' => null,
        ]);
    }

    public function test_notification_can_be_created(): void
    {
        $response = $this
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'customer@example.com',
                'data' => [
                    'order_no' => 'ORD-001',
                    'amount' => 1280,
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'project_id' => $this->project->id,
            'event_type' => 'order.paid',
            'channel' => 'email',
            'recipient' => 'customer@example.com',
        ]);
    }

    public function test_notification_requires_api_key(): void
    {
        $response = $this->postJson('/api/notifications', [
            'event_type' => 'order.paid',
            'channel' => 'email',
            'recipient' => 'customer@example.com',
        ]);

        $response->assertUnauthorized();
    }

    public function test_notification_rejects_invalid_channel(): void
    {
        $response = $this
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'facebook',
                'recipient' => 'customer@example.com',
            ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'channel',
        ]);
    }

    public function test_notification_rejects_invalid_email_recipient(): void
    {
        $response = $this
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'not-an-email',
            ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'recipient',
        ]);
    }

    public function test_notification_creates_delivery(): void
    {
        $this
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'customer@example.com',
            ])
            ->assertCreated();

        $this->assertDatabaseCount(
            'notification_deliveries',
            1
        );
    }

    public function test_notification_dispatches_job(): void
    {
        $this
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'customer@example.com',
            ])
            ->assertCreated();

        Queue::assertPushed(
            SendNotificationJob::class
        );
    }
}