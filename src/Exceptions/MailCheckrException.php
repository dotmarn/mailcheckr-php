<?php

namespace Dotmarn\MailCheckr\Exceptions;

use RuntimeException;

class MailCheckrException extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly array $response)
    {
        parent::__construct((string) ($response['message'] ?? $response['code'] ?? 'MailCheckr request failed.'), $status);
    }
}
