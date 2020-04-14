<?php

namespace CommissionBundle\Controller;

use AppBundle\Controller\PageController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of CommissionSettingController
 *
 * @author cydrick
 */
class CommissionSettingController extends PageController
{
    public function recomputeAndPayoutRevenueShareAction(Request $request, int $commissionPeriodId): JsonResponse
    {
        $commissionManager = $this->get('commission.manager');
        $loggedInUser = $this->getContainer()->getParameter('cron_user');
        $action = $request->get('action');
        $result  = $commissionManager->recomputeAndPayoutRevenueShareForPeriod($commissionPeriodId, $loggedInUser, $action);
        return new JsonResponse(['success' => $result]);
    }
}