<?php

namespace PaymentBundle\Controller\Bitcoin;

use ArrayAccess;
use AppBundle\Service\CustomerPaymentOptionService;
use AppBundle\Service\PaymentOptionService;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\Transaction;
use Http\Client\Exception\HttpException;
use MemberBundle\Manager\MemberManager;
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
     * @var string
     */
    private $routeName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MemberManager
     */
    private $memberManager;

	/**
	 * @var CustomerPaymentOptionService
	 */
	private $cpoService;

	/**
	 * @var PaymentOptionService
	 */
	private $poService;

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = $request->getModel();
        /* @var $transaction Transaction */
        $transaction = $model['transaction'];

        /* @var $member \DbBundle\Entity\Customer */
        $member = $transaction->getCustomer();
        $notifyToken = $this->tokenFactory->createToken(
            $request->getToken()->getGatewayName(),
            $member,
            $this->routeName
        );

        $callback = $notifyToken->getTargetUrl();
        try
        {
	        $receivingAddress = $this->poService->getReceiveAddress('BITCOIN', $callback);
	        $member->setBitcoinDetails($receivingAddress);
            $this->memberManager->save($member);
        }
		catch (HttpException $e)
		{
            $response = $e->getResponse();
            $contentType = $response->getHeader('Content-Type')[0];
            $message = $response->getBody()->getContents();
            if ($contentType === 'application/json') {
                $message = json_decode($message);
            }

            $this->logger->critical($e->getMessage(), ['exception' => $e]);

            throw new HttpResponse(new JsonResponse([
                'error' => $message,
                'thrown_message' => $e->getMessage(),
            ], $response->getStatusCode()));
        }

	    $transaction->setBitcoinAddress($receivingAddress['address']);
	    $transaction->setBitcoinCallback($receivingAddress['callback']);
	    $transaction->setBitcoinIndex($receivingAddress['index']);
        $transaction->setBitcoinRateExpiration(false);
        $transaction->setBitcoinAcknowledgedByUser(false);
	    $cpoDetails = $this->getCustomerPaymentOptionDetails($member, $receivingAddress['address']);
	    $transaction->setPaymentOptionDetails($cpoDetails['onTransaction']);
	    $transaction->setPaymentOptionOnRecord($cpoDetails['onRecord']);
        if ($transaction->getPaymentOption()->getField('account_id', '') === '') {
            $transaction->getPaymentOption()->setField('account_id', $member->getBitcoinAddress());
        }
    }

	private function getCustomerPaymentOptionDetails($customer, $receivingAddress)
	{
		$args = [
			$customer->getId(),
			PaymentOptionService::BITCOIN,
			Transaction::TRANSACTION_TYPE_DEPOSIT,
			[ 'account_id' => $receivingAddress ],
			[ 'replace' => true ] // Replace active fields to new fields
		];

		return $this->cpoService->getCustomerPaymentOptionDetails(...$args);
	}

    public function supports($request): bool
    {
        return
            $request instanceof Capture
            && $request->getModel() instanceof ArrayAccess
            && $request->getModel()['transaction'] instanceof Transaction
            && $request->getModel()['gateway']->getGatewayName() === PaymentOption::PAYMENT_MODE_BITCOIN
            && $request->getModel()['transaction']->isNew()
        ;
    }

    public function setMemberManager(MemberManager $memberManager)
    {
        $this->memberManager = $memberManager;
    }

    public function setTokenFactory(TokenFactoryInterface $tokenFactory): void
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

	public function setCustomerPaymentOptionService(CustomerPaymentOptionService $cpoService)
	{
		$this->cpoService = $cpoService;
	}

	public function setPaymentOptionService(PaymentOptionService $poService)
	{
		$this->poService = $poService;
	}
}
