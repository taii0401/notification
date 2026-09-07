<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Exceptions\ApiClientException;
use App\Models\ApiKey;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $plainTextKey = $request->bearerToken();

        if (!$plainTextKey) {
            throw new ApiClientException(40101);
        }

        $hash = hash('sha256', $plainTextKey);

        $apiKey = ApiKey::with('project')
            ->where('key_hash', $hash)
            ->first();

        if (!$apiKey) {
            throw new ApiClientException(40102);
        }

        if ($apiKey->status !== 'active') {
            throw new ApiClientException(40103);
        }

        if (
            $apiKey->expires_at !== null
            && $apiKey->expires_at->isPast()
        ) {
            throw new ApiClientException(40104);
        }

        if (!$apiKey->project) {
            throw new ApiClientException(40301);
        }

        if ($apiKey->project->status !== 'active') {
            throw new ApiClientException(40302);
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
