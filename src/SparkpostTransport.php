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
     */
    protected ClientInterface $client;

    /**
     * The SparkPost API key.
     */
    protected string $key;

    /**
     * The SparkPost API domain.
     */
    protected string $domain;

    /**
     * The SparkPost transmission options.
     */
    protected array $options = [];

    /**
     * Create a new SparkPost transport instance.
     *
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
     */
    public function __toString(): string
    {
        return 'sparkpost';
    }

    /**
     * Get the API key being used by the transport.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     */
    public function setKey(string $key): string
    {
        return $this->key = $key;
    }

    /**
     * Get the API domain being used by the transport.
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set the API domain being used by the transport.
     */
    public function setDomain(string $domain): string
    {
        return $this->domain = $domain;
    }

    /**
     * Get the SparkPost API endpoint.
     */
    public function getEndpoint(): string
    {
        return sprintf(self::API_ENDPOINT, $this->domain);
    }

    /**
     * Get the Guzzle client instance.
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Get the transmission options being used by the transport.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     */
    public function setOptions(array $options): array
    {
        return $this->options = $options;
    }
}
