<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Services\Providers\EmailProvider;

use App\Models\NotificationMessage;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $notificationId) 
    {

    }

    public function handle(EmailProvider $emailProvider): void
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

        //更新 Notification
        $updated = NotificationMessage::query()
            ->where('id', $this->notificationId)
            ->where('status', 'queued')
            ->whereNull('processed_at')
            ->update([
                'status' => 'processing',
                'processed_at' => now(),
            ]);

        if ($updated === 0) {
            return;
        }

        //更新 Delivery
        $notification = NotificationMessage::with('deliveries')->findOrFail($this->notificationId);
        $delivery = $notification->deliveries->firstOrFail();
        $delivery->update([
            'status' => 'processing',
        ]);

        //建立 Attempt
        $attemptNo = $delivery->attempt_count + 1;
        $attempt = $delivery->attempts()->create([
            'attempt_no' => $attemptNo,
            'status' => 'processing',
            'request_payload' => [
                'recipient' => $notification->recipient,
                'event_type' => $notification->event_type,
                'payload' => $notification->payload,
            ],
        ]);
        $delivery->increment('attempt_count');

        //Call Provider
        $result = [];
        if ($notification->channel == 'email') {
            $result = $emailProvider->send($notification);
        }

        
        //Success
        if ($result['success']) {
            $attempt->update([
                'status' => 'success',
                'response_code' => $result['response_code'],
                'finished_at' => now(),
            ]);

            $delivery->update([
                'status' => 'sent',
                'provider_message_id' =>
                    $result['provider_message_id'],
                'sent_at' => now(),
            ]);

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return;
        }

        //Failure
        
    }
}