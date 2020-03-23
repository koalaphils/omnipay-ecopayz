<?php

namespace ApiBundle\Controller;

use ApiBundle\Form\Member\RegisterType;
use ApiBundle\Model\File;
use ApiBundle\Request\RegisterRequest;
use ApiBundle\RequestHandler\MemberHandler;
use ApiBundle\RequestHandler\RegisterHandler;
use MediaBundle\Manager\MediaManager;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\View\View;
use ApiBundle\Manager\MemberManager;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $filters['sort'] = $request->get('sort', 'asc');

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

        if ($request->query->has('dwlDateFrom')) {
            $currentPeriodReferralTurnoversAndCommissions['period']['dwlDateFrom'] = convert_to_timezone($request->query->get('dwlDateFrom'))->format('c');
        }

        if ($request->query->has('dwlDateTo')) {
            $currentPeriodReferralTurnoversAndCommissions['period']['dwlDateTo'] = convert_to_timezone($request->query->get('dwlDateTo'))->format('c');
        }

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
     *          {
     *              "name"="verification_code",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="email",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="phone_number",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="country_phone_code",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="currency",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="password",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="repeat_password",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="referrer_site",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="referrer_origin_site",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="registration_site",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="registration_locale",
     *              "dataType"="string"
     *          }
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
     *     description="Get Balance",
     *     views={"default", "piwi"},
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getBalanceAction(MemberHandler $memberHandler): View
    {
        $user = $this->getUser();

        return $this->view($memberHandler->handleGetBalance($user->getCustomer()));
    }

    /**
     * @ApiDoc(
     *     section="Current Login Member",
     *     description="Get active payment options",
     *     views={"piwi"},
     *     filters={
     *          {"name"="type", "dataType"="string"}
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getActivePaymentOptionAction(Request $request, MemberHandler $memberHandler): View
    {
        $user = $this->getUser();

        return $this->view($memberHandler->handleGetActivePaymentOptionGroupByType($user->getCustomer(), $request->get('type', null)));
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

        return $this->view($memberHandler->changeMemberLocale($request, $member, $request->get('locale')));
    }

    /**
     * @ApiDoc(
     *     section="Current Login Member",
     *     description="Change member country",
     *     views={"piwi"},
     *     requirements={
     *          {
     *             "name"="country",
     *             "dataType"="string"
     *         }
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function changeCountryAction(Request $request, MemberHandler $memberHandler): View
    {
        $member = $this->getUser()->getCustomer();

        return $this->view($memberHandler->changeMemberCountry($member, $request->get('country')));
    }

    /**
     * @ApiDoc(
     *     section="Member",
     *     description="Gets the documents of the user",
     *     views={"piwi"},
     * )
     */
    public function getMemberFilesAction(): array
    {
        return $this->getMemberManager()->getMemberFiles();
    }

    /**
     * @ApiDoc(
     *    section="Member",
     *    description="Uploads KYC file of the user",
     *    views={"piwi"},
     *    input={
     *        "class"="ApiBundle\Form\Member\FileType",
     *    }
     * )
     */
    public function uploadMemberFileAction(Request $request): array
    {
        $files = new File();
        $form = $this->createForm( \ApiBundle\Form\Member\FileType::class, $files);
        $form->handleRequest($request);
        $response = [];
        if($form->isValid() && $form->isSubmitted()){
            $files = $form->getData();
            $response = $this->getMemberManager()->uploadMemberFile($files->getFiles(), $this->container->getParameter('customer_folder') ?? 'customerDocuments');
        }

        return $response;
    }

    /**
     * @ApiDoc(
     *     section="Member",
     *     description="deletes customer file",
     *     views={"piwi"},
     *     requirements={
     *      {"name"="filename", "dataType"="string"}
     *     }
     * )
     */
    public function deleteMemberFileAction(string $filename){
        $response = $this->getMemberManager()->deleteMemberFile($filename, $this->container->getParameter('customer_folder') ?? 'customerDocuments');

        return $response;
    }

    /**
     * @ApiDoc(
     *     section="Member",
     *     description="renders customer file",
     *     views={"piwi"},
     *     requirements={
     *      {"name"="filename", "dataType"="string"}
     *     }
     * )
     */
    public function renderMemberFileAction(string $filename){
        return $this->getMediaManager()->renderFile($filename, $this->container->getParameter('customer_folder') ?? 'customerDocuments');
    }

    /**
     * @ApiDoc(
     *     section="Member",
     *     description="retrieves the URI of the file",
     *     views={"piwi"},
     *     requirements={
     *      {"name"="filename", "dataType"="string"}
     *     }
     * )
     */
    public function getMemberFileUriAction(string $filename){
        return $this->getMediaManager()->getFileUri($filename, $this->container->getParameter('customer_folder') ?? 'customerDocuments');
    }

    /**
     * @ApiDoc(
     *     section="Member",
     *     description="Check Pinnacle Product if existing, otherwise create the Product",
     *     views={"piwi"}
     * )
     */
    public function loginToPinnacleAction()
    {
        $manager = $this->getMemberManager();
        $response = $manager->loginToPinnacle($this->getUser()->getMember());
        
        return new JsonResponse($response ?? []);
    }

    private function getMemberManager(): MemberManager
    {
        return $this->container->get('api.member.manager');
    }

    private function getMediaManager(): MediaManager
    {
        return $this->container->get('media.manager');
    }
}