<?php

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Mailer;
use Stidges\SparkpostTransport\SparkpostTransport;

it('registers the Sparkpost transport with the Laravel MailManager', function () {
    $manager = app('mail.manager');
    config()->set('services.sparkpost', [
        'secret' => 'SPARKPOST_SECRET',
    ]);

    $mailer = $manager->driver('sparkpost');

    expect($mailer)->toBeInstanceOf(Mailer::class);
    $transport = $mailer->getSymfonyTransport();
    expect($transport)->toBeInstanceOf(SparkpostTransport::class)
        ->getKey()->toBe('SPARKPOST_SECRET')
        ->getDomain()->toBe('api.sparkpost.com')
        ->getOptions()->toBe([])
        ->getClient()->toBeInstanceOf(ClientInterface::class);
    expect($transport->getClient()->getConfig('connect_timeout'))->toBe(60);
});

it('passes any configured options to the transport', function () {
    $manager = app('mail.manager');
    config()->set('services.sparkpost', [
        'secret' => 'SPARKPOST_SECRET',
        'options' => ['open_tracking' => true],
    ]);

    $mailer = $manager->driver('sparkpost');

    expect($mailer->getSymfonyTransport()->getOptions())->toBe([
        'open_tracking' => true,
    ]);
});

it('allows customizing the Sparkpost domain', function () {
    $manager = app('mail.manager');
    config()->set('services.sparkpost', [
        'secret' => 'SPARKPOST_SECRET',
        'domain' => 'api.eu.sparkpost.com',
    ]);

    $mailer = $manager->driver('sparkpost');

    expect($mailer->getSymfonyTransport()->getDomain())->toBe('api.eu.sparkpost.com');
});

it('passes any configured guzzle options to the Guzzle client', function () {
    $manager = app('mail.manager');
    config()->set('services.sparkpost', [
        'secret' => 'SPARKPOST_SECRET',
        'guzzle' => [
            'timeout' => 100,
            'connect_timeout' => 20,
        ],
    ]);

    $mailer = $manager->driver('sparkpost');

    expect($mailer->getSymfonyTransport()->getClient()->getConfig())->toMatchArray([
        'timeout' => 100,
        'connect_timeout' => 20,
    ]);
});
