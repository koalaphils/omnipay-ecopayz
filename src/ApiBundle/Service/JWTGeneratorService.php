<?php

namespace ApiBundle\Service;

use Firebase\JWT\JWT;

class JWTGeneratorService
{
    private $jwtKey;

    public function __construct(string $jwtKey)
    {
        $this->jwtKey = $jwtKey;
    }

    public function generate(array $payload, string $algorithm = 'HS256')
    {
        return JWT::encode($payload, $this->jwtKey, $algorithm);
    }
}
