<?php

namespace App\Services\Delivery;

use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\NotificationMessage;

class WebhookProvider
{
    public function send(NotificationMessage $notification): array 
    {
        try {
            $response = Http::connectTimeout(config('services.webhook.connect_timeout', 2))
                ->timeout(config('services.webhook.timeout', 5))
                ->acceptJson()
                ->post(
                    $notification->recipient,
                    [
                        'event_type' => $notification->event_type,
                        'notification_uuid' => $notification->uuid,
                        'data' => $notification->payload ?? [],
                    ]
                );

            Log::info('Webhook delivery response.', [
                'notification_uuid' => $notification->uuid,
                'url' => $notification->recipient,
                'status_code' => $response->status(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response_code' => $response->status(),
                    'provider_message_id' => null,
                    'error_type' => null,
                    'error_message' => null,
                    'response_body' => $response->body(),
                ];
            }

            return [
                'success' => false,
                'response_code' => $response->status(),
                'provider_message_id' => null,
                'error_type' => $this->errorType($response->status()),
                'error_message' => 'Webhook returned HTTP ' . $response->status(),
                'response_body' => $response->body(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Webhook delivery exception.', [
                'notification_uuid' => $notification->uuid,
                'url' => $notification->recipient,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'response_code' => null,
                'provider_message_id' => null,
                'error_type' => 'network_error',
                'error_message' => $exception->getMessage(),
                'response_body' => null,
            ];
        }
    }

    private function errorType(int $statusCode): string
    {
        return match (true) {
            $statusCode === 408 => 'timeout',
            $statusCode === 429 => 'rate_limited',
            $statusCode >= 500 => 'provider_temporary_error',
            $statusCode >= 400 => 'provider_permanent_error',
            default => 'provider_error',
        };
    }
}