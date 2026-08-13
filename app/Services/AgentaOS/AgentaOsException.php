<?php

namespace App\Services\AgentaOS;

use RuntimeException;

class AgentaOsException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $body = [],
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message);
    }
}
