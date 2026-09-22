<?php

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DispatchDueNotifications extends Command
{
    protected $signature = 'notifications:dispatch-due {--limit=100 : Maximum notifications to dispatch}';

    protected $description = 'Dispatch pending notifications whose scheduled time has arrived';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $notificationIds = NotificationMessage::query()
            ->where('status', NotificationStatus::PENDING->value)
            ->where(function ($query) {
                $query
                    ->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;

        foreach ($notificationIds as $notificationId) {
            $claimedId = DB::transaction(function () use ($notificationId): ?int {
                $notification = NotificationMessage::query()
                    ->whereKey($notificationId)
                    ->lockForUpdate()
                    ->first();

                if (
                    $notification === null
                    || $notification->status !== NotificationStatus::PENDING
                ) {
                    return null;
                }

                $notification->update([
                    'status' => NotificationStatus::QUEUED,
                ]);

                $notification->deliveries()
                    ->where('status', NotificationStatus::PENDING->value)
                    ->update([
                        'status' => NotificationStatus::QUEUED->value,
                        'last_error' => null,
                    ]);

                return $notification->id;
            });

            if ($claimedId === null) {
                continue;
            }

            try {
                SendNotificationJob::dispatch($claimedId);
                $dispatched++;
            } catch (Throwable $exception) {
                $this->restorePendingStatus($claimedId, $exception);
                report($exception);

                $this->error(
                    "Failed to dispatch notification {$claimedId}: {$exception->getMessage()}"
                );
            }
        }

        $this->info("Dispatched {$dispatched} due notification(s).");

        return self::SUCCESS;
    }

    private function restorePendingStatus(int $notificationId, Throwable $exception): void
    {
        DB::transaction(function () use ($notificationId, $exception): void {
            NotificationMessage::query()
                ->whereKey($notificationId)
                ->where('status', NotificationStatus::QUEUED->value)
                ->update([
                    'status' => NotificationStatus::PENDING->value,
                ]);

            DB::table('notification_deliveries')
                ->where('notification_id', $notificationId)
                ->where('status', NotificationStatus::QUEUED->value)
                ->update([
                    'status' => NotificationStatus::PENDING->value,
                    'last_error' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);
        });
    }
}
