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
    public function index(Request $request): JsonResponse
    {
        $project = $request->attributes->get('current_project');

        $query = NotificationMessage::query()
            ->where('project_id', $project->id)
            ->with(['deliveries',]);


        //filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }
        if ($request->filled('recipient')) {
            $query->where('recipient', $request->input('recipient'));
        }
        //created_at range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $notifications = $query->latest()->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => collect($notifications->items())
                ->map(function ($notification) {
                    $delivery = $notification->deliveries->first();

                    return [
                        'uuid' => $notification->uuid,
                        'event_type' => $notification->event_type,
                        'channel' => $notification->channel,
                        'recipient' => $notification->recipient,
                        'status' => $notification->status,
                        'delivery' => [
                            'provider' => $delivery?->provider,
                            'status' => $delivery?->status,
                            'attempt_count' => $delivery?->attempt_count,
                        ],
                        'scheduled_at' => $notification->scheduled_at,
                        'processed_at' => $notification->processed_at,
                        'sent_at' => $notification->sent_at,
                        'failed_at' => $notification->failed_at,
                        'created_at' => $notification->created_at,
                    ];
                }),

            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

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

    public function show(Request $request, NotificationMessage $notification): JsonResponse 
    {
        $project = $request->attributes->get('current_project');

        if ($notification->project_id !== $project->id) {
            abort(404);
        }

        $notification->load(['template', 'deliveries.attempts']);

        return response()->json([
            'data' => [
                'uuid' => $notification->uuid,
                'event_type' => $notification->event_type,
                'channel' => $notification->channel,
                'recipient' => $notification->recipient,
                'status' => $notification->status,
                'payload' => $notification->payload,
                'metadata' => $notification->metadata,
                'template' => $notification->template
                    ? [
                        'code' => $notification->template->code,
                        'name' => $notification->template->name,
                        'channel' => $notification->template->channel,
                    ]
                    : null,
                'scheduled_at' => $notification->scheduled_at,
                'processed_at' => $notification->processed_at,
                'sent_at' => $notification->sent_at,
                'failed_at' => $notification->failed_at,
                'created_at' => $notification->created_at,
                'deliveries' => $notification->deliveries->map(function ($delivery) {
                    return [
                        'provider' => $delivery->provider,
                        'status' => $delivery->status,
                        'attempt_count' => $delivery->attempt_count,
                        'provider_message_id' => $delivery->provider_message_id,
                        'last_error' => $delivery->last_error,
                        'sent_at' => $delivery->sent_at,
                        'failed_at' => $delivery->failed_at,
                        'attempts' => $delivery->attempts->map(function ($attempt) {
                            return [
                                'attempt_no' => $attempt->attempt_no,
                                'status' => $attempt->status,
                                'response_code' => $attempt->response_code,
                                'response_body' => $attempt->response_body,
                                'error_type' => $attempt->error_type,
                                'error_message' => $attempt->error_message,
                                'started_at' => $attempt->started_at,
                                'finished_at' => $attempt->finished_at,
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }
}
