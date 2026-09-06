<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification as LaravelNotification;

use App\Http\Requests\StoreNotificationRequest;

use App\Http\Controllers\Controller;
use App\Models\NotificationMessage;
use App\Models\NotificationDelivery;

class NotificationController extends Controller
{
    public function store(StoreNotificationRequest $request): JsonResponse 
    {
        $project = $request->attributes->get('current_project');

        $result = DB::transaction(
            function () use ($request, $project) {
                //建立 Notification
                $notification = NotificationMessage::create([
                    'project_id' => $project->id,
                    'event_type' => $request->input('event_type'),
                    'channel' => $request->input('channel'),
                    'recipient' => $request->input('recipient'),
                    'payload' => $request->input('data'),
                    'metadata' => null,
                    'status' => 'pending',
                    'scheduled_at' => $request->input('scheduled_at'),
                ]);

                //決定 Provider
                $provider = match ($notification->channel) {
                    'email' => 'mock_email',
                    'webhook' => 'http_webhook',
                    default => throw new \RuntimeException(
                        'Unsupported notification channel.'
                    ),
                };

                //建立 Delivery
                $delivery = $notification->deliveries()->create([
                    'provider' => $provider,
                    'status' => 'pending',
                    'attempt_count' => 0,
                ]);

                return [
                    'notification' => $notification,
                    'delivery' => $delivery,
                ];
            }
        );

        $notification = $result['notification'];
        $delivery = $result['delivery'];

        return response()->json([
            'message' => 'Notification created successfully.',
            'data' => [
                'uuid' => $notification->uuid,
                'event_type' => $notification->event_type,
                'channel' => $notification->channel,
                'recipient' => $notification->recipient,
                'status' => $notification->status,
                'scheduled_at' => $notification->scheduled_at,
                'created_at' => $notification->created_at,
                'delivery' => [
                    'provider' => $delivery?->provider,
                    'status' => $delivery?->status,
                ],
            ],
        ], 201);
    }
}
