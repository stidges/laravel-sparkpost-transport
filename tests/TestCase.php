<?php

namespace Stidges\SparkpostTransport\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Stidges\SparkpostTransport\SparkpostTransportServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SparkpostTransportServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        config()->set('mail.mailers.sparkpost', [
            'transport' => 'sparkpost',
        ]);
    }
}
