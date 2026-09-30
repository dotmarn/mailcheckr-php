# MailCheckr for Laravel

Laravel client for the [MailCheckr API](https://mailcheckr.app/docs). Requires PHP 8.2+ and Laravel 10–13.

## Install

```bash
composer require dotmarn/mailcheckr-php
```

The service provider and facade are discovered automatically. Add your server-side API key to `.env`:

```dotenv
MAILCHECKR_API_KEY=mc_live_your_api_key
MAILCHECKR_WEBHOOK_SECRET=your_webhook_signing_secret
```

Optional settings: `MAILCHECKR_BASE_URL`, `MAILCHECKR_TIMEOUT` (seconds, default 30), and `MAILCHECKR_WEBHOOK_TOLERANCE` (seconds, default 300). Publish the configuration with `php artisan vendor:publish --tag=mailcheckr-config`.

## Verify an address

```php
use Dotmarn\MailCheckr\MailCheckrClient;

$verification = app(MailCheckrClient::class)->verify(
    'person@example.com',
    'verify-user-123' // persist and reuse this key for retries of the same email
);

if ($verification['state'] !== 'completed') {
    $verification = app(MailCheckrClient::class)->find($verification['id']);
}

if ($verification['state'] === 'completed') {
    // Inspect $verification['status']: deliverable, undeliverable, risky, or unknown.
}
```

`verify()` returns the `data` object for HTTP 200 and 202. Pending states include `queued`, `processing`, and `retry_scheduled`; poll the ID or handle a webhook. Do not treat a pending or unknown result as deliverable.

The `MailCheckr` facade exposes the same `verify()` and `find()` methods. API errors throw `Dotmarn\MailCheckr\Exceptions\MailCheckrException`, with `status` and `response` properties. Network errors are raised by Laravel's HTTP client.

## Verify webhook deliveries

Configure an HTTPS endpoint in the MailCheckr dashboard. Put the route below in `routes/api.php` so Laravel's web CSRF middleware does not reject MailCheckr's POST requests. In Laravel 11–13, add `api: __DIR__.'/../routes/api.php'` to the existing `withRouting(...)` call in `bootstrap/app.php` if API routing is not already registered. With Laravel's default API prefix, set the dashboard URL to `https://your-app.example/api/webhooks/mailcheckr`.

Verify the raw request body before processing `verification.completed` or `bulk_verification.completed`:

```php
use Dotmarn\MailCheckr\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/mailcheckr', function (Request $request, WebhookVerifier $verifier) {
    abort_unless($verifier->verify($request), 401);

    $event = $request->json()->all();
    // Deduplicate by $event['id'] in persistent storage before applying effects.

    return response()->noContent();
});
```

The verifier checks the `X-MailCheckr-Signature` HMAC against `X-MailCheckr-Timestamp` and the exact body, and rejects timestamps outside the configured tolerance. Store processed event IDs because valid deliveries may be retried.

## Contributing

Please feel free to fork this package and contribute by submitting a pull request to enhance the functionalities.

## How can I thank you?

Why not star the github repo? I'd love the attention! Why not share the link for this repository on Twitter.

Don't forget to [follow me on twitter](https://twitter.com/oluwalosheyii)!

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
