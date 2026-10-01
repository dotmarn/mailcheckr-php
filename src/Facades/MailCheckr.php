<?php

namespace Dotmarn\MailCheckr\Facades;

use Dotmarn\MailCheckr\MailCheckrClient;
use Dotmarn\MailCheckr\VerificationResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static VerificationResult verify(string $email, string $idempotencyKey)
 * @method static VerificationResult find(string $id)
 */
class MailCheckr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MailCheckrClient::class;
    }
}
