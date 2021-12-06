<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PaymentOptionController extends Controller
{
    public function searchAction(Request $request)
    {
        $poService = $this->get('app.service.payment_option_service');
        $paymentOptions = array_map(function($po) {
            return [
                'id' => $po['code'],
                'text' => $po['name']
            ];
        }, $poService->getAllPaymentOptions());
        
        return $this->json(['items' => $paymentOptions]);
    }
}
