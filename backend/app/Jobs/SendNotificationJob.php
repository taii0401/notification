<?php

namespace App\Jobs;

use RuntimeException;
use Throwable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

use App\Services\Providers\EmailProvider;

use App\Models\NotificationMessage;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    //最多執行 4 次
    public int $tries = 4;

    //等待時間
    public function backoff(): array
    {
        return [
            3, //第一次失敗，3 → 30
            5, //第二次失敗，5 → 120
            10, //第三次失敗，10 → 600
        ];
    }

    public function __construct(public int $notificationId) 
    {

    }

    public function handle(EmailProvider $emailProvider): void
    {
        //只有 queued 才能進入 Job
        $updated = NotificationMessage::query()
            ->where('id', $this->notificationId)
            ->where('status', 'queued')
            ->update([
                'status' => 'processing',
            ]);

        if ($updated === 0) {
            return;
        }

        //processed_at 只記錄「第一次開始處理」的時間
        $notification = NotificationMessage::with('template', 'deliveries')->findOrFail($this->notificationId);
        if ($notification->processed_at === null) {
            $notification->update([
                'processed_at' => now(),
            ]);
        }

        //取得 Delivery
        $delivery = $notification->deliveries->firstOrFail();
        $delivery->update([
            'status' => 'processing',
        ]);

        //建立 Attempt
        //每一次真正呼叫 Provider 都新增一筆
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
        //Delivery 累計真正送過幾次
        $delivery->increment('attempt_count');

        //Call Provider
        $result = [];
        if ($notification->channel == 'email') {
            $result = $emailProvider->send($notification);
        }

        
        //Success
        if ($result['success'] === true) {
            $attempt->update([
                'status' => 'success',
                'response_code' => $result['response_code'] ?? 200,
                'finished_at' => now(),
            ]);

            $delivery->update([
                'status' => 'sent',
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'last_error' => null,
                'sent_at' => now(),
            ]);

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null, //最後成功時 failed_at 應保持 NULL
            ]);

            Log::info(
                'Notification delivery succeeded.',
                [
                    'notification_id' => $notification->id,
                    'notification_uuid' => $notification->uuid,
                    'delivery_id' => $delivery->id,
                    'attempt_id' => $attempt->id,
                    'attempt_no' => $attemptNo,
                    'provider' => $delivery->provider,
                ]
            );

            return;
        }

        //Failure
        $responseCode = $result['response_code'] ?? null;
        $errorType = $result['error_type'] ?? 'provider_error';
        $errorMessage = $result['error_message'] ?? 'Notification delivery failed.';
        $attempt->update([
            'status' => 'failed',
            'response_code' => $responseCode,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'finished_at' => now(),
        ]);

        $delivery->update([
            'status' => 'failed',
            'last_error' => $errorMessage,
            'failed_at' => now(),
        ]);

        $notification->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        Log::warning(
            'Notification delivery permanently failed.',
            [
                'notification_id' => $notification->id,
                'notification_uuid' => $notification->uuid,
                'delivery_id' => $delivery->id,
                'attempt_no' => $attemptNo,
                'response_code' => $responseCode,
                'error_type' => $errorType,
                'error_message' => $errorMessage,
            ]
        );

        //Retry 前，回復狀態 status
        $delivery->update([
            'status' => 'pending',
            'last_error' => $errorMessage,
        ]);
        $notification->update([
            'status' => 'queued',
        ]);

        Log::warning(
            'Notification delivery failed and will retry.',
            [
                'notification_id' => $notification->id,
                'notification_uuid' => $notification->uuid,
                'delivery_id' => $delivery->id,
                'attempt_no' => $attemptNo,
                'job_attempt' => $this->attempts(), //Queue 自己的 Job attempt 次數
                'response_code' => $responseCode,
                'error_message' => $errorMessage,
            ]
        );

        //Provider 回 false 不代表 Laravel Queue 知道失敗
        //必須 throw Exception，Laravel Queue 才會依照 backoff() Retry
        throw new RuntimeException(
            $errorMessage
        );
    }

    /**
     * Laravel Retry 次數耗盡後執行
     * 次數還沒用完就會跑進這裡
     */
    public function failed(?Throwable $exception): void 
    {
        $notification = NotificationMessage::query()->with('deliveries')->find($this->notificationId);

        if (!$notification) {
            return;
        }

        $notification->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        $delivery = $notification->deliveries->first();

        if ($delivery) {
            $delivery->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_error' => $exception?->getMessage(),
            ]);
        }

        Log::error(
            'Notification delivery exhausted all retries.',
            [
                'notification_id' => $notification->id,
                'notification_uuid' => $notification->uuid,
                'delivery_id' => $delivery->id,
                'error_message' => $exception?->getMessage(),
            ]
        );
    }
}