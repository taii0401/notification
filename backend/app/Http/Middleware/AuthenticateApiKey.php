<?php

namespace App\Http\Middleware;

use Closure, DB;
use Illuminate\Http\Request;

use App\Models\ApiKey;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $plainTextKey = $request->bearerToken();

        if (!$plainTextKey) {
            return response()->json([
                'message' => 'API Key is required.',
            ], 401);
        }

        $hash = hash('sha256', $plainTextKey);

        $apiKey = ApiKey::with('project')
            ->where('key_hash', $hash)
            ->first();

        if (!$apiKey) {
            return response()->json([
                'message' => 'Invalid API Key.',
            ], 401);
        }

        if ($apiKey->status !== 'active') {
            return response()->json([
                'message' => 'API Key is not active.',
            ], 401);
        }

        if (
            $apiKey->expires_at !== null
            && $apiKey->expires_at->isPast()
        ) {
            return response()->json([
                'message' => 'API Key has expired.',
            ], 401);
        }

        if (!$apiKey->project) {
            return response()->json([
                'message' => 'Project is unavailable.',
            ], 403);
        }

        if ($apiKey->project->status !== 'active') {
            return response()->json([
                'message' => 'Project is not active.',
            ], 403);
        }

        $apiKey->update([
            'last_used_at' => now(),
        ]);

        //因為不要讓 Client 端傳送資料, 所以在這裡把資料放到 HTTP Request 裡面
        $request->attributes->set(
            'current_project',
            $apiKey->project
        );

        $request->attributes->set(
            'current_api_key',
            $apiKey
        );

        return $next($request);
    }
}