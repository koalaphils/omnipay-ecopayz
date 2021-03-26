<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PaymentBundle\Component\Blockchain;

use PaymentBundle\Service\Blockchain;
use Psr\Http\Message\ResponseInterface;

/**
 * Description of BlockchainComponent
 *
 * @author cydrick
 */
class BlockchainComponent
{
    protected $blockchain;
    
    public function __construct(BlockchainInterface $blockchain)
    {
        $this->blockchain = $blockchain;
    }
    
    protected function get(string $path, array $query = [], array $params = []): ResponseInterface
    {
        return $this->blockchain->get($path, $query, $params);
    }
    
    protected function post(string $path, array $postData = [], array $params = []): ResponseInterface
    {
        return $this->blockchain->post($path, $postData, $params);
    }
    
    protected function apiGet(string $path, array $query = [], array $headers = []): ResponseInterface
    {
        return $this->blockchain->apiGet($path, $query, $headers);
    }
    
    protected function apiPost(string $path, array $postData = [], array $params = []): ResponseInterface
    {
        return $this->blockchain->apiPost($path, $postData, $params);
    }
    
    protected function getBlockchain(): BlockchainInterface
    {
        return $this->blockchain;
    }
}
