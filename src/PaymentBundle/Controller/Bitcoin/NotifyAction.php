<?php

namespace PaymentBundle\Controller\Bitcoin;

use AppBundle\Helper\Publisher;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PaymentBundle\Component\Blockchain\BitcoinConverter;
use PaymentBundle\Event\NotifyEvent;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Service\Blockchain;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Payum;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Throwable;

class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public const PUBLISH_CHANNEL = 'btc.request_status';
    private const BLOCKCHAIN_OK_RESPONSE = '*ok*';

    private $transactionRepository;
    private $memberRepository;
    private $payum;
    private $tokenStorage;
    private $publisher;
    private $blockchain;
    private $confirmations = [];

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var BitcoinManager
     */
    private $bitcoinManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $httpRequest = $this->getHttpRequest();
        try {
            $this->validateRequest($httpRequest);
        } catch (\RuntimeException $ex) {
            $this->logWithHttpRequest(LogLevel::ERROR, 'Invalid Request', $httpRequest, [$ex]);

            throw $this->createOkResponse();
        }
        $token = $request->getToken();

        $memberId = $token->getDetails()['memberId'];

        try {
            $member = $this->memberRepository->getById($memberId);
        } catch (NoResultException $e) {
            $this->logWithHttpRequest(LogLevel::CRITICAL, 'Member not found', $httpRequest, [$e]);

            throw $this->createOkResponse();
        } catch (NonUniqueResultException $e) {
            $this->logWithHttpRequest(LogLevel::CRITICAL, 'Multiple memebers has been found', $httpRequest, [$e]);

            throw $this->createOkResponse();
        }

        try {
            $transaction = $this->transactionRepository->getLessThanConfirmationBitcoinTransactionForMember($member->getId(), count($this->confirmations) - 1);
        } catch (NoResultException $e) {
            $this->logWithHttpRequest(
                LogLevel::ERROR,
                'Transaction Not found',
                $httpRequest,
                ['memberId' => $member->getId()]
            );

            throw $this->createOkResponse();
        } catch (NonUniqueResultException $e) {
            $this->logWithHttpRequest(
                LogLevel::ERROR,
                'Multiple transaction found',
                $httpRequest,
                ['memberId' => $member->getId()]
            );

            throw $this->createOkResponse();
        }

        $transactionHash = $transaction->getBitcoinTransactionHash();
        if ($transactionHash !== '' && $transactionHash !== $httpRequest->query['transaction_hash']) {
            $this->logWithHttpRequest(
                LogLevel::ERROR,
                sprintf(
                    'Transaction hash expecting %s, but %s was given',
                    $transactionHash,
                    $httpRequest->query['transaction_hash']
                ),
                $httpRequest,
                ['transaction' => $transaction->getId()]
            );

            throw $this->createOkResponse();
        }

        $this->loginUser($transaction->getCustomer()->getUser());
        $satoshiValue = $httpRequest->query['value'];
        $btcValue = BitcoinConverter::convertToBtc($satoshiValue);
        $address = $httpRequest->query['address'];
        $confirmations = $httpRequest->query['confirmations'];

        if ($address !== $member->getBitcoinAddress()) {
            $this->logWithHttpRequest(
                LogLevel::ERROR,
                sprintf('Expecting %s address, %s address given', $member->getBitcoinAddress(), $address),
                $httpRequest,
                ['memberId' => $member->getId()]
            );

            throw $this->createOkResponse();
        }
        $transaction->setBitcoinTransactionHash($httpRequest->query['transaction_hash']);
        $transaction->setBitcoinValue($btcValue);
        $transaction->setBitcoinValueInSatoshi($httpRequest->query['value']);
        $transaction->setBitcoinConfirmation($confirmations);
        $transaction->setBitcoinSenderAddresses(
            $this->getSenderAddresses($transaction->getBitcoinTransactionHash(), $transaction->getBitcoinAddress())
        );
        if (isset($this->confirmations[$confirmations])) {
            $transaction->setStatus($this->confirmations[$confirmations]->getConfirmationTransactionStatus());
        } else if ($confirmations > max(array_keys($this->confirmations))) {
            $transaction->setStatus($this->confirmations[max(array_keys($this->confirmations))]->getConfirmationTransactionStatus());
        } else if ($confirmations < min(array_keys($this->confirmations))) {
            $transaction->setStatus($this->confirmations[min(array_keys($this->confirmations))]->getConfirmationTransactionStatus());
        }
        $notifyEvent = new NotifyEvent(
            $transaction,
            [
                'transactionHash' => $transactionHash,
                'satoshiValue' => $satoshiValue,
                'btcValue' => $btcValue,
                'address' => $address,
                'confirmations' => $confirmations,
            ]
        );
        $this->eventDispatcher->dispatch(NotifyEvent::EVENT_NAME, $notifyEvent);
        $this->save($transaction);

        $maxConfirmation = count($this->bitcoinManager->getListOfConfirmations()) - 1;
        if ($confirmations >= $maxConfirmation) {
            $this->logWithHttpRequest(
                LogLevel::INFO,
                sprintf('Callback done, from %s confirmation', $confirmations),
                $httpRequest,
                ['transactionId' => $transaction->getId()]
            );

            $response = $this->createOkResponse();
        } else {
            $this->logWithHttpRequest(
                LogLevel::INFO,
                'Need 3 or more confirmation to finish the callback',
                $httpRequest,
                ['transactionId' => $transaction->getId()]
            );

            $response = new HttpResponse('');
        }

        $this->publishTransactionStatus($transaction);
        
        throw $response;
    }

    public function supports($request): bool
    {
        return $request instanceof Notify
            && $request->getToken() instanceof TokenInterface
            && $request->getToken()->getGatewayName() === 'bitcoin';
    }

    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function __construct(Payum $payum, TransactionRepository $transactionRepository, MemberRepository $memberRepository)
    {
        $this->transactionRepository = $transactionRepository;
        $this->payum = $payum;
        $this->memberRepository = $memberRepository;
    }

    public function setPublisher(Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    public function setConfirmations(array $confirmations): void
    {
        $this->confirmations = $confirmations;
    }

    public function setBlockchain(Blockchain $blockchain): void
    {
        $this->blockchain = $blockchain;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setBitcoinManager(BitcoinManager $bitcoinManager): void
    {
        $this->bitcoinManager = $bitcoinManager;
    }

    protected function getSenderAddresses(string $hash, string $address): array
    {
        try {
            $transaction = $this->blockchain->getExplorer()->getTransaction($hash);
            $inputs = $transaction->getInputs();

            return array_map(function (\PaymentBundle\Component\Blockchain\Model\BlockchainTransactionInput $input) {
                return $input->getPreviousOutAddress();
            }, $inputs);
        } catch(\Exception $ex) {
            return [];
        }
    }

    protected function getHttpRequest(): GetHttpRequest
    {
        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        return $httpRequest;
    }

    // zimi
    
    private function publishNoneUsingWampTransactionStatus(Transaction $transaction): void
    {
        try {            
            // $publishData = [
            //     'transaction_id' => $transaction->getId(),
            //     'member_id' => $transaction->getCustomer()->getId(),
            //     'confirmation_count' => $transaction->getBitcoinConfirmation(),
            //     'status' => $transaction->getBitcoinStatus(),
            // ]; 

            $data = ['confirm' => $transaction->getBitcoinConfirmation(),'status' => $transaction->getBitcoinStatus()];           
            
            // zimi-debug
            // pending_confirmation
            // confirmed
            // $data = ['confirm' => 0, 'status' => 'requested'];
            // $data = ['confirm' => 0, 'status' => 'pending'];            

            $topic = 'mwa.topic.deposit.bitcoin';                    
            $this->publisher->publish($topic, json_encode($data));

        } catch (Throwable $ex) {
            /* Do nothing must, even the publishing has an error it must still procceed as success */
        } catch (\Exception $e) {
            /* Do nothing must, even the publishing has an error it must still procceed as success */
        }
    }

    private function publishTransactionStatus(Transaction $transaction): void
    {
        try {            
            $publishData = [
                'transaction_id' => $transaction->getId(),
                'member_id' => $transaction->getCustomer()->getId(),
                'confirmation_count' => $transaction->getBitcoinConfirmation(),
                'status' => $transaction->getBitcoinStatus(),
            ];            

            $this->publisher->publishUsingWamp(self::PUBLISH_CHANNEL, $publishData);
            $this->publisher->publishUsingWamp(self::PUBLISH_CHANNEL . '.' . $transaction->getCustomer()->getWebsocketChannel(), $publishData);
            $this->publisher->publishUsingWamp(self::PUBLISH_CHANNEL . '.' . $transaction->getId(), $publishData);
        } catch (Throwable $ex) {
            /* Do nothing must, even the publishing has an error it must still procceed as success */
        } catch (\Exception $e) {
            /* Do nothing must, even the publishing has an error it must still procceed as success */
        }
    }

    private function validateRequest(GetHttpRequest $request): void
    {
        if (!array_key_exists('transaction_hash', $request->query)
            || !array_key_exists('value', $request->query)
            || !array_key_exists('address', $request->query)
            || !array_key_exists('confirmations', $request->query)
        ) {
            throw new RuntimeException('Incomplete request. "transaction_hash", "value", "address" and "confirmations" must be pass as query');
        }
    }

    private function save(Transaction $entity): void
    {
        $this->transactionRepository->getEntityManager()->persist($entity);
        $this->transactionRepository->getEntityManager()->flush($entity);
    }

    private function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'payment', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    private function logWithHttpRequest(string $level, string $message, GetHttpRequest $httpRequest, array $context = []): void
    {
        if (is_null($this->logger)) {
            return;
        }

        $this->logger->log($level, $message, array_merge($context, [
            'clientIp' => $httpRequest->clientIp,
            'content' => $httpRequest->content,
            'method' => $httpRequest->method,
            'query' => $httpRequest->query,
            'request' => $httpRequest->request,
            'uri' => $httpRequest->uri,
            'userAgent' => $httpRequest->userAgent,
        ]));
    }

    private function createOkResponse(): HttpResponse
    {
        return new HttpResponse(self::BLOCKCHAIN_OK_RESPONSE);
    }
}
