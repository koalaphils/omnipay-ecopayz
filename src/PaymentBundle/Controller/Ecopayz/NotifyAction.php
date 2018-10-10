<?php

namespace PaymentBundle\Controller\Ecopayz;

use ArrayAccess;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerPaymentOption;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Omnipay\Common\GatewayInterface;
use Omnipay\Ecopayz\Message\CompletePurchaseRequest;
use Omnipay\Ecopayz\Message\CompletePurchaseResponse;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Payum;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use TransactionBundle\Manager\TransactionManager;

class NotifyAction implements ApiAwareInterface, ActionInterface
{
    const ECOPAYZ_NOTIFY_INVALID = 1;
    const ECOPAYZ_NOTIFY_DECLINED_BY_CUSTOMER = 2;
    const ECOPAYZ_NOTIFY_TRANSACTION_FAILED = 3;
    const ECOPAYZ_NOTIFY_REQUIRES_MERCHANT_CONFIRMATION = 4;
    const ECOPAYZ_NOTIFY_TRANSACTION_CANCELLED = 5;

    use ContainerAwareTrait;
    use ApiAwareTrait;

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->apiClass = GatewayInterface::class;
        $this->logger = $logger;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $payment = $request->getModel();
        $details = ArrayObject::ensureArrayObject($payment);
        /* @var $completeRequest CompletePurchaseRequest */
        $completeRequest = $this->api->completePurchase($details->toUnsafeArray());
        $validitionResponse = $this->validateRequest($completeRequest, $this->getCurrentRequest()->get('XML'), $details);
        if ($validitionResponse !== '') {
            $response = $completeRequest->createResponse('InvalidRequest', 1000, $validitionResponse);
            $this->logger->critical("Ecopayz: " . $validitionResponse);
        } elseif (!$this->requestIsConfirmation($completeRequest)) {
            $response = $completeRequest->createResponse('Cancelled', 1000, $validitionResponse);
        } else {
            $requestAccountid = $this->getFieldData($completeRequest->getData(), 'StatusReport.SVSTransaction.SVSCustomerAccount')->__toString();
            $transaction = $this->initializeRelation($details['transaction']);
            $this->loginUser($transaction->getCustomer()->getUser());
            $customerPaymentOption = $this->getCustomerPaymentOptionEcopayzUsed($transaction , $completeRequest);

            $transaction->setPaymentOptionOnTransaction($customerPaymentOption);
            $transaction->setDetail('payment', [
                'status' => 'OK',
                'ecopayz' => [
                    'transactionId' => $this->getFieldData($completeRequest->getData(), 'StatusReport.SVSTransaction.Id')->__toString(),
                    'transactionBatchNumber' => $this->getFieldData($completeRequest->getData(), 'StatusReport.SVSTransaction.BatchNumber')->__toString(),
                ],
            ]);
            $transaction->retainImmutableData();

            try {
                $this->beginTransaction();
                $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];
                $this->getTransactionManager()->processTransaction($transaction, $action, true);
                $storage = $this->getPayum()->getStorage(get_class($payment));
                $storage->delete($payment);
                $this->commit();
                $response = (string) $this->sendData($completeRequest);
            } catch (\Exception $e) {
                $this->rollback();
                $response = $completeRequest->createResponse('InvalidRequest', 1001, $e->getMessage());
                $this->logger->critical("Ecopayz: " . $e->getMessage(), $e->getTrace());
            }
            $response = str_replace("\n", '', $response);
            $response = substr($response, 0);

