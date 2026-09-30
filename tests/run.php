<?php

require __DIR__.'/../vendor/autoload.php';

use Dotmarn\MailCheckr\Exceptions\MailCheckrException;
use Dotmarn\MailCheckr\MailCheckrClient;
use Dotmarn\MailCheckr\WebhookVerifier;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$container = new Container();
$container->instance('http', new Factory());
Facade::setFacadeApplication($container);

Http::fake([
    'mailcheckr.app/api/v1/verifications' => Http::response(['data' => ['id' => 'id-1', 'state' => 'queued']], 202),
    'mailcheckr.app/api/v1/verifications/id-1' => Http::response(['data' => ['id' => 'id-1', 'state' => 'completed', 'status' => 'deliverable']], 200),
]);

$client = new MailCheckrClient('test-key');
check($client->verify('person@example.com', 'person-1')['state'] === 'queued', '202 response must remain pending');
check($client->find('id-1')['status'] === 'deliverable', 'Retrieval must return verification data');
$sent = Http::recorded(fn ($request) => $request->method() === 'POST'
    && $request->hasHeader('Authorization', 'Bearer test-key')
    && $request->hasHeader('Idempotency-Key', 'person-1')
    && $request['email'] === 'person@example.com');
check($sent->count() === 1, 'POST must include authorization, idempotency key, and email');

Http::swap(new Factory());
Http::fake(['*' => Http::response(['code' => 'insufficient_credits'], 402)]);
try {
    $client->verify('person@example.com', 'person-2');
    throw new RuntimeException('Expected API error');
} catch (MailCheckrException $e) {
    check($e->status === 402 && $e->response['code'] === 'insufficient_credits', 'API error details must be preserved');
}

$body = '{"id":"event_123"}';
$timestamp = (string) time();
$signature = 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'secret');
$verifier = new WebhookVerifier('secret');
check($verifier->verifyPayload($body, $timestamp, $signature), 'Valid signature must pass');
check(! $verifier->verifyPayload($body.' ', $timestamp, $signature), 'Modified body must fail');
check(! $verifier->verifyPayload($body, (string) (time() - 301), $signature), 'Stale timestamp must fail');
check(! (new WebhookVerifier(null))->verifyPayload($body, $timestamp, $signature), 'Missing secret must fail');

echo "All MailCheckr tests passed.\n";
