<?php

namespace App\Providers;

use App\Exceptions\TokenMissingException;
use App\Mail\PostMarkTransport;
use GuzzleHttp\Client;
use Illuminate\Mail\MailServiceProvider;

class PostMarkServiceProvider extends MailServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function registerSwiftTransport()
    {
        parent::registerSwiftTransport();

        app('swift.transport')->extend('postmark', function ($app) {
            $token = config('services.postmark.api-token', env('POSTMARK_SECRET'));

            if (!$token) {
                throw new TokenMissingException('API Token is missing. add POSTMARK_SECRET in your .env');
            }

            return new PostMarkTransport(
                app(Client::class), $token
            );
        });
    }
}
