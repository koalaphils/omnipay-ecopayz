<?php

namespace PaymentBundle\Component\Blockchain;

use PaymentBundle\Component\Blockchain\Model\BlockchainTransaction;

class Explorer extends BlockchainComponent
{
    private const RAWTX_PATH = '/rawtx/{hash}';

    public function getTransaction(string $hash): BlockchainTransaction
    {
        $path = str_replace('{hash}', $hash, self::RAWTX_PATH);
        $result = \GuzzleHttp\json_decode($this->get($path, ['format' => 'json'])->getBody(), true);

        return BlockchainTransaction::create($result);
    }
}
