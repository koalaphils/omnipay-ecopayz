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
    public function recomputeAndPayoutAction(Request $request, int $commissionPeriodId)
    {
        $commissionManager = $this->get('commission.manager');
        $loggedInUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $result  = $commissionManager->recomputeAndPayoutCommissionForPeriod($commissionPeriodId, $loggedInUser->getUsername());
        $responseData = ['success' => false];
        if ($result === true) {
            $responseData = ['success' => true];
        }

        return new JsonResponse($responseData);
    }
}
