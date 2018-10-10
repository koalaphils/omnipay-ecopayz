<?php

namespace AppBundle\Helper;

use Firebase\JWT\JWT;
use Rx\Observable;
use React\Promise\Deferred;
use WebSocketBundle\Client\Client;

class Publisher
{
    const AUTH_ID = 'BACK_OFFICE';

    private $websocketUrl;
    private $websocketRealm;
    private $jwtKey;

    public function __construct($websocketUrl, $websocketRealm, $jwtKey)
    {
        $this->websocketUrl = $websocketUrl;
        $this->websocketRealm = $websocketRealm;
        $this->jwtKey = $jwtKey;
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
}
