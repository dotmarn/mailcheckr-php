<?php

use Dotmarn\MailCheckr\Exceptions\MailCheckrException;
use Dotmarn\MailCheckr\MailCheckrClient;
use Dotmarn\MailCheckr\VerificationResult;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
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
        $container->instance('config', new Repository([
            'mailcheckr' => [
                'api_key' => 'test-key',
                'base_url' => 'https://mailcheckr.app/api/v1',
                'timeout' => 30,
            ],
        ]));
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_sends_authenticated_idempotent_requests_and_preserves_pending_results(): void
    {
        Http::fake([
            'mailcheckr.app/api/v1/verifications' => Http::response(['data' => ['id' => 'id-1', 'state' => 'queued']], 202),
            'mailcheckr.app/api/v1/verifications/id-1' => Http::response(['data' => ['id' => 'id-1', 'state' => 'completed', 'status' => 'deliverable']], 200),
        ]);

        $client = new MailCheckrClient();

        $this->assertSame('queued', $client->verify('person@example.com', 'person-1')->state());
        $this->assertTrue($client->find('id-1')->isDeliverable());

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request->hasHeader('Idempotency-Key', 'person-1')
            && $request['email'] === 'person@example.com');
    }

    public function test_it_exposes_api_error_status_and_response(): void
    {
        Http::fake(['*' => Http::response(['code' => 'insufficient_credits'], 402)]);

        try {
            (new MailCheckrClient())->verify('person@example.com', 'person-2');
            $this->fail('Expected a MailCheckrException.');
        } catch (MailCheckrException $exception) {
            $this->assertSame(402, $exception->status);
            $this->assertSame('insufficient_credits', $exception->response['code']);
        }
    }

    public function test_direct_instantiation_uses_configured_base_url(): void
    {
        config()->set('mailcheckr.base_url', 'https://custom.example/api');
        Http::fake(['custom.example/api/verifications' => Http::response([
            'data' => ['id' => 'custom-id', 'state' => 'queued'],
        ], 202)]);

        $result = (new MailCheckrClient())->verify('person@example.com', 'custom-1');

        $this->assertSame('custom-id', $result->id());
        Http::assertSent(fn ($request) => $request->url() === 'https://custom.example/api/verifications');
    }

    public function test_verify_and_find_return_result_objects(): void
    {
        Http::fake([
            'mailcheckr.app/api/v1/verifications' => Http::response(['data' => ['id' => 'id-2', 'state' => 'queued']], 202),
            'mailcheckr.app/api/v1/verifications/id-2' => Http::response(['data' => ['id' => 'id-2', 'state' => 'completed', 'status' => 'unknown']], 200),
        ]);

        $client = new MailCheckrClient();

        $pending = $client->verify('person@example.com', 'person-2');
        $this->assertInstanceOf(VerificationResult::class, $pending);
        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isUnknown());

        $completed = $client->find($pending->id());
        $this->assertTrue($completed->isUnknown());
        $this->assertSame('unknown', $completed->toArray()['status']);
    }
}
