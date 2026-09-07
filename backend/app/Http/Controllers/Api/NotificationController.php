<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification as LaravelNotification;
use App\Services\Notifications\CreateNotificationService;

use App\Http\Requests\StoreNotificationRequest;

use App\Http\Controllers\Controller;
use App\Models\NotificationMessage;
use App\Models\NotificationDelivery;

class NotificationController extends Controller
{
    public function store(StoreNotificationRequest $request, CreateNotificationService $service): JsonResponse
    {
        $project = $request->attributes->get('current_project');
        $idempotencyKey = $request->idempotencyKey();

        $result = $service->execute(
            project: $project,
            data: $request->validated(),
            idempotencyKey: $idempotencyKey
        );

        $notification = $result['notification'];
        $delivery = $result['delivery'];

        return response()->json([
            'message' => $result['replayed']
                ? 'Notification already exists.'
                : 'Notification created successfully.',
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
            'meta' => [
                'idempotent_replay' => $result['replayed'],
            ],
        ], $result['replayed'] ? 200 : 201);
    }
}
