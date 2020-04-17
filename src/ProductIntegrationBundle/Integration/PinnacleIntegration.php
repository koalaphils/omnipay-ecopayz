<?php

namespace ProductIntegrationBundle\Integration;

use AppBundle\ValueObject\Number;
use PinnacleBundle\Service\PinnacleService;
use PinnacleBundle\Component\Exceptions\PinnacleError;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use Http\Client\Exception\NetworkException;
use ProductIntegrationBundle\Exception\NoPinnacleProductException;

class PinnacleIntegration implements ProductIntegrationInterface, PinnaclePlayerInterface
{
    private $pinnacleService;
    private $http;

    public function __construct(PinnacleService $pinnacleService, HttpPersistence $http)
    {
        $this->pinnacleService = $pinnacleService;
        $this->http = $http;
    }

    public function auth(string $token, $body = []): array
    {
        try {
            $authComponent = $this->pinnacleService->getAuthComponent();

            // Body supposedly contains jsonBody for form requests.
            // But this is an adapter class so we need to adapt.
            return $authComponent->login($token, $body['locale'])->toArray();         
        } catch (PinnacleException $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        } catch (PinnacleError $exception) {
            throw new IntegrationException($exception->getMessage(), 422);
        }
    }

    public function getBalance(string $token, string $id): string
    {
        try {
            $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->getPlayer($id);
       
            return $pinnaclePlayer->availableBalance();            
        } catch (PinnacleException $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        } catch (PinnacleError $exception) {
            throw new IntegrationException($exception->getMessage(), 422);
        }
    }

    public function credit(string $token, array $params): string
    {
        try {
            $transactionComponent = $this->pinnacleService->getTransactionComponent();
            $response = $transactionComponent->deposit($params['id'], Number::format($params['amount'], ['precision' => 2]));
    
            return $response->availableBalance();          
        } catch (PinnacleException $exception) {
            throw new IntegrationException($exception->getMessage(), 422);
        } catch (PinnacleError $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        } catch (NetworkException $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        }
    }

    public function debit(string $token, array $params): string
    {
        try {
            $transactionComponent = $this->pinnacleService->getTransactionComponent();
            $response = $transactionComponent->withdraw($params['id'], Number::format($params['amount'], ['precision' => 2]));
    
            return $response->availableBalance();          
        } catch (PinnacleException $exception) {
            throw new IntegrationException($exception->getMessage(), 422);
        } catch (PinnacleError $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        } catch (NetworkException $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        }
    }

    public function create(): array
    {
        try {
            return $this->pinnacleService->getPlayerComponent()->createPlayer()->toArray();     
        } catch (PinnacleException $exception) {
            throw new IntegrationException($exception->getMessage(), 422);
        } catch (PinnacleError $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        } catch (NetworkException $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
        }
    }

    public function configure(): array
    {
       dump('WAHAHA');
    }
}
