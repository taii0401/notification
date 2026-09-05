<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification as LaravelNotification;

use App\Http\Requests\StoreNotificationRequest;

use App\Http\Controllers\Controller;
use App\Models\NotificationMessage;

class NotificationController extends Controller
{
    public function store(StoreNotificationRequest $request): JsonResponse 
    {
        $project = $request->attributes->get('current_project');

        $notification = NotificationMessage::create([
            'project_id' => $project->id,
            'event_type' => $request->input('event_type'),
            'channel' => $request->input('channel'),
            'recipient' => $request->input('recipient'),
            'payload' => $request->input('data'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Notification created successfully.',
            'data' => [
                'uuid' => $notification->uuid,
                'status' => $notification->status,
            ],
        ], 201);
    }
}
