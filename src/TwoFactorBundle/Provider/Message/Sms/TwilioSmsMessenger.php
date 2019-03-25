<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

use Twilio\Rest\Client;

class TwilioSmsMessenger implements SmsMessengerInterface
{
    /**
     * @var string
     */
    private $sid;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $from;

    /**
     * @var Client
     */
    private $client;

    public function __construct(string $sid, string $token, string $from)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;
    }

    public function send(string $message, string $to, string $from = ''): void
    {
        $client = $this->getClient();
        $client->messages->create(
            $to,
            [
                'from' => ($from === '') ? $this->from : $from,
                'body' => $message,
            ]
        );
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($this->sid, $this->token);
        }

        return $this->client;
    }
}