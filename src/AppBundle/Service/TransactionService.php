<?php

namespace AppBundle\Service;

use ApiBundle\Service\JWTGeneratorService;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;

class TransactionService
{
    private $http;
    private $jwtGenerator;

    public function __construct(HttpService $http, JWTGeneratorService $jwtGenerator)
    {
        $this->http = $http;
        $this->jwtGenerator = $jwtGenerator;
    }

    private function getBearer(array $payload = [])
    {
        $jwt = $this->jwtGenerator->generate($payload);

        return 'Bearer ' . $jwt;
    }


    public function create(array $payload)
    {
        try {
            $this->http->post("/api/v1/transaction/", [
                'json' => $payload,
                'headers' => [
                    'Authorization' => $this->getBearer([
                        'roles' => ['ROLE_TRANSACTION_CREATE']
                    ]),
                ]
            ]);
        } catch (ServerException $exception) {
           throw $exception;
        } catch (ClientException $exception) {
            throw $exception;
        }
    }
}
