<?php

namespace Dotmarn\MailCheckr;

use Illuminate\Http\Request;

class WebhookVerifier
{
    public function __construct(private readonly ?string $secret, private readonly int $tolerance = 300) {}

    public function verify(Request $request): bool
    {
        return $this->verifyPayload(
            $request->getContent(),
            $request->header('X-MailCheckr-Timestamp'),
            $request->header('X-MailCheckr-Signature')
        );
    }

    public function verifyPayload(string $body, ?string $timestamp, ?string $signature): bool
    {
        if (! $this->secret || ! $timestamp || ! ctype_digit($timestamp)
            || ! $signature || ! preg_match('/^v1=[a-f0-9]{64}$/iD', $signature)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $this->tolerance) {
            return false;
        }

        $expected = 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, $this->secret);

        return hash_equals($expected, strtolower($signature));
    }
}
