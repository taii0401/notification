<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;

use App\Http\Controllers\Controller;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->string('keyword')->toString();

            $query->where(function ($query) use ($keyword) {
                $query
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%");
            });
        }

        $projects = $query
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $projects->items(),

            'meta' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create([
            'uuid' => (string) Str::uuid(),
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'status' => $request->input('status', 'active'),
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => $project,
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'data' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse 
    {
        $project->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Project updated successfully.',
            'data' => $project->fresh(),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        DB::transaction(function () use ($project) {
            $project->update(['status' => 'inactive']); //將狀態停用
            $project->delete();
        });

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}