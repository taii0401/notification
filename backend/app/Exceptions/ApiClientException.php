<?php

namespace App\Exceptions;

use App\Support\SystemCode;
use RuntimeException;

class ApiClientException extends RuntimeException
{
    public readonly int $httpStatus;

    public function __construct(public readonly int $systemCode)
    {
        $definition = SystemCode::definition($systemCode);

        $this->httpStatus = $definition['http_status'];

        parent::__construct($definition['message']);
    }
}
