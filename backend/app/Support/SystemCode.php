<?php

namespace App\Support;

use LogicException;

final class SystemCode
{
    private const DEFINITIONS = [
        40101 => [
            'http_status' => 401,
            'message' => 'API Key is required.',
        ],
        40102 => [
            'http_status' => 401,
            'message' => 'Invalid API Key.',
        ],
        40103 => [
            'http_status' => 401,
            'message' => 'API Key is not active.',
        ],
        40104 => [
            'http_status' => 401,
            'message' => 'API Key has expired.',
        ],
        40301 => [
            'http_status' => 403,
            'message' => 'Project is unavailable.',
        ],
        40302 => [
            'http_status' => 403,
            'message' => 'Project is not active.',
        ],
        40401 => [
            'http_status' => 404,
            'message' => 'The requested resource was not found.',
        ],
        40501 => [
            'http_status' => 405,
            'message' => 'The HTTP method is not allowed for this endpoint.',
        ],
        40901 => [
            'http_status' => 409,
            'message' => 'Idempotency key has already been used with a different request.',
        ],
        42201 => [
            'http_status' => 422,
            'message' => 'The given data was invalid.',
        ],
    ];

    /**
     * @return array{http_status: int, message: string}
     */
    public static function definition(int $systemCode): array
    {
        return self::DEFINITIONS[$systemCode]
            ?? throw new LogicException(
                "Undefined system code: {$systemCode}"
            );
    }
}
