<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\NotificationMessage;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $notificationId) 
    {

    }

    public function handle(): void
    {
        $notification = NotificationMessage::findOrFail(
            $this->notificationId
        );

        if ($notification->status !== 'queued') {
            return;
        }

        if ($notification->processed_at !== null) {
            return;
        }

        //scheduled_at 暫時先預設建立通知的時候，之後若有安排時間，則需要設定排程
        if ($notification->scheduled_at !== null && $notification->scheduled_at->isFuture()) {
            return;
        }

        $notification->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);

        $delivery = $notification->deliveries->firstOrFail();
        $delivery->update([
            'status' => 'processing',
        ]);

        // 下一步：
        // Create NotificationAttempt
        // Call Provider
    }
}