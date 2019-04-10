<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;


class TokenGenerator
{
    /**
     * @var string
     */
    private $agentCode;

    /**
     * @var string
     */
    private $agentKey;

    /**
     * @var string
     */
    private $secretKey;

    public function __construct(string $agentCode, string $agentKey, string $secretKey)
    {
        $this->agentCode = $agentCode;
        $this->agentKey = $agentKey;
        $this->secretKey = $secretKey;
    }

    public function generate(): string
    {
        $timestamp = time() * 1000;
        $hashToken = md5($this->agentCode . $timestamp . $this->agentKey);
        $tokenPayload = $this->agentCode . "|" . $timestamp . "|" . $hashToken;

        return $this->encrypt($tokenPayload);
    }

    public function encrypt(string $tokenPayload): string
    {
        $iv = 'RandomInitVector';
        $encrypt = openssl_encrypt($tokenPayload, 'AES-128-CBC', $this->secretKey, OPENSSL_RAW_DATA, $iv);

        return base64_encode($encrypt);
    }
}