            throw new HttpResponse($response, 200);
        }
    }

    private function requestIsConfirmation(CompletePurchaseRequest $request): int
    {
        $status = (int) $this->getFieldData($request->getData(), 'StatusReport.Status')->__toString();

        return $status === self::ECOPAYZ_NOTIFY_REQUIRES_MERCHANT_CONFIRMATION;
    }

    private function getCustomerPaymentOptionEcopayzUsed(Transaction $transaction, CompletePurchaseRequest $request): CustomerPaymentOption
    {

        $customer = $transaction->getCustomer();
        $paymentOptions = $customer->getPaymentOptions();
        $paymentOptionId = $transaction->getPaymentOption()->getId();

        foreach ($paymentOptions as $paymentOption) {
            $requestAccountid = $this->getFieldData($request->getData(), 'StatusReport.SVSTransaction.SVSCustomerAccount')->__toString();
            if ($paymentOption->getPaymentOption()->isPaymentEcopayz() && $paymentOption->getField('account_id') == $requestAccountid) {
                return $this->updateAsDefaultPaymentOption($paymentOption->getId(), $requestAccountid);
            }
        }

        return $this->createDefaultPaymentOption($customer, $requestAccountid);
    }

    private function updateAsDefaultPaymentOption(int $paymentOptionId, int $accountId): CustomerPaymentOption
    {
        $customerPaymentOption = $this->getCustomerPaymentOptionRepository()->find($paymentOptionId);
        $customerPaymentOption->setField('email', '');
        $customerPaymentOption->setField('account_id', $accountId);
        $customerPaymentOption->enable();
        $this->getEntityManager()->persist($customerPaymentOption);
        $this->getEntityManager()->flush($customerPaymentOption);

        $this->getCustomerPaymentOptionRepository()
            ->disableOldPaymentOption(
                $customerPaymentOption->getId(),
                $customerPaymentOption->getCustomer()->getId(),
                strtoupper(PaymentOption::PAYMENT_MODE_ECOPAYZ))
            ;

        return $customerPaymentOption;
    }

    private function createDefaultPaymentOption(Customer $customer, int $accountId): CustomerPaymentOption
    {
        $customerPaymentOption = new CustomerPaymentOption();
        $customerPaymentOption->setCustomer($customer);
        $paymentOption = $this->getEntityManager()->getReference(
            PaymentOption::class,
            strtoupper(PaymentOption::PAYMENT_MODE_ECOPAYZ)
        );
        $customerPaymentOption->enable();
        $customerPaymentOption->setPaymentOption($paymentOption);
        $customerPaymentOption->addField('account_id', $accountId);
        $customerPaymentOption->addField('email', '');
        $customerPaymentOption->addField('notes', '');
        $this->getEntityManager()->persist($customerPaymentOption);
        $this->getEntityManager()->flush($customerPaymentOption);

        $this->getCustomerPaymentOptionRepository()
            ->disableOldPaymentOption(
                $customerPaymentOption->getId(),
                $customerPaymentOption->getCustomer()->getId(),
                strtoupper(PaymentOption::PAYMENT_MODE_ECOPAYZ))
            ;

        return $customerPaymentOption;
    }

    private function initializeRelation(Transaction $transaction)
    {
        /* @var $mergeTransaction Transaction */
        $customer = $this->getEntityManager()->merge($transaction->getCustomer());
        $this->initializeMember($customer);
        $transaction->setCustomer($customer);
        $currency = $this->getEntityManager()->merge($transaction->getCurrency());
        $transaction->setCurrency($currency);
        $paymentOption = $this->getCustomerPaymentOptionRepository()->find($transaction->getPaymentOption()->getId());
        $this->getEntityManager()->initializeObject($paymentOption->getPaymentOption());
        $transaction->setPaymentOption($paymentOption);
        $paymentOptionType = $this->getEntityManager()->merge($transaction->getPaymentOptionType());
        $transaction->setPaymentOptionType($paymentOptionType);
        if ($transaction->getCreator() !== null) {
            $creator = $this->getEntityManager()->merge($transaction->getCreator());
            $transaction->setCreator($creator);
        }

        foreach ($transaction->getSubTransactions() as $subTransaction) {
            $customerProduct = $this->getCustomerProductRepository()->findById($subTransaction->getCustomerProduct()->getId());
            $subTransaction->setCustomerProduct($customerProduct);
        }

        return $transaction;
    }

    private function initializeMember(Customer $member): void
    {
        if ($member->getAffiliate() instanceof Customer) {
            $referrer = $this->getMemberRepository()->find($member->getAffiliate()->getId());
            $this->getEntityManager()->initializeObject($referrer->getUser());
            $member->setReferrer($referrer);
        }
    }

    private function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'payment', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
    }

    private function sendData($request)
    {
        $data = $request->getData();
        if ($this->hasField($data, 'StatusReport')) {
            if (in_array($this->getFieldData($data, 'StatusReport.Status'), array(1, 2, 3))) {
                $response = $request->createResponse('OK', 0, 'OK');
            } elseif (in_array($this->getFieldData($data, 'StatusReport.Status'), array(4, 5))) {
                $response = $request->createResponse('Confirmed', 0, 'Confirmed');
            } else {
                $response = $request->createResponse('InvalidRequest', 99, 'Invalid StatusReport/Status');
            }

            return $response;
        } else {
            return new CompletePurchaseResponse($request, $data);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof ArrayAccess && (
                method_exists($this->api, 'completePurchase') ||
                method_exists($this->api, 'acceptNotification')
            )
        ;
    }

    private function getFieldData($data, $field)
    {
        if (is_null($field)) {
            return $data;
        }

        if (isset($data->{$field})) {
            return $data->{$field};
        }

        foreach (explode('.', $field) as $segment) {
            if (!isset($data->{$segment})) {
                return false;
            }

            $data = $data->{$segment};
        }

        return $data;
    }

    private function hasField($data, $field)
    {
        if (is_null($field)) {
            return false;
        }

        if (isset($data->{$field})) {
            return true;
        }

        foreach (explode('.', $field) as $segment) {
            if (!isset($data->{$segment})) {
                return false;
            }

            $data = $data->{$segment};
        }

        return true;
    }

    private function validateRequest(CompletePurchaseRequest $request, $data, $original): string
    {
        $response = '';

        if (!$request->validateChecksum($data)) {
            $response = 'Invalid checksum';
        }

        return $response;
    }

    private function getCurrentRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    private function getPayum(): Payum
    {
        return $this->container->get('payum');
    }

    private function getTransactionManager(): TransactionManager
    {
        return $this->container->get('transaction.manager');
    }

    private function beginTransaction()
    {
        $this->getDoctrine()->getManager()->beginTransaction();
    }

    private function commit()
    {
        $this->getDoctrine()->getManager()->commit();
    }

    private function rollback()
    {
        $this->getDoctrine()->getManager()->rollback();
    }

    private function getCustomerPaymentOptionRepository(): CustomerPaymentOptionRepository
    {
        return $this->getEntityManager()->getRepository(CustomerPaymentOption::class);
    }

    private function getCustomerProductRepository(): CustomerProductRepository
    {
        return $this->getEntityManager()->getRepository(CustomerProduct::class);
    }

    private function getMemberRepository(): MemberRepository
    {
        return $this->getEntityManager()->getRepository(Customer::class);
    }

    private function getEntityManager($name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    private function getDoctrine(): Registry
    {
        return $this->container->get('doctrine');
    }
}
