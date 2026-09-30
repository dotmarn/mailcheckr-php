<?php

use Dotmarn\MailCheckr\Exceptions\MailCheckrException;
use Dotmarn\MailCheckr\MailCheckrClient;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class MailCheckrClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->instance('http', new Factory());
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function test_it_sends_authenticated_idempotent_requests_and_preserves_pending_results(): void
    {
        Http::fake([
            'mailcheckr.app/api/v1/verifications' => Http::response(['data' => ['id' => 'id-1', 'state' => 'queued']], 202),
            'mailcheckr.app/api/v1/verifications/id-1' => Http::response(['data' => ['id' => 'id-1', 'state' => 'completed', 'status' => 'deliverable']], 200),
        ]);

        $client = new MailCheckrClient('test-key');

        $this->assertSame('queued', $client->verify('person@example.com', 'person-1')['state']);
        $this->assertSame('deliverable', $client->find('id-1')['status']);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request->hasHeader('Idempotency-Key', 'person-1')
            && $request['email'] === 'person@example.com');
    }

    public function test_it_exposes_api_error_status_and_response(): void
    {
        Http::fake(['*' => Http::response(['code' => 'insufficient_credits'], 402)]);

        try {
            (new MailCheckrClient('test-key'))->verify('person@example.com', 'person-2');
            $this->fail('Expected a MailCheckrException.');
        } catch (MailCheckrException $exception) {
            $this->assertSame(402, $exception->status);
            $this->assertSame('insufficient_credits', $exception->response['code']);
        }
    }
}
