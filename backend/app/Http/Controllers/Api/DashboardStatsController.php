<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Enums\NotificationStatus;

use App\Http\Controllers\Controller;
use App\Models\NotificationMessage;

class DashboardStatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $project = $request->attributes->get('current_project');

        $stats = NotificationMessage::query()
            ->where('project_id', $project->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending", [NotificationStatus::PENDING->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as queued", [NotificationStatus::QUEUED->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as processing", [NotificationStatus::PROCESSING->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent", [NotificationStatus::SENT->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed", [NotificationStatus::FAILED->value])
            ->first();

        $sent = (int) $stats->sent;
        $failed = (int) $stats->failed;
        $completed = $sent + $failed;
        //成功率
        $successRate = $completed > 0
            ? round(($sent / $completed) * 100, 2)
            : 0;

        return response()->json([
            'data' => [
                'total' => (int) $stats->total,
                'pending' => (int) $stats->pending,
                'queued' => (int) $stats->queued,
                'processing' => (int) $stats->processing,
                'sent' => $sent,
                'failed' => $failed,
                'success_rate' => $successRate,
            ],
        ]);
    }
}
