<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationTemplateRequest;
use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse 
    {
        $query = $project->notificationTemplates()->latest();

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(
                function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                }
            );
        }

        $templates = $query->paginate(20);

        return response()->json([
            'data' => $templates->items(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    public function store(StoreNotificationTemplateRequest $request, Project $project): JsonResponse 
    {
        $template = $project
            ->notificationTemplates()
            ->create([
                'code' => $request->input('code'),
                'name' => $request->input('name'),
                'channel' => $request->input('channel'),
                'subject' => $request->input('subject'),
                'content' => $request->input('content'),
                'status' => $request->input('status', 'active'),
            ]);

        return response()->json([
            'message' => 'Notification template created successfully.',
            'data' => $template,
        ], 201);
    }

    public function show(Project $project, NotificationTemplate $notificationTemplate): JsonResponse 
    {
        return response()->json([
            'data' => $notificationTemplate,
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, Project $project, NotificationTemplate $notificationTemplate): JsonResponse 
    {
        $notificationTemplate->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Notification template updated successfully.',
            'data' =>
                $notificationTemplate->fresh(),
        ]);
    }

    public function destroy(Project $project, NotificationTemplate $notificationTemplate): JsonResponse 
    {
        $notificationTemplate->update([
            'status' => 'inactive',
        ]);
        $notificationTemplate->delete();

        return response()->json([
            'message' => 'Notification template deleted successfully.',
        ]);
    }
}