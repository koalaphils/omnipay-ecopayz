<?php

namespace ApiBundle\ProductIntegration;

use AppBundle\ValueObject\Number;
use PinnacleBundle\Service\PinnacleService;
use PinnacleBundle\Component\Exceptions\PinnacleError;
use PinnacleBundle\Component\Exceptions\PinnacleException;

class PinnacleAdapterIntegration extends AbstractIntegration
{
    private $pinnacleService;

    public function __construct(PinnacleService $pinnacleService)
    {
        parent::__construct('');

        $this->pinnacleService = $pinnacleService;
    }

    public function auth(string $token, $body = []): array
    {
        $authComponent = $this->pinnacleService->getAuthComponent();

        // Body supposedly contains jsonBody for form requests.
        // But this is an adapter class so we need to adapt.
        return $authComponent->login($token, $body['locale'])->toArray();
    }

    public function getBalance(string $token, string $id): string
    {
        try {
            $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->getPlayer($id);
       
            return $pinnaclePlayer->availableBalance();            
        } catch (PinnacleException $exception) {
            throw new IntegrationException($exception->getMessage(), 422);
        } catch (PinnacleError $exception) {
            throw new IntegrationNotAvailableException($exception->getMessage(), 422);
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
        }
    }
}
