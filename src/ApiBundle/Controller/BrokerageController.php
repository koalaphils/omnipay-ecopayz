<?php

namespace ApiBundle\Controller;

use BrokerageBundle\Service\BrokerageSyncService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BrokerageController extends AbstractController
{
    /**
     *
     * @ApiDoc(
     *  description="Sync Win Loss Transactions",
     *  parameters={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "required"=true,
     *          "description"="date of daily winloss"
     *      },
     *      {
     *          "name"="members[0][sync_id]",
     *          "dataType"="string",
     *          "required"=true,
     *          "description"="brokerage sync id of member product"
     *      },
     *      {
     *          "name"="members[0][stake]",
     *          "dataType"="string",
     *          "required"=true,
     *          "description"="cummulative stake per day"
     *      },
     *      {
     *          "name"="members[0][turnover]",
     *          "dataType"="string",
     *          "required"=true,
     *          "description"="cummulative turnover per day"
     *      },
     *      {
     *          "name"="members[0][win_loss]",
     *          "dataType"="string",
     *          "required"=true,
     *          "description"="cummulative winLoss per day"
     *      }
     *  }
     * )
     */
    public function syncWinLossAction(Request $request): Response
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date'));
        $synIdsAffected = $this->getBrokerageSyncService()->syncWinLoss($date, $request->get('members', []));

        return $this->view(['sync_ids_affected' => $synIdsAffected]);
    }

    private function getBrokerageSyncService(): BrokerageSyncService
    {
        return $this->get('brokerage.sync_service');
    }
}
