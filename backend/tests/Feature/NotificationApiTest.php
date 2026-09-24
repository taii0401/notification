<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use App\Jobs\SendNotificationJob;

use App\Models\ApiKey;
use App\Models\NotificationMessage;
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

    public function test_notification_detail_returns_multiple_deliveries_and_attempts(): void
    {
        $notification = NotificationMessage::factory()->create([
            'project_id' => $this->project->id,
        ]);

        foreach (['smtp', 'ses'] as $provider) {
            $delivery = $notification->deliveries()->create([
                'provider' => $provider,
                'status' => 'failed',
                'attempt_count' => 2,
            ]);

            $delivery->attempts()->createMany([
                [
                    'attempt_no' => 1,
                    'status' => 'failed',
                    'error_message' => 'Temporary provider error.',
                ],
                [
                    'attempt_no' => 2,
                    'status' => 'success',
                    'response_code' => 200,
                ],
            ]);
        }

        $this
            ->getJson(
                "/api/projects/{$this->project->uuid}/notifications/{$notification->uuid}"
            )
            ->assertOk()
            ->assertJsonCount(2, 'data.deliveries')
            ->assertJsonCount(2, 'data.deliveries.0.attempts')
            ->assertJsonCount(2, 'data.deliveries.1.attempts')
            ->assertJsonPath('data.deliveries.0.attempts.0.attempt_no', 1)
            ->assertJsonPath('data.deliveries.0.attempts.1.attempt_no', 2);
    }

    public function test_notification_detail_cannot_be_read_from_another_project(): void
    {
        $anotherProject = Project::factory()->create();
        $notification = NotificationMessage::factory()->create([
            'project_id' => $anotherProject->id,
        ]);

        $this
            ->getJson(
                "/api/projects/{$this->project->uuid}/notifications/{$notification->uuid}"
            )
            ->assertNotFound();
    }

    public function test_notification_remains_pending_until_scheduler_dispatches_it(): void
    {
        $this
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'customer@example.com',
            ])
            ->assertCreated();

        Queue::assertNotPushed(
            SendNotificationJob::class
        );

        $this->assertDatabaseHas('notifications', [
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);
    }
}
