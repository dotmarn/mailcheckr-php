<?php

use Dotmarn\MailCheckr\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookVerifierTest extends TestCase
{
    public function test_it_accepts_a_valid_signature_and_rejects_modified_or_stale_deliveries(): void
    {
        $body = '{"id":"event_123"}';
        $timestamp = (string) time();
        $signature = 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'secret');
        $verifier = new WebhookVerifier('secret');

        $this->assertTrue($verifier->verifyPayload($body, $timestamp, $signature));
        $this->assertFalse($verifier->verifyPayload($body.' ', $timestamp, $signature));
        $this->assertFalse($verifier->verifyPayload($body, (string) (time() - 301), $signature));
        $this->assertFalse((new WebhookVerifier(null))->verifyPayload($body, $timestamp, $signature));
    }
}
