<?php

declare(strict_types = 1);

namespace MemberBundle\Manager;

use AppBundle\ValueObject\Number;
use AppBundle\Widget\Page\ListWidget;
use PinnacleBundle\Component\Exceptions\PinnacleError;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use PinnacleBundle\Service\PinnacleService;

class MemberProductManager
{
    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    public function __construct(PinnacleService $pinnacleService)
    {
        $this->pinnacleService = $pinnacleService;
    }

    public function processMemberProductListWidget(array $result, ListWidget $listWidget): array
    {
        $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
        $result['records'] = array_map(function(&$record) use ($pinnacleProduct) {
            $record['product'] = [
                'id' => $record['product_id'],
                'details' => $record['product_details'],
                'name' => $record['product_name'],
            ];
            $record['customer'] = [
                'id' => $record['customer_id'],
            ];
            if ($pinnacleProduct->getId() == $record['product_id']) {
                try {
                    $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->getPlayer($record['userName']);
                    $record['balance'] = $pinnaclePlayer->availableBalance();
                } catch (PinnacleException $exception) {
                    $record['balance'] = "Unable to fetch balance";
                } catch (PinnacleError $exception) {
                    $record['balance'] = "Unable to fetch balance";
                }
            }

            if (Number::isNumber($record['balance'])) {
                $record['balance'] = Number::formatToMinimumDecimal($record['balance'], 2);
            }

            return $record;
        }, $result['records']);

        return $result;
    }
}