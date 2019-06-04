<?php

namespace ApiBundle\Controller;

use ApiBundle\Form\Member\RegisterType;
use ApiBundle\Request\RegisterRequest;
use ApiBundle\RequestHandler\MemberHandler;
use ApiBundle\RequestHandler\RegisterHandler;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\View\View;
use ApiBundle\Manager\MemberManager;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /**
     * @ApiDoc(
     *     section="Member",
     *     description="Register Member",
     *     views={"default","piwi"},
     *     requirements={
     *         {
     *             "name"="verification_code",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="email",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="phone_number",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="country_phone_code",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="currency",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="password",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="repeat_password",
     *             "dataType"="string"
     *         }
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function registerAction(Request $request, RegisterHandler $registerHandler, ValidatorInterface $validator): View
    {
        $registerRequest = RegisterRequest::createFromRequest($request);
        $violations = $validator->validate($registerRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }
        try {
            $member = $registerHandler->handle($registerRequest);
        } catch (PinnacleException $ex) {
            return $this->view([
                'success' => false,
                'error' => 'Something went wrong, contact support',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        return $this->view($member);
    }

    /**
     * @ApiDoc(
     *     section="Current Login Member",
     *     description="Get Pinnacle Balance",
     *     views={"default", "piwi"},
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getPinnacleBalanceAction(MemberHandler $memberHandler): View
    {
        $user = $this->getUser();

        return $this->view($memberHandler->handleGetBalance($user->getCustomer()));
    }

    /**
     * @ApiDoc(
     *     section="Current Login Member",
     *     description="Get active payment options",
     *     views={"piwi"},
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getActivePaymentOptionAction(MemberHandler $memberHandler): View
    {
        $user = $this->getUser();

        return $this->view($memberHandler->handleGetActivePaymentOptionGroupByType($user->getCustomer()));
    }

    /**
     * @ApiDoc(
     *     section="Current Login Member",
     *     description="Change member locale",
     *     views={"piwi"},
     *     requirements={
     *          {
     *             "name"="locale",
     *             "dataType"="string"
     *         }
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function changeLocaleAction(Request $request, MemberHandler $memberHandler): View
    {
        $member = $this->getUser()->getCustomer();

        return $this->view($memberHandler->changeMemberLocale($member, $request->get('locale')));
    }

    private function getMemberManager(): MemberManager
    {
        return $this->container->get('api.member.manager');
    }
}