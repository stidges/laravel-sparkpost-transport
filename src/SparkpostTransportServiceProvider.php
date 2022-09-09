<?php

namespace Stidges\SparkpostTransport;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class SparkpostTransportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('sparkpost', function () {
            $config = config('services.sparkpost');

            return new SparkpostTransport(
                new Client(array_merge(['connect_timeout' => 60], $config['guzzle'] ?? [])),
                $config['secret'],
                $config['domain'] ?? 'api.sparkpost.com',
                $config['options'] ?? []
            );
        });
    }
}
