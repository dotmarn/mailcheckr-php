<?php

use Dotmarn\MailCheckr\Facades\MailCheckr;
use Dotmarn\MailCheckr\MailCheckrClient;
use Dotmarn\MailCheckr\MailCheckrServiceProvider;
use Dotmarn\MailCheckr\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class LaravelIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [MailCheckrServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('mailcheckr.api_key', 'integration-key');
        $app['config']->set('mailcheckr.webhook_secret', 'integration-secret');
    }

    public function test_provider_merges_configuration_and_resolves_the_client_and_webhook_verifier(): void
    {
        $this->assertSame('integration-key', config('mailcheckr.api_key'));
        $this->assertSame('https://mailcheckr.app/api/v1', config('mailcheckr.base_url'));
        $this->assertSame(app(MailCheckrClient::class), app(MailCheckrClient::class));
        $this->assertInstanceOf(WebhookVerifier::class, app(WebhookVerifier::class));

        Http::fake(['*' => Http::response(['data' => ['id' => 'integration-id', 'state' => 'queued']], 202)]);

        $this->assertSame('integration-id', MailCheckr::verify('person@example.com', 'integration-1')['id']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer integration-key')
            && $request->hasHeader('Idempotency-Key', 'integration-1'));

        $body = '{"id":"event_123"}';
        $timestamp = (string) time();
        $signature = 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'integration-secret');
        $webhook = Request::create('/api/webhooks/mailcheckr', 'POST', [], [], [], [
            'HTTP_X_MAILCHECKR_TIMESTAMP' => $timestamp,
            'HTTP_X_MAILCHECKR_SIGNATURE' => $signature,
        ], $body);

        $this->assertTrue(app(WebhookVerifier::class)->verify($webhook));
    }
}
