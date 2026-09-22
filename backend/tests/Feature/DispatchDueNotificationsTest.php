<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationMessage;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchDueNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_due_pending_notifications(): void
    {
        Queue::fake();

        $notification = $this->createPendingNotification(now()->subMinute());

        $this->artisan('notifications:dispatch-due')
            ->expectsOutput('Dispatched 1 due notification(s).')
            ->assertSuccessful();

        Queue::assertPushed(
            SendNotificationJob::class,
            fn (SendNotificationJob $job) => $job->notificationId === $notification->id
        );

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('notification_deliveries', [
            'notification_id' => $notification->id,
            'status' => 'queued',
        ]);
    }

    public function test_it_does_not_dispatch_future_notifications(): void
    {
        Queue::fake();

        $notification = $this->createPendingNotification(now()->addHour());

        $this->artisan('notifications:dispatch-due')
            ->expectsOutput('Dispatched 0 due notification(s).')
            ->assertSuccessful();

        Queue::assertNothingPushed();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'pending',
        ]);
    }

    private function createPendingNotification($scheduledAt): NotificationMessage
    {
        $notification = NotificationMessage::factory()
            ->for(Project::factory())
            ->pending()
            ->create([
                'scheduled_at' => $scheduledAt,
            ]);

        $notification->deliveries()->create([
            'provider' => 'smtp',
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        return $notification;
    }
}
