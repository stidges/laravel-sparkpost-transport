<?php

namespace Stidges\SparkpostTransport;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class SparkpostTransport extends AbstractTransport
{
    protected const API_ENDPOINT = 'https://%s/api/v1/transmissions';

    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $client;

    /**
     * The SparkPost API key.
     *
     * @var string
     */
    protected string $key;

    /**
     * The SparkPost API domain.
     *
     * @var string
     */
    protected string $domain;

    /**
     * The SparkPost transmission options.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Create a new SparkPost transport instance.
     *
     * @param  ClientInterface  $client
     * @param  string  $key
     * @param  string  $domain
     * @param  array  $options
     * @return void
     */
    public function __construct(ClientInterface $client, string $key, string $domain, array $options = [])
    {
        $this->key = $key;
        $this->client = $client;
        $this->domain = $domain;
        $this->options = $options;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $response = $this->client->request('POST', $this->getEndpoint(), [
            'headers' => [
                'Authorization' => $this->key,
            ],
            'json' => array_merge([
                'recipients' => $this->getRecipients($message->getEnvelope()),
                'content' => [
                    'email_rfc822' => $message->toString(),
                ],
            ], $this->options),
        ]);

        $message->getOriginalMessage()->getHeaders()->addHeader(
            'X-Sparkpost-Transmission-ID', $this->getTransmissionId($response)
        );
    }

    /**
     * Get all the addresses this message should be sent to.
     *
     * @param  \Symfony\Component\Mailer\Envelope  $envelope
     * @return array
     */
    protected function getRecipients(Envelope $envelope): array
    {
        $recipients = [];

        foreach ($envelope->getRecipients() as $recipient) {
            $recipients[] = [
                'address' => [
                    'name' => $recipient->getName(),
                    'email' => $recipient->getAddress(),
                ],
            ];
        }

        return $recipients;
    }

    /**
     * Get the transmission ID from the response.
     *
     * @param  \GuzzleHttp\Psr7\Response  $response
     * @return string
     *
     * @throws \JsonException
     */
    protected function getTransmissionId(Response $response): string
    {
        return object_get(
            json_decode($response->getBody()->getContents(), flags: JSON_THROW_ON_ERROR), 'results.id'
        );
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'sparkpost';
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string  $key
     * @return string
     */
    public function setKey(string $key): string
    {
        return $this->key = $key;
    }

    /**
     * Get the API domain being used by the transport.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set the API domain being used by the transport.
     *
     * @param  string  $domain
     * @return string
     */
    public function setDomain(string $domain): string
    {
        return $this->domain = $domain;
    }

    /**
     * Get the SparkPost API endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return sprintf(self::API_ENDPOINT, $this->domain);
    }

    /**
     * Get the Guzzle client instance.
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options): array
    {
        return $this->options = $options;
    }
}
