<?php

namespace PaymentBundle\Manager;

use DbBundle\Entity\Gateway;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\GatewayRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Payum\Core\Payum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentManager
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Payum
     */
    private $payum;

    /**
     *
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(Payum $payum, Registry $registry, RequestStack $requestStack)
    {
        $this->doctrine = $registry;
        $this->payum = $payum;
        $this->requestStack = $requestStack;
    }

    public function processPaymentOption(Transaction $transaction)
    {
        if ($transaction->getDetail('payment.status', false) === false) {
            $paymentOption = $transaction->getPaymentOptionType();
            $currency = $transaction->getCustomer()->getCurrency();

            /* @var  $gateway \DbBundle\Entity\Gateway */
            $gateway = $this->getGatewayRepository()->findOneBy([
                'currency' => $currency,
                'paymentOption' => $paymentOption,
            ]);

            $storage = $this->payum->getStorage('PaymentBundle\Model\Payment');
            /* @var $payment \PaymentBundle\Model\Payment */
            $payment = $storage->create();
            $payment['number'] = $transaction->getNumber();
            $payment['amount'] = number_format($transaction->getAmount(), 2, '.', '');
            $payment['currency'] = $currency->getCode();
            $payment['email'] = $transaction->getCustomer()->getUser()->getEmail();
            $payment['clientId'] = $transaction->getCustomer()->getId();

            $details = $gateway->getConfig();
            $details = array_merge($details, [
                'customerIdAtMerchant' => $transaction->getCustomer()->getId(),
                'transactionId' => $transaction->getNumber(),
            ]);

            if ($this->getCurrentRequest()->request->has('returnUrl')) {
                $details['returnUrl'] = $this->getCurrentRequest()->get('returnUrl');
            }

            if ($this->getCurrentRequest()->request->has('cancelUrl')) {
                $details['cancelUrl'] = $this->getCurrentRequest()->get('cancelUrl');
            }
            $payment['transaction'] = $transaction;
            $payment['gateway'] = $gateway;

            foreach ($details as $key => $detail) {
                $payment[$key] = $detail;
            }

            $storage->update($payment);

            $captureToken = $this->payum->getTokenFactory()->createCaptureToken(
                $gateway->getGatewayName(),
                $payment,
                'payment_done'
            );

            $gatewayPayum = $this->payum->getGateway($captureToken->getGatewayName());

            $gatewayPayum->execute(new \Payum\Core\Request\Capture($captureToken));

            if ($gateway->getFactoryName('offline') === 'offline') {
                $storage->delete($payment);
            }
        }
    }

    private function getGatewayRepository(): GatewayRepository
    {
        return $this->doctrine->getRepository(Gateway::class);
    }

    private function getCurrentRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
