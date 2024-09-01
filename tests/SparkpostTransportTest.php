<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Testing\FileFactory;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Stidges\SparkpostTransport\SparkpostTransport;

it('sends an e-mail via the Sparkpost Transmissions API', function () {
    ['client' => $client, 'sentRequests' => $sentRequests] = createGuzzleClient([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'results' => ['id' => 'MESSAGE_ID'],
        ], JSON_THROW_ON_ERROR)),
    ]);
    Mail::extend('sparkpost', fn () => new SparkPostTransport($client, 'secret_key', 'api.sparkpost.com'));
    $attachmentA = (new FileFactory)->image('attachment-a.png', 100, 100);
    $attachmentB = (new FileFactory)->image('attachment-b.png', 50, 50);

    $message = Mail::mailer('sparkpost')
        ->to([
            ['name' => 'Recipient A', 'email' => 'recipient-a@example.com'],
            ['name' => 'Recipient B', 'email' => 'recipient-b@example.com'],
        ])
        ->cc([
            ['name' => 'CC A', 'email' => 'cc-a@example.com'],
            ['name' => 'CC B', 'email' => 'cc-b@example.com'],
        ])
        ->bcc([
            ['name' => 'BCC A', 'email' => 'bcc-a@example.com'],
            ['name' => 'BCC B', 'email' => 'bcc-b@example.com'],
        ])
        ->send(new class([$attachmentA, $attachmentB]) extends Mailable
        {
            public function __construct(private array $attachmentsToSend) {}

            public function build()
            {
                foreach ($this->attachmentsToSend as $attachment) {
                    $this->attach($attachment, ['as' => $attachment->name, 'mime' => 'image/png']);
                }

                return $this->html('HTML content')
                    ->text(new HtmlString('Text content'))
                    ->subject('Mail subject')
                    ->from('sender@example.com', 'Sender name')
                    ->replyTo('reply-to@example.com', 'Reply To');
            }
        });

    expect($message)->toBeInstanceOf(SentMessage::class)
        ->and($message->getOriginalMessage()->getHeaders()->getHeaderBody('X-Sparkpost-Transmission-ID'))->toBe('MESSAGE_ID');
    expect($sentRequests)->toHaveCount(1);
    /** @var \GuzzleHttp\Psr7\Request $request */
    $request = $sentRequests[0]['request'];
    expect($request)
        ->getUri()->toEqual('https://api.sparkpost.com/api/v1/transmissions')
        ->getHeaderLine('authorization')->toBe('secret_key')
        ->getHeaderLine('content-type')->toBe('application/json')
        ->and(json_decode($request->getBody()->getContents(), true))->toEqual([
            'recipients' => [
                ['address' => ['email' => 'recipient-a@example.com', 'name' => 'Recipient A']],
                ['address' => ['email' => 'recipient-b@example.com', 'name' => 'Recipient B']],

                ['address' => ['email' => 'cc-a@example.com', 'name' => 'CC A']],
                ['address' => ['email' => 'cc-b@example.com', 'name' => 'CC B']],

                ['address' => ['email' => 'bcc-a@example.com', 'name' => 'BCC A']],
                ['address' => ['email' => 'bcc-b@example.com', 'name' => 'BCC B']],
            ],
            'content' => [
                'email_rfc822' => $message->toString(),
            ],
        ]);
    expect($message->toString())
        ->not->toContain('bcc-a@example.com')
        ->not->toContain('bcc-b@example.com');
});

it('allows customizing the Sparkpost domain', function () {
    ['client' => $client, 'sentRequests' => $sentRequests] = createGuzzleClient([
        new Response(200, ['Content-Type' => 'application/json'], '{"results":{"id":"MESSAGE_ID"}}'),
    ]);
    Mail::extend('sparkpost', fn () => new SparkPostTransport($client, 'secret_key', 'api.eu.sparkpost.com'));

    sendSimpleEmail();

    expect($sentRequests)->toHaveCount(1);
    /** @var \GuzzleHttp\Psr7\Request $request */
    $request = $sentRequests[0]['request'];
    expect($request->getUri())->toEqual('https://api.eu.sparkpost.com/api/v1/transmissions');
});

/** @test */
it('allows customizing the request body', function () {
    ['client' => $client, 'sentRequests' => $sentRequests] = createGuzzleClient([
        new Response(200, ['Content-Type' => 'application/json'], '{"results":{"id":"MESSAGE_ID"}}'),
    ]);
    Mail::extend('sparkpost', fn () => new SparkPostTransport($client, 'secret_key', 'api.sparkpost.com', [
        'options' => [
            'click_tracking' => false,
        ],
        'description' => 'Test',
    ]));

    sendSimpleEmail();

    expect($sentRequests)->toHaveCount(1);
    /** @var \GuzzleHttp\Psr7\Request $request */
    $request = $sentRequests[0]['request'];
    $body = json_decode($request->getBody()->getContents(), true);
    expect($body['options'])->toEqual(['click_tracking' => false]);
    expect($body['description'])->toEqual('Test');
});

/**
 * @param  Response[]  $responses
 * @return array{client: Client, sentRequests: \Illuminate\Support\Collection}
 */
function createGuzzleClient(array $responses): array
{
    $handler = HandlerStack::create(new MockHandler($responses));

    $sentRequests = collect();
    $history = Middleware::history($sentRequests);

    $handler->push($history);

    return [
        'client' => new Client(['handler' => $handler]),
        'sentRequests' => $sentRequests,
    ];
}

function sendSimpleEmail(): SentMessage
{
    return Mail::mailer('sparkpost')
        ->to([['name' => 'Recipient', 'email' => 'recipient@example.com']])
        ->send(new class extends Mailable
        {
            public function build()
            {
                return $this->html('HTML content')->text(new HtmlString('Text content'));
            }
        });
}
