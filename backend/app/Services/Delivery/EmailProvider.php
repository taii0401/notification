<?php

namespace App\Services\Delivery;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Services\Templates\TemplateRenderer;

use App\Models\NotificationMessage;

class EmailProvider
{
    public function __construct(private TemplateRenderer $renderer) 
    {

    }

    public function send(NotificationMessage $notification): array {
        //測試(不要都是成功，所以有時成功，有時失敗)
        $mode = config(
            'services.email_provider.mode',
            'success'
        );

        return match ($mode) {
            'success' => $this->success($notification),
            'fail' => $this->failure($notification),
            'random' => $this->random($notification),
            default => $this->success($notification),
        };
    }

    private function success(NotificationMessage $notification): array 
    {
        $template = $notification->template;

        if (!$template) {
            return [
                'success' => false,
                'response_code' => 400,
                'provider_message_id' => null,
                'error_type' => 'template_not_found',
                'error_message' => 'Notification template is missing.',
            ];
        }

        $payload = $notification->payload ?? [];

        $subject = $this->renderer->render(
            $template->subject ?? '',
            $payload
        );

        $content = $this->renderer->render(
            $template->content,
            $payload
        );

        Log::info(
            'Email notification simulated.',
            [
                'notification_uuid' => $notification->uuid,
                'recipient' => $notification->recipient,
                'template_code' => $template->code,
                'subject' => $subject,
                'content' => $content,
            ]
        );

        /*
        Mail::raw(
            $content,
            function ($message) use ($notification, $subject) {
                $message->to($notification->recipient)->subject($subject);
            }
        );
        */

        return [
            'success' => true,
            'response_code' => 200,
            'provider_message_id' => 'log-' . $notification->uuid,
            'error_type' => null,
            'error_message' => null,
        ];
    }

    private function failure(NotificationMessage $notification): array {
        Log::warning('Email notification simulated failure.', [
            'notification_uuid' => $notification->uuid,
            'recipient' => $notification->recipient,
            'event_type' => $notification->event_type,
        ]);

        return [
            'success' => false,
            'response_code' => 503,
            'provider_message_id' => null,
            'error_type' => 'provider_temporary_error',
            'error_message' => 'Simulated provider failure.',
        ];
    }

    private function random(NotificationMessage $notification): array {
        $success = random_int(1, 100) <= 60;

        return $success
            ? $this->success($notification)
            : $this->failure($notification);
    }
}