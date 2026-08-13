<?php

namespace App\Services\AgentaOS;

use RuntimeException;
use Throwable;

class AgentaOsException extends RuntimeException
{
    /**
     * @param  int  $status  The HTTP status, or 0 when the request never got a response.
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $body = [],
        public readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
