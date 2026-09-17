<?php

namespace App\Exceptions;

use App\Support\SystemCode;
use RuntimeException;

class ApiClientException extends RuntimeException
{
    public readonly int $httpStatus;

    public function __construct(string $message, public readonly int $systemCode, private readonly array $errorDetails = [])
    {
        $definition = SystemCode::definition($systemCode);

        $this->httpStatus = $definition['http_status'];

        parent::__construct($definition['message']);
    }

    public function errors(): array
    {
        return $this->errorDetails;
    }
}
