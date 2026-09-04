<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Http\Requests\StoreApiKeyRequest;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Project;

class ApiKeyController extends Controller
{
    /**
     * 取得 Project 的 API Key 列表。
     */
    public function index(Project $project): JsonResponse
    {
        $apiKeys = $project->apiKeys()
            ->latest()
            ->get([
                'id',
                'name',
                'key_prefix',
                'status',
                'last_used_at',
                'expires_at',
                'created_at',
            ]);

        return response()->json([
            'data' => $apiKeys,
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
        $this->ensureApiKeyBelongsToProject($project,$apiKey);

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
            'message' => 'API Key revoked successfully.',
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
