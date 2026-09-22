<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Exceptions\ApiClientException;
use App\Http\Requests\StoreApiKeyRequest;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Project;

class ApiKeyController extends Controller
{
    /**
     * 取得 Project 的 API Key 列表。
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $query = $project->apiKeys();

        if ($request->filled('keyword')) {
            $keyword = trim(
                $request->string('keyword')->toString()
            );

            $query->where('name', 'like', "%{$keyword}%");
        }

        $apiKeys = $query->latest()->paginate(20);

        $data = $apiKeys
            ->getCollection()
            ->map(function (ApiKey $apiKey) {
                return [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'key_prefix' => $apiKey->key_prefix,
                    'status' => $apiKey->status,
                    'status_display' => $apiKey->status_display,
                    'last_used_at' => $apiKey->last_used_at,
                    'last_used_at_display' => $apiKey->last_used_at
                        ?->format('Y-m-d H:i:s'),
                    'expires_at' => $apiKey->expires_at,
                    'expires_at_display' => $apiKey->expires_at
                        ?->format('Y-m-d H:i:s'),
                    'created_at' => $apiKey->created_at,
                    'created_at_display' => $apiKey->created_at
                        ?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $apiKeys->currentPage(),
                'per_page' => $apiKeys->perPage(),
                'total' => $apiKeys->total(),
                'last_page' => $apiKeys->lastPage(),
                'from' => $apiKeys->firstItem(),
                'to' => $apiKeys->lastItem(),
            ],
        ]);
    }

    /**
     * 建立 API Key。
     *
     * Plaintext API Key 只會在這次 Response 出現一次。
     */
    public function store(StoreApiKeyRequest $request, Project $project): JsonResponse 
    {
        $prefix = str_replace('-', '_', $project->slug).'_';

        $secret = Str::random(48);

        $plainTextKey = $prefix . $secret;

        $apiKey = $project->apiKeys()->create([
            'name' => $request->string('name')->toString(),
            'key_prefix' => $prefix,
            'key_hash' => hash(
                'sha256',
                $plainTextKey
            ),
            'status' => 'active',
            'expires_at' => $request->input('expires_at'),
        ]);

        return response()->json([
            'message' => 'API Key created successfully.',

            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'api_key' => $plainTextKey,
                'status' => $apiKey->status,
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],

            'warning' => 'This API Key will only be displayed once.',
        ], 201);
    }

    /**
     * 取得單一 API Key。
     */
    public function show(Project $project, ApiKey $apiKey): JsonResponse 
    {
        $this->ensureApiKeyBelongsToProject($project, $apiKey);

        return response()->json([
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'status' => $apiKey->status,
                'last_used_at' => $apiKey->last_used_at,
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
        ]);
    }

    /**
     * API Key 不提供修改功能。
     */
    public function update(Request $request, Project $project, ApiKey $apiKey): JsonResponse 
    {
        return response()->json([
            'message' => 'API Key update is not supported.',
        ], 405);
    }

    /**
     * Revoke API Key。
     */
    public function destroy(Project $project, ApiKey $apiKey): JsonResponse 
    {
        $this->ensureApiKeyBelongsToProject($project, $apiKey);

        DB::transaction(function () use ($apiKey) {
            $apiKey->update([
                'status' => 'revoked',
                //'expires_at' => date('Y-m-d H:i:s')
            ]); 
            $apiKey->delete();
        });

        return response()->json([
            'message' => 'API Key deleted successfully.',
        ]);
    }

    //重新產生 API Key
    public function regenerate(Project $project, ApiKey $apiKey): JsonResponse 
    {
        $this->ensureApiKeyBelongsToProject($project, $apiKey);

        $plainTextKey = DB::transaction(function () use ($project, $apiKey) {
            $prefix = str_replace('-', '_', $project->slug) . '_';
            $secret = Str::random(48);
            $plainTextKey = $prefix . $secret;

            $apiKey->update([
                'key_prefix' => $prefix,
                'key_hash' => hash('sha256', $plainTextKey),
                'status' => 'active',
                'last_used_at' => null,
            ]);

            return $plainTextKey;
        });

        return response()->json([
            'message' => 'API Key regenerated successfully.',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'api_key' => $plainTextKey,
                'status' => $apiKey->status,
                'regenerated_at_display' => now()
                    ->timezone(config('app.timezone'))
                    ->format('Y/m/d H:i:s'),
            ],
            'warning' => 'The previous API Key is now invalid. This new API Key will only be displayed once.',
        ]);
    }

    /**
     * 確認 API Key 屬於指定 Project。
     */
    private function ensureApiKeyBelongsToProject(Project $project, ApiKey $apiKey): void 
    {
        abort_unless(
            $apiKey->project_id === $project->id,
            404
        );
    }
}
