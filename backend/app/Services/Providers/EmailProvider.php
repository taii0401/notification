<?php

namespace App\Services\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Models\NotificationMessage;

class EmailProvider
{
    public function send(
        NotificationMessage $notification
    ): array {
        Log::info('Email notification simulated.', [
            'notification_id' => $notification->id,
            'notification_uuid' => $notification->uuid,
            'recipient' => $notification->recipient,
            'event_type' => $notification->event_type,
            'payload' => $notification->payload,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 真正發送 Email
        |--------------------------------------------------------------------------
        |
        | 之後要正式寄信時，可以在這裡使用 Laravel Mail：
        |
        | Mail::raw(
        |     '通知內容',
        |     function ($message) use ($notification) {
        |         $message
        |             ->to($notification->recipient)
        |             ->subject('Notification');
        |     }
        | );
        |
        */

        return [
            'success' => true,
            'response_code' => 200,
            'provider_message_id' => 'log-' . $notification->uuid,
            'error_type' => null,
            'error_message' => null,
        ];
    }
}