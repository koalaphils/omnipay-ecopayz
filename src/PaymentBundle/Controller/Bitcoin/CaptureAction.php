<?php

namespace PaymentBundle\Controller\Bitcoin;

use ArrayAccess;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\Transaction;
use Http\Client\Exception\HttpException;
use PaymentBundle\Component\Blockchain\Wallet\Credentials;
use PaymentBundle\Service\Blockchain;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Symfony\Reply\HttpResponse;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use function GuzzleHttp\json_decode;

class CaptureAction implements ActionInterface
{
    /**
     * @var TokenFactoryInterface
     */
    private $tokenFactory;

    /**
     * @var Blockchain
     */
    private $blockchain;

    /**
     * @var string
     */
    private $routeName;

    /**
     * @var string
     */
    private $cycleFundRouteName;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = $request->getModel();
        $transaction = $model['transaction'];

        /* @var $member \DbBundle\Entity\Customer */
        $member = $transaction->getCustomer();

        $notifyToken = $this->tokenFactory->createToken(
            $request->getToken()->getGatewayName(),
            $member,
            $this->routeName
        );

        $callback = $notifyToken->getTargetUrl();

        if (!$member->equalsToBitcoinCallback($callback)) {
            $payment = $request->getModel();
            $gateway = $payment['gateway'];
            $xpub = $gateway->getConfig()['receiverXpub'];

            try {
                $result = $this->generateReceivingAddress($callback, $xpub, $gateway);
                $member->setBitcoinDetails($result);
            } catch (HttpException $e) {
                $response = $e->getResponse();
                $contentType = $response->getHeader('Content-Type')[0];
                $message = $response->getBody()->getContents();
                if ($contentType === 'application/json') {
                    $message = json_decode($message);
                }

                throw new HttpResponse(new JsonResponse([
                    'error' => $message,
                    'thrown_message' => $e->getMessage(),
                ], $response->getStatusCode()));
            }
        }

        $transaction->setBitcoinAddress($member->getBitcoinAddress());
        $transaction->setBitcoinCallback($member->getBitcoinCallback());
        $transaction->setBitcoinIndex($member->getBitcoinIndex());
        $transaction->setBitcoinRateExpiration(false);
        $transaction->setBitcoinAcknowledgedByUser(false);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture
            && $request->getModel() instanceof ArrayAccess
            && $request->getModel()['transaction'] instanceof Transaction
            && $request->getModel()['gateway']->getPaymentOptionEntity()->getPaymentMode() === PaymentOption::PAYMENT_MODE_BITCOIN
            && $request->getModel()['transaction']->isNew()
        ;
    }

    public function cycleFund(Gateway $gateway): void
    {
        $configs = $gateway->getConfig();
        $credentials = Credentials::create($configs['guid'], $configs['password'], $configs['secondPassword'] ?? '');

        $receiverXpub = $gateway->getConfig()['receiverXpub'];
        $sender = $this->blockchain->getWallet()->getSingleAccount($credentials, $gateway->getConfig()['senderXpub']);

        $result = $this->blockchain->getReceivePayment()->generateReceivingAddress(
            urlencode($this->urlGenerator->generate($this->cycleFundRouteName, [], UrlGeneratorInterface::ABSOLUTE_URL)),
            $receiverXpub,
            100
        );

        $this->blockchain->getWallet()->payment($credentials, $result['address'], '0.00001', (string) $sender->getIndex());
    }

    public function setBlockchain(Blockchain $blockchain): void
    {
        $this->blockchain = $blockchain;
    }

    public function setTokenFactory(TokenFactoryInterface $tokenFactory): void
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function setCycleFundRouteName(string $routeName): void
    {
        $this->cycleFundRouteName = $routeName;
    }

    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function generateReceivingAddress(string $callback, string $xpub, Gateway $gateway): array
    {
        $currentGap = $this->blockchain->getReceivePayment()->checkGap($xpub);
        if ($currentGap === 19) {
            $this->cycleFund($gateway);
        }

        $receivingAddress = $this->blockchain->getReceivePayment()->generateReceivingAddress(
            urlencode($callback),
            $xpub
        );

        return $receivingAddress;
    }
}
