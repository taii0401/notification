<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationMessage;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardStatsController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $stats = NotificationMessage::query()
            ->where('project_id', $project->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', [NotificationStatus::PENDING->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as queued', [NotificationStatus::QUEUED->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as processing', [NotificationStatus::PROCESSING->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent', [NotificationStatus::SENT->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [NotificationStatus::FAILED->value])
            ->first();

        $sent = (int) $stats->sent;
        $failed = (int) $stats->failed;
        $pending = (int) $stats->pending;
        $processing = (int) $stats->processing;
        $waiting = $pending + $processing;
        $completed = $sent + $failed;
        // 成功率
        $successRate = $completed > 0
            ? round(($sent / $completed) * 100, 2)
            : 0;

        return response()->json([
            'data' => [
                'total' => (int) $stats->total,
                'waiting' => $waiting,
                'processing' => (int) $stats->processing,
                'sent' => $sent,
                'failed' => $failed,
                'success_rate' => $successRate,
            ],
        ]);
    }

    public function deliveryChart(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $start = CarbonImmutable::parse("{$month}-01")->startOfDay();
        $end = $start->addMonth();

        $rows = NotificationMessage::query()
            ->where('project_id', $project->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success',
                [NotificationStatus::SENT->value]
            )
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $daily = [];

        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $row = $rows->get($dateKey);

            $daily[] = [
                'date' => $dateKey,
                'total' => (int) ($row?->total ?? 0),
                'success' => (int) ($row?->success ?? 0),
            ];
        }

        return response()->json([
            'data' => [
                'month' => $month,
                'daily' => $daily,
            ],
        ]);
    }
}
