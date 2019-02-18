<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PaymentBundle\Component\Blockchain;

/**
 * Description of RecievePayment
 *
 * @author cydrick
 */
class ReceivePayment extends BlockchainComponent
{
    public const RECEIVE_PATH = '/v2/receive';
    public const GAPCHECK_PATH = '/v2/receive/checkgap';

    public function generateReceivingAddress(string $callbackUrl, string $xpub, int $gabLimit = 0): array
    {
        $query = [
            'xpub' => $xpub,
            'callback' => $callbackUrl,
            'key' => $this->getBlockchain()->getApiKey(),
        ];

        if ($gabLimit > 0) {
            $query['gap_limit'] = $gabLimit;
        }

        $response = $this->apiGet(self::RECEIVE_PATH, $query);

        return \GuzzleHttp\json_decode($response->getBody(), true);
    }

    public function checkGap(string $xpub): int
    {
        $query = [
            'xpub' => $xpub,
            'key' => $this->getBlockchain()->getApiKey(),
        ];

        $response = $this->apiGet(self::GAPCHECK_PATH, $query);
        // pending: waiting supplied access token api
        $content = \GuzzleHttp\json_decode($response->getBody(), true);

        return (int) $content['gap'];
    }
}
