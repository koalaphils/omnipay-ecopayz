<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\View\View;
use ApiBundle\Manager\MemberManager;
use Symfony\Component\HttpFoundation\Request;

class MemberController extends AbstractController
{
    /**
     * @ApiDoc(
     *     description="Gets list of member referral links"
     * )
     */
    public function listReferralLinksAction(): View
    {
        $referralLinks = $this->getMemberManager()->getReferralLinkList();

        return $this->view($referralLinks);
    }

    /**
     * @ApiDoc(
     *     description="Get current period turnovers and commissions of member referrals",
     *     filters={
     *      {"name"="dwlDateFrom", "dataType"="date"},
     *      {"name"="dwlDateTo", "dataType"="date"},
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="page", "dataType"="integer"},
     *      {"name"="search", "dataType"="string"},
     *      {"name"="orderBy", "dataType"="string"},
     *      {"name"="precision", "dataType"="integer"},
     *      {"name"="hideZeroTurnover", "dataType"="integer"}
     *  }
     * )
     */
    public function getCurrentPeriodReferralTurnoversAndCommissionsAction(Request $request): View
    {
        $filters = [];

        $filters['limit'] = $request->get('limit', 10);
        $filters['page'] = (int) $request->get('page', 1);
        $filters['offset'] = ($filters['page'] - 1) * $filters['limit'];
        $filters['orderBy'] = $request->get('orderBy');
        $filters['precision'] = $request->get('precision');

        if ($request->query->has('dwlDateFrom')) {
            $filters['dwlDateFrom'] = $request->query->get('dwlDateFrom');
        }

        if ($request->query->has('dwlDateTo')) {
            $filters['dwlDateTo'] = $request->query->get('dwlDateTo');
        }

        if ($request->query->has('search')) {
            $filters['search'] = $request->query->get('search');
        }

        if ($request->query->has('hideZeroTurnover')) {
            $filters['hideZeroTurnover'] = $request->query->get('hideZeroTurnover');
        }

        $currentPeriodReferralTurnoversAndCommissions = $this->getMemberManager()
            ->getCurrentPeriodReferralTurnoversAndCommissions($filters);

        return $this->view($currentPeriodReferralTurnoversAndCommissions);
    }

    /**
     * @ApiDoc(
     *     description="Gets previous successful running commissions and payout of referrer"
     * )
     */
    public function getPreviousSuccessfulMemberRunningCommissionsAction(int $periodCount): View
    {
        $previousSuccessfulMemberRunningCommissions = $this->getMemberManager()
            ->getPreviousSuccessfulMemberRunningCommissions($periodCount);

        return $this->view($previousSuccessfulMemberRunningCommissions);
    }

    /**
     * @ApiDoc(
     *     description="Gets last successful running commission and payout of referrer"
     * )
     */
    public function getLastSuccessfulMemberRunningCommissionAction(): View
    {
        $lastSuccessfulMemberRunningCommission = $this->getMemberManager()
            ->getLastSuccessfulMemberRunningCommission();

        return $this->view($lastSuccessfulMemberRunningCommission);
    }

    /**
     * @ApiDoc(
     *  description="Confirms the terms and condition of referrer",
     *  requirements={
     *      {
     *          "name"="hasConfirm",
     *          "dataType"="int",
     *          "description"="1 or 0 if the referrer confirmed the terms and conditions"
     *      }
     *  }
     * )
     */
    public function confirmReferrerTermsAndConditionsAction(int $hasConfirm): View
    {
        $result = $this->getMemberManager()->confirmReferrerTermsAndConditions($hasConfirm);
        $view = $this->view($result, $result['code']);
        $view->getContext()->setGroups(['termsAndConditions']);

        return $view;
    }

    private function getMemberManager(): MemberManager
    {
        return $this->container->get('api.member.manager');
    }
}