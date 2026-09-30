<?php

namespace Dotmarn\MailCheckr;

use Dotmarn\MailCheckr\Exceptions\MailCheckrException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class MailCheckrClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $baseUrl = 'https://mailcheckr.app/api/v1',
        private readonly int $timeout = 30
    ) {}

    public function verify(string $email, string $idempotencyKey): array
    {
        if ($email === '' || $idempotencyKey === '') {
            throw new InvalidArgumentException('Email and idempotency key are required.');
        }

        return $this->request('post', 'verifications', ['email' => $email], $idempotencyKey);
    }

    public function find(string $id): array
    {
        if ($id === '' || str_contains($id, '/')) {
            throw new InvalidArgumentException('A valid verification ID is required.');
        }

        return $this->request('get', 'verifications/'.rawurlencode($id));
    }

    private function request(string $method, string $path, array $body = [], ?string $idempotencyKey = null): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('Configure MAILCHECKR_API_KEY before making requests.');
        }

        $request = Http::acceptJson()->withToken($this->apiKey)->timeout($this->timeout);

        if ($idempotencyKey !== null) {
            $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        $url = rtrim($this->baseUrl, '/').'/'.$path;
        $response = $method === 'post' ? $request->post($url, $body) : $request->get($url);
        $json = $response->json();

        if ($response->failed()) {
            throw new MailCheckrException($response->status(), is_array($json) ? $json : []);
        }

        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            throw new RuntimeException('MailCheckr returned an unexpected response.');
        }

        return $json['data'];
    }
}
