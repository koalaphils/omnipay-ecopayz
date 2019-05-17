<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

use Psr\Log\LoggerInterface;
use Twilio\Exceptions\RestException;
use Twilio\Exceptions\TwilioException;
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, string $sid, string $token, string $from)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;
        $this->logger = $logger;
    }

    public function send(string $message, string $to, string $from = ''): void
    {
        $client = $this->getClient();
        try {
            $messageInstance = $client->messages->create(
                $to,
                [
                    'from' => ($from === '') ? $this->from : $from,
                    'body' => $message,
                ]
            );
            $logData = [
                'properties' => $messageInstance->toArray(),
                'solutions' => $messageInstance->__toString(),
            ];
            $this->logger->debug('Twilio Send Message', $logData);
        } catch (RestException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e->getTrace(), 'statuscode' => $e->getStatusCode()]);
        }

    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($this->sid, $this->token);
        }

        return $this->client;
    }
}