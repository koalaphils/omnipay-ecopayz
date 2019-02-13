<?php

namespace PaymentBundle\Component\Blockchain;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class BlockchainGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'blockchain',
            'payum.factory_title' => 'blockchain',
        ]);
    }
}
