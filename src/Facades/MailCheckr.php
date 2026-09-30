<?php

namespace Dotmarn\MailCheckr\Facades;

use Dotmarn\MailCheckr\MailCheckrClient;
use Illuminate\Support\Facades\Facade;

/** @method static array verify(string $email, string $idempotencyKey) */
/** @method static array find(string $id) */
class MailCheckr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MailCheckrClient::class;
    }
}
