<?php

namespace Dotmarn\MailCheckr;

use Illuminate\Support\ServiceProvider;

class MailCheckrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailcheckr.php', 'mailcheckr');

        $this->app->singleton(MailCheckrClient::class, fn ($app) => new MailCheckrClient(
            $app['config']->get('mailcheckr.api_key'),
            $app['config']->get('mailcheckr.base_url'),
            $app['config']->get('mailcheckr.timeout')
        ));

        $this->app->singleton(WebhookVerifier::class, fn ($app) => new WebhookVerifier(
            $app['config']->get('mailcheckr.webhook_secret'),
            $app['config']->get('mailcheckr.webhook_tolerance')
        ));
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/mailcheckr.php' => config_path('mailcheckr.php')], 'mailcheckr-config');
    }
}
