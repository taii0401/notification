<?php

namespace App\Services\Idempotency;

class RequestHashService
{
    public function make(array $payload): string
    {
        //因為字串順序不同，hash 也可能不同
        $normalized = $this->normalize($payload);

        return hash(
            'sha256',
            json_encode(
                $normalized,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );
    }

    private function normalize(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize($value);
            }
        }

        return $data;
    }
}