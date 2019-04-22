<?php

namespace AppBundle\Helper;

use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use JMS\JobQueueBundle\Entity\Job;
use Rx\Observable;
use React\Promise\Deferred;
use Symfony\Component\Process\Process;
use WebSocketBundle\Client\Client;
use WebSocketBundle\Command\PublishCommand;

class Publisher
{
    const AUTH_ID = 'BACK_OFFICE';

    /**
     * @var string
     */
    private $websocketUrl;

    /**
     * @var string
     */
    private $websocketRealm;

    /**
     * @var string
     */
    private $jwtKey;

    /**
     * @var string
     */
    private $wampUrl;

    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager, string $websocketUrl, string $websocketRealm, string $jwtKey, string $kernelEnvironment)
    {
        $this->websocketUrl = $websocketUrl;
        $this->websocketRealm = $websocketRealm;
        $this->jwtKey = $jwtKey;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->entityManager = $entityManager;
    }
    
    public function setWampUrl(string $wampUrl): void
    {
        $this->wampUrl = $wampUrl;
    }

    // TODO: Handle challenge or websocket error. This might cause our request to never return a response
    // This will confused the user as the transaction is actually submitted.
    // Edit: This is an open issue on RxThruwayClient and really don't know when the development would start.
    // See. https://github.com/voryx/RxThruwayClient/issues/4
    public function publish(string $topic, string $data)
    {
        $deferrer = new Deferred();

        // TODO: If conection failed, just defer the promise.
        $client = new Client($this->websocketUrl, $this->websocketRealm, ['authmethods' => ['jwt']]);
        // TODO: If challenge failed, just defer the promise.
        $client->onChallenge(function (Observable $challenge) {
            return $challenge->map(function ($args) {
                $token = [
                    'authid' => self::AUTH_ID,
                ];

                $jwt = JWT::encode($token, $this->jwtKey);
                return $jwt;
            });
        });


        $client->publish($topic, $data);

        return $deferrer->promise();
    }
    
    public function publishUsingWamp(string $topic, array $args): void
    {
        $data = ['topic' => $topic, 'args' => [$args]];
        $encodedData = json_encode($data);

        $process = new Process([
            'curl',
            '-H', 'Content-Type: application/json',
            '-d', $encodedData,
            $this->wampUrl . '/pub'
        ]);

        $process->run();
    }

    public function addPublishToQueue(string $topic, array $args): void
    {
        $encodedData = json_encode($args);
        $command = PublishCommand::COMMAND_NAME;
        $job = new Job($command, [$topic, $encodedData, '--env', $this->kernelEnvironment]);

        $this->entityManager->persist($job);
        $this->entityManager->flush($job);
    }
}
