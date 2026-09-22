<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Enums\NotificationChannel;
use App\Http\Requests\StoreNotificationTemplateRequest;
use App\Http\Requests\UpdateNotificationTemplateRequest;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\Project;

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

        $data = $templates
            ->getCollection()
            ->map(function (NotificationTemplate $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'code' => $template->code,
                    'channel' => $template->channel,
                    'channel_display' => NotificationChannel::labelFor(
                        $template->channel
                    ),
                    'subject' => $template->subject,
                    'content' => $template->content,
                    'status' => $template->status,
                    'status_display' => $template->status_display,
                    'created_at' => $template->created_at,
                    'created_at_display' => $template->created_at
                        ?->format('Y-m-d H:i:s'),
                    'updated_at' => $template->updated_at,
                    'updated_at_display' => $template->updated_at
                        ?->format('Y-m-d H:i:s'),
                ];
            });
        
        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $templates->currentPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'last_page' => $templates->lastPage(),
                'from' => $templates->firstItem(),
                'to' => $templates->lastItem(),
            ],
            'options' => [
                'channels' => NotificationChannel::options(),
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

    public function show(Project $project, NotificationTemplate $template): JsonResponse 
    {
        $this->ensureTemplateBelongsToProject($project, $template);

        return response()->json([
            'data' => $template,
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, Project $project, NotificationTemplate $template): JsonResponse 
    {
        $this->ensureTemplateBelongsToProject($project, $template);

        $template->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Notification template updated successfully.',
            'data' =>
                $template->fresh(),
        ]);
    }

    public function destroy(Project $project, NotificationTemplate $template): JsonResponse 
    {
        $this->ensureTemplateBelongsToProject($project, $template);

        $template->update([
            'status' => 'inactive',
        ]);
        $template->delete();

        return response()->json([
            'message' => 'Notification template deleted successfully.',
        ]);
    }

    /**
     * 確認 Template 屬於指定 Project。
     */
    private function ensureTemplateBelongsToProject(Project $project, NotificationTemplate $template): void
    {
        abort_unless(
            $template->project_id === $project->id,
            404
        );
    }
}
