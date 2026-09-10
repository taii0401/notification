<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Exceptions\ApiClientException;
use App\Services\Notifications\CreateNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class NotificationIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_idempotency_key_and_request_replays_existing_notification(): void
    {
        $project = $this->createProject();
        $service = app(CreateNotificationService::class);
        $data = $this->notificationData();

        $first = $service->execute($project, $data, 'notification-request-1');
        $replayed = $service->execute($project, $data, 'notification-request-1');

        $this->assertFalse($first['replayed']);
        $this->assertTrue($replayed['replayed']);
        $this->assertSame($first['notification']->id, $replayed['notification']->id);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_same_idempotency_key_with_different_request_is_rejected(): void
    {
        $project = $this->createProject();
        $service = app(CreateNotificationService::class);

        $service->execute(
            $project,
            $this->notificationData(),
            'notification-request-2'
        );

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage(
            'Idempotency key has already been used with a different request.'
        );

        $service->execute(
            $project,
            $this->notificationData('another@example.com'),
            'notification-request-2'
        );
    }

    private function createProject(): Project
    {
        return Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
        ]);
    }

    /** @return array<string, mixed> */
    private function notificationData(
        string $recipient = 'customer@example.com'
    ): array {
        return [
            'event_type' => 'order.created',
            'channel' => 'email',
            'recipient' => $recipient,
            'template' => null,
            'data' => [
                'order_id' => 123,
                'customer' => 'Taylor',
            ],
            'scheduled_at' => null,
        ];
    }
}
