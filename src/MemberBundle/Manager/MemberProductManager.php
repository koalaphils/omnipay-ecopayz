<?php

declare(strict_types = 1);

namespace MemberBundle\Manager;

use ApiBundle\Service\JWTGeneratorService;
use ApiBundle\ProductIntegration\ProductIntegrationFactory;
use ApiBundle\ProductIntegration\IntegrationException;
use ApiBundle\ProductIntegration\IntegrationNotAvailableException;
use ApiBundle\ProductIntegration\NoSuchIntegrationException;
use AppBundle\ValueObject\Number;
use AppBundle\Widget\Page\ListWidget;
use PinnacleBundle\Component\Exceptions\PinnacleError;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use PinnacleBundle\Service\PinnacleService;
use DbBundle\Repository\CustomerProductRepository;

class MemberProductManager
{
    private $pinnacleService;
    private $factory;
    private $jwtGeneratorService;
    private $customerRepository;

    public function __construct(PinnacleService $pinnacleService, 
        ProductIntegrationFactory $factory, 
        JWTGeneratorService $jwtGeneratorService,
        CustomerProductRepository $customerProductRepository)
    {
        $this->pinnacleService = $pinnacleService;
        $this->jwtGeneratorService = $jwtGeneratorService;
        $this->factory = $factory;
        $this->customerProductRepository = $customerProductRepository;
    }

    public function processMemberProductListWidget(array $result, ListWidget $listWidget): array
    {
        $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
        $result['records'] = array_map(function(&$record) use ($pinnacleProduct) {
            $record['product'] = $this->getProductDetails($record);
            $record['customer'] = [ 'id' => $record['customer_id']];
            $record['balance'] = $this->getProductBalance($record);

            return $record;
        }, $result['records']);


        return $result;
    }

    private function getProductDetails($record): array
    {
        return [
            'id' => $record['product_id'],
            'details' => $record['product_details'],
            'name' => $record['product_name'],
        ];
    }

    private function getProductBalance($record)
    {
        $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
        $balance = 'Unable to fetch balance';

        dump($record);

        try {
            $integration = $this->factory->getIntegration(strtolower($record['product_code']));
            $jwt = $this->jwtGeneratorService->generate([]);
            $balance = $integration->getBalance($jwt, $record['userName']);
        } catch(NoSuchIntegrationException $ex) {
            $balance = $record['balance'];
        } 
        catch (IntegrationException $ex) {
            $balance = "Unable to fetch balance";
        } catch (IntegrationNotAvailableException $ex) {
            $balance = $ex->getMessage();
        }

        if (Number::isNumber($balance)) {
            $balance = Number::formatToMinimumDecimal($balance, 2);
        }

        return $balance;
    }
}