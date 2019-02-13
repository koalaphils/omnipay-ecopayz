<?php

namespace PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function doneAction()
    {
    }

    public function testAction()
    {
        $response = $this->getBlockchain()->post('/merchant/1f12bd88-6591-45cf-8af8-780a26774288/new_address', [
            'password' => 'Cyd@zmtsys.293',
            'label' => 'test',
        ]);

        return new \Symfony\Component\HttpFoundation\Response($response->getBody()->getContents());
    }

    public function getBlockchain(): \PaymentBundle\Service\Blockchain
    {
        return $this->get('payment.blockchain');
    }
}
