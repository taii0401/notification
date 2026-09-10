<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use App\Models\ApiKey;
use App\Models\Project;

class NotificationIdempotencyTest extends TestCase
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
        ]);
    }

    public function test_same_key_and_same_payload_returns_existing_notification(): void
    {
        $payload = [
            'event_type' => 'order.paid',
            'channel' => 'email',
            'recipient' => 'customer@example.com',
            'data' => [
                'order_no' => 'ORD-001',
                'amount' => 1280,
            ],
        ];

        $headers = [
            'Idempotency-Key' => 'order-001-paid',
        ];

        $this
            ->withHeaders($headers)
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', $payload)
            ->assertCreated();

        $this
            ->withHeaders($headers)
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', $payload)
            ->assertOk();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_same_key_with_different_payload_returns_conflict(): void
    {
        $headers = [
            'Idempotency-Key' => 'order-001-paid',
        ];

        $this
            ->withHeaders($headers)
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'customer@example.com',
                'data' => [
                    'order_no' => 'ORD-001',
                    'amount' => 1280,
                ],
            ])
            ->assertCreated();

        $this
            ->withHeaders($headers)
            ->withToken($this->plainKey)
            ->postJson('/api/notifications', [
                'event_type' => 'order.paid',
                'channel' => 'email',
                'recipient' => 'customer@example.com',
                'data' => [
                    'order_no' => 'ORD-001',
                    'amount' => 9999,
                ],
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('notifications', 1);
    }
}
