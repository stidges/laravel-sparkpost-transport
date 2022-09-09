# Sparkpost transport for Laravel 9.x

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stidges/laravel-sparkpost-transport.svg?style=flat-square)](https://packagist.org/packages/stidges/laravel-sparkpost-transport)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/stidges/laravel-sparkpost-transport/run-tests?label=tests)](https://github.com/stidges/laravel-sparkpost-transport/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/stidges/laravel-sparkpost-transport/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/stidges/laravel-sparkpost-transport/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stidges/laravel-sparkpost-transport.svg?style=flat-square)](https://packagist.org/packages/stidges/laravel-sparkpost-transport)

A Sparkpost transport for Laravel 9.x

## Installation

You can install the package via composer:

```bash
composer require stidges/laravel-sparkpost-transport
```

## Usage

### 1. Configuration

To get started, update your `config/services.php` with your Sparkpost secret key like so:

```php
<?php

return [
    // ...
    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],
];
```

Next, add the Sparkpost transport to the `mailers` section of your `config/mail.php`:

```php
<?php

return [
    // ...
    'mailers' => [
        // ...
        'sparkpost' => [
            'transport' => 'sparkpost',
        ],
    ],
    // ...
];
```

And finally, update your `.env` file to add the `SPARKPOST_SECRET` and to update the `MAIL_MAILER`:

```dotenv 
MAIL_MAILER=sparkpost

SPARKPOST_SECRET=YourSecretKey
```

### 2. Customizing the Sparkpost API domain

If you'd like to use the EU domain for Sparkpost, you can add the `domain` to your `config/services.php` file:

```php
'sparkpost' => [
    'secret' => env('SPARKPOST_SECRET'),
    'domain' => 'api.eu.sparkpost.com',
],
```

### 3. Customizing Sparkpost Transmission API options

You can add a `options` array to your `config/services.php` to add any data you would like to send to the Sparkpost API.
Any data in the `options` array will be merged into the API request body. For details on how you can customize the
transmission, review the [Sparkpost API documentation](https://developers.sparkpost.com/api/transmissions/#header-request-body)

```php
'sparkpost' => [
    'secret' => env('SPARKPOST_SECRET'),
    'options' => [
        'campaign_id' => 'my_campaign_id',
        'options' => [
            'click_tracking' => false,
        ],
    ],
],
```

### 4. Customizing the Guzzle client

You can add any options to the Guzzle client by adding a `guzzle` array to your `config/services.php` file:

```php
'sparkpost' => [
    'secret' => env('SPARKPOST_SECRET'),
    'guzzle' => [
        'timeout' => 10,
    ],
],
```

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
