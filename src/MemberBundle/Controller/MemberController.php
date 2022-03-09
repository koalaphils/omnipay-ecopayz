<?php

namespace MemberBundle\Controller;

use DbBundle\Entity\Product;
use DbBundle\Repository\ProductRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DateTime;
use AppBundle\Controller\PageController;
use AppBundle\Manager\PageManager;
use AppBundle\Exceptions\HTTP\UnprocessableEntityException;
use AppBundle\Widget\Page\ListWidget;
use CustomerBundle\Manager\CustomerPaymentOptionManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\MarketingTool;
use DbBundle\Entity\RiskSetting;
use DbBundle\Repository\CustomerGroupRepository;
use DbBundle\Repository\CustomerRepository;
use DbBundle\Repository\MarketingToolRepository;
use DbBundle\Repository\RiskSettingRepository;
use MediaBundle\Manager\MediaManager;
use MediaBundle\Widget\Page\MediaLibraryWidget;
use MemberBundle\Manager\MemberManager;
use MemberBundle\Manager\MemberReferralNameManager;
use MemberBundle\Manager\MemberCommissionManager;
use MemberBundle\Manager\MemberRevenueShareManager;
use MemberBundle\Manager\MemberWebsiteManager;
use GuzzleHttp\Exception\GuzzleException;
use PromoBundle\Manager\PromoManager;

class MemberController extends PageController
{
    public function getMemberGroups(): array
    {
        return $this->getCustomerGroupRepository()->findAll();
    }

    public function processListResult(array $result, ListWidget $widget): array
    {
        $processedResult = $result;
        $countries = $this->get('country.manager')->getCountries();
        dump($processedResult);
        foreach ($processedResult['records'] as &$record) {
            $countryName = "Country Code Not Valid {$record['country']}";
            if ($record['country'] === null) {
                $countryName = 'Unknown';
            }

            if (isset($countries[$record['country']]['name'])) {
                $countryName = $countries[$record['country']]['name'];
            }

            $record['countryName'] = $countryName;

            $record = [
                'customer' => $record,
//                'referralCount' => $record['referralCount'],
                'routes' => [
                    'update' => $this->getRouter()->generate('member.update_page', ['id' => $record['id']]),
                ],
            ];
        }

        return $processedResult;
    }

	public function processPaymentOptionFilter($filters)
	{
		$paymentOptionsFilter = [];
		$paymentOptionSearch = "";

		//payment option filter
		if (isset($filters['filter']['paymentOption']) && $filters['filter']['paymentOption']) {
			$paymentOptionsFilter = $filters['filter']['paymentOption'];
		}

		//payment gateway search
		if (isset($filters['filter']['searchCategory']) && !empty($filters['filter']['searchCategory']) &&
			in_array('paymentGateway', $filters['filter']['searchCategory']) &&
			$filters['filter']['search']
		) {
			$paymentOptionSearch =  $filters['filter']['search'];
		}
		if ($paymentOptionSearch || $paymentOptionsFilter) {
			$filters['customer_payment_options'] = $this->get('app.service.customer_payment_option_service')->search($paymentOptionSearch, $paymentOptionsFilter);
		}

		return $filters;
	}

    public function processCommissionResult(array $result, ListWidget $widget): array
    {
        $member = $widget->getPageManager()->getData('customer');
        $memberCommissionManager = $this->getMemberCommissionManager();

        $result['records'] = array_map(function(&$record) use ($memberCommissionManager, $member) {
            $product = $this->getProductRepository()->findOneById($record['productId']);
            $record['commission'] = $memberCommissionManager->getCommissionSetting($member, $product);

            return $record;
        }, $result['records']);

        return $result;
    }

    public function processRevenueShareResult(array $result, ListWidget $widget): array
    {
        $member = $widget->getPageManager()->getData('customer');
        $memberRevenueShareManager = $this->getMemberRevenueShareManager();
        $result['records'] = array_map(function(&$record) use ($memberRevenueShareManager, $member) {
            $product = $this->getProductRepository()->findOneById($record['productId']);
            $record['revenueShare'] = $memberRevenueShareManager->getRevenueShareSetting($member, $product);

            return $record;
        }, $result['records']);

        return $result;
    }

    public function updateSaveRevenueShare(PageManager $pageManager, array $data): JsonResponse
    {
        $member = $pageManager->getData('customer');
        if ($member instanceof Customer){
            $status = $data['allow_revenue_share'];
            $member->setRevenueShare(json_decode($status, true));
            $this->getCustomerRepository()->save($member);
        }

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    public function updateRemoveRevenueShareSettings(PageManager $pageManager, array $data): JsonResponse
    {
        $member = $pageManager->getData('customer');
        $memberRevenueShareManager = $this->getMemberRevenueShareManager();

        $revenueShareSettings = $memberRevenueShareManager->getRevenueShareSettingByMember($member);
        $defaultSettings[] = array('max' => '0', 'min' => '0', 'percentage' => '0');

        foreach ($revenueShareSettings as $revenueShareSetting) {
            $request = \MemberBundle\Request\UpdateRevenueShareRequest::fromEntity($member);
            $handler = $this->getUpdateRevenueShareRequestHandler();
            $request->setRevenueShareSettings($defaultSettings);
            $request->setProductId($revenueShareSetting->getProduct()->getIdentifier());
            $request->setResourceId($revenueShareSetting->getResourceId());
            $handler->handle($request);
        }

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    public function updateDocumentsOnGetList(PageManager $pageManager, MediaLibraryWidget $widget, array $data): array
    {
        $member = $pageManager->getData('customer');

        return $this->getMemberManager()->getMemberFiles($member);
    }

    public function updateDocumentsOnDeleteFile(PageManager $pageManager, MediaLibraryWidget $widget, array $data): array
    {
        $member  = $pageManager->getData('customer');
        $member->deleteFile($data['filename']);
        $this->getCustomerRepository()->save($member);

        return $widget->deleteFile($data['filename']);
    }

    public function updateDocumentsOnUploadFile(PageManager $pageManager, MediaLibraryWidget $widget, array $data): array
    {
        $member  = $pageManager->getData('customer');
        $uploadedFile = $widget->uploadFile();

        if ($uploadedFile['success']) {
            $member->addFile([
                'folder' => $uploadedFile['folder'],
                'file' => $uploadedFile['filename'],
                'title' => $uploadedFile['filename'],
                'description' => '',
            ]);
            $this->getCustomerRepository()->save($member);

            return $this->getMediaManager()->getFile($this->getMediaManager()->getPath($uploadedFile['folder']) . $uploadedFile['filename'], true);
        } else { 
            return $uploadedFile;
        }
    }

    public function updateOnVerifyCustomer(PageManager $pageManager, array $data): array
    {
        $member = $pageManager->getData('customer');
        $this->getMemberManager()->verifyMember($member);

        return ['success' => true];
    }

    public function updateOnUnverifyCustomer(PageManager $pageManager, array $data): array
    {
        $member = $pageManager->getData('customer');
        $this->getMemberManager()->unverifyMember($member);

        return ['success' => true];
    }

    public function updateOnLinkMember(PageManager $pageManager, array $data): JsonResponse
    {
        try {
            $member = $pageManager->getData('customer');
            $response = $this->getMemberManager()->linkMember($member);
        } catch (UnprocessableEntityException $ex) {
            $message = '';
            if ($ex->member_user_id) $message = $ex->member_user_id[0];
            if ($ex->referral_code) $message = $ex->referral_code[0];
            
            return $this->json([
                'message' => $message
            ], $ex->getCode());
        }

        return $this->json([]);
    }

    public function updateOnUnlinkMember(PageManager $pageManager, array $data): JsonResponse
    {
        $referral = $pageManager->getData('customer');
        $customer = $this->getCustomerRepository()->find($data['id']);
        if ($customer->hasReferral() && $referral->getId() === $customer->getAffiliate()->getId()) {
            $customer->unlinkReferral();
            $this->getEntityManager()->persist($customer);
            $this->getEntityManager()->flush($customer);

            $notifications = [
                [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans(
                        'notification.unlinkCustomer.success.title',
                        [],
                        'MemberBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notification.unlinkCustomer.success.message',
                        ['%name%' => $customer->getFName().' '.$customer->getLName()],
                        'MemberBundle'
                    ),
                ],
            ];
            $code = Response::HTTP_OK;
        } else {
            $notifications = [
                [
                    'type' => 'error',
                    'title' => $this->getTranslator()->trans(
                        'notification.unlinkCustomer.error.title',
                        [],
                        'MemberBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notification.unlinkCustomer.error.message',
                        ['%name%' => $customer->getFName().' '.$customer->getLName()],
                        'MemberBundle'
                    ),
                ],
            ];
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        return new JsonResponse(['__notifications' => $notifications], $code);
    }

    public function updateOnSuspendReferralName(PageManager $pageManager, array $data): array
    {
        $memberWebsiteId = $data['memberReferralNameId'];
        $this->getMemberReferralNameManager()->suspendReferralName($memberWebsiteId);

        return ['success' => true];
    }

    public function updateOnActivateReferralName(PageManager $pageManager, array $data): JsonResponse
    {
        $member = $pageManager->getData('customer');
        if (!$this->getMemberReferralNameManager()->canAddMoreReferralName($member->getId())) {
            return JsonResponse::create(['success' => false, 'error' => 'Max active referral name exceeded'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $memberWebsiteId = $data['memberReferralNameId'];
        $this->getMemberReferralNameManager()->activateReferralName($memberWebsiteId);

        return JsonResponse::create(['success' => true]);
    }

    public function onFindMembers(PageManager $pageManager, array $data): array
    {
        $affiliates = $this->get('app.service.affiliate_service')
            ->getAffiliates($data);

        $records = array_map(function ($affiliate) {
            return [
                'id' => $affiliate['user_id'],
                'text' => $affiliate['name'],
            ];
        }, $affiliates['data']);

        return [
            'items' => $records,
        ];
    }

    public function onFindAvailableReferrals(PageManager $pageManager, array $data): array
    {
        $filters = ['withReferralCount' => false];
        if (array_has($data, 'search')) {
            $filters['search'] = $data['search'];
        }

        if (!empty($memberId = $pageManager->getCurrentRequest()->get('_route_params')['id'])) {
            $filters['excludeMemberId'] = $memberId;
        }

        $records = $this->getCustomerRepository()->getAvailableReferrals($filters, $data['length'], $data['start']);

        $records = array_map(function ($record) {
            return [
                'id' => $record['id'],
                'text' => $record['fullName'] . ' (' . $record['user']['username'] . ')',
                'refferer' => $record['affiliate']['id'],
            ];
        }, $records);

        return [
            'items' => $records,
            'total' => $this->getCustomerRepository()->getAllPotentialReferralsOfMember($filters['excludeMemberId']),
        ];
    }

    public function updateOnSuspendWebsite(PageManager $pageManager, array $data): array
    {
        $memberWebsiteId = $data['memberWebsiteId'];
        $this->getMemberWebsiteManager()->suspendWebsite($memberWebsiteId);

        return ['success' => true];
    }

    public function updateOnActivateWebsite(PageManager $pageManager, array $data): JsonResponse
    {
        $member = $pageManager->getData('customer');
        if (!$this->getMemberWebsiteManager()->canAddMoreWebsiteForMember($member->getId())) {
            return JsonResponse::create(['success' => false, 'error' => 'Max active website exceeded'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $memberWebsiteId = $data['memberWebsiteId'];
        $this->getMemberWebsiteManager()->activateWebsite($memberWebsiteId);

        return JsonResponse::create(['success' => true]);
    }

    public function downloadBannerAction(Request $request, int $id)
    {
        $memberBanner = $this->getMemberBannerRepository()->find($id);
        $memberBanner->setReferralLink(
            $this->getReferralToolGenerator()->generateReferralLink(
                $memberBanner->getReferralLinkOptions()
            )
        );
        $memberBanner->setTrackingHtmlCode(
            $this->getReferralToolGenerator()->generateTrackingHtmlCode(
                $memberBanner->getTrackingHtmlOptions()
            )
        );

        return new StreamedResponse(function () use ($memberBanner) {
            echo $memberBanner->getTrackingHtmlCode();
        }, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="' . $memberBanner->getMemberReferralName()->getName() . '.html"',
        ]);
    }

    public function updateRunningCommissionListOnGetList(
        PageManager $pageManager,
        \AppBundle\Widget\Page\ListWidget $listWidget,
        array $data
    ): array {
        $originalLimit = $data['limit'] ?? 20;
        $data['limit'] = $originalLimit + 1;
        $data['page'] = $data['page'] ?? 1;
        $data['offset'] = $data['offset'] ?? ($data['page'] - 1) * $originalLimit;

        return $listWidget->onGetList($data);
    }

    public function getAffiliateLinkPreviousVersionsAction(Request $request)
    {
        $response = $this->getMarketingToolRepository()->getAffiliateLinkPreviousUpdatesByMember($request->request->get('memberId'));

        return new JsonResponse($response);
    }

    public function getClientLoginHistoryAction(Request $request): JsonResponse
    {
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $results = $this->getMemberManager()->getMemberLoginHistory($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function getTurnoverCommissionListAction(Request $request, int $id, string $orderBy)
    {
        $data = $request->get('data', []);
        $filters = $data['filters'];

        $customer = $this->getCustomerRepository()->find($id);
        if ($customer->getIsAffiliate()) {
            $filters['revenueShare'] = $customer->isRevenueShareEnabled();
            $filters['sort'] = array_get($data, 'sort', 'asc');
            $filters['limit'] = array_get($data, 'limit', 10);
            $filters['page'] = (int) array_get($data, 'page', 1);
            $filters['offset'] = ($filters['page'] - 1) * $filters['limit'];
            $filters['orderBy'] = $orderBy;
            $filters['precision'] = $request->get('precision');
    
            if((array_get($filters, 'dwlDateFrom') == "Invalid date") || (array_get($filters, 'dwlDateTo') == "Invalid date")){
                $filters['dwlDateFrom'] = "";
                $filters['dwlDateTo'] = "";
            }
    
            if (array_get($filters, 'dwlDateFrom')) {
                array_set($filters, 'dwlDateFrom', date('Y-m-d', strtotime($filters['dwlDateFrom'])));
            }
    
            if (array_get($filters, 'dwlDateTo')) {
                array_set($filters, 'dwlDateTo', date('Y-m-d', strtotime($filters['dwlDateTo'])));
            }
    
            $currentPeriodReferralTurnoversAndCommissions = $this->getMemberManager()
                ->getCurrentPeriodReferralTurnoversAndCommissions(
                    $customer, new DateTime('now'), $filters
                );
    
            return $this->jsonResponse($currentPeriodReferralTurnoversAndCommissions, Response::HTTP_OK);
        }
        return $this->jsonResponse([], Response::HTTP_OK);
    }

    public function downloadTurnoverCommissionReportAction(Request $request, int $id, string $orderBy): StreamedResponse
    {
        $filters = $request->get('filters', []);

        $response = new StreamedResponse(function () use ($id, $filters) {
            $this->getMemberManager()->generateTurnoverCommissionReport($id, $filters);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="TurnoverCommissionListBy' . ucfirst($filters['orderBy']) . '_' . date('Ymd') . '.csv"');

        return $response;
    }

    public function checkMemberPaymentOptionIfBitcoinAction(Request $request, int $id): Response
    {
        $isPaymentOptionBitcoin = $this->getMemberManager()->isPaymentOptionIdBitcoin($id);

        return $this->response($request, ['isPaymentOptionBitcoin' => $isPaymentOptionBitcoin], ['groups' => ['Default']]);
    }

    public function getReferrerDetailsAction(Request $request)
    {
        $referralCode = $request->query->get('referral_code');
        $userId = $request->query->get('user_id');
        $affiliate = [];

        if ($referralCode) {
            $affiliate = $this->get('app.service.affiliate_service')
                ->getAffiliateByReferralCode($referralCode);
        } else if ($userId) {
            $affiliate = $this->get('app.service.affiliate_service')
                ->getAffiliate($userId);
        }

        $customer = $this->getCustomerRepository()->getByUserId($affiliate['user_id']);

        return new JsonResponse(['name' => $customer->getFName(), 'email' => $customer->getUser()->getEmail()], JsonResponse::HTTP_OK);
    }   

    public function updateGeneratePersonalLink(PageManager $pageManager, array $data): JsonResponse
    {
        $member = $pageManager->getData('customer');
        $link = '';

        if ($member->getCountry()) {
            if (!$member->getPersonalLink()) {
                $this->getPromoManager()->createPersonalLinkId($member);
            }

            $link = $this->getPromoManager()->getPersonalLink($member);
        }

        return new JsonResponse(['link' => $link], Response::HTTP_OK);
    }

    private function getCustomerRepository(): CustomerRepository
    {
        return $this->getRepository(Customer::class);
    }

    private function getCustomerGroupRepository(): CustomerGroupRepository
    {
        return $this->getRepository(CustomerGroup::class);
    }

    private function getMarketingToolRepository(): MarketingToolRepository
    {
        return $this->getRepository(MarketingTool::class);
    }

    private function getRiskSettingRepository(): RiskSettingRepository
    {
        return $this->getRepository(RiskSetting::class);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->getRepository(Product::class);
    }

    private function getMemberManager(): MemberManager
    {
        return $this->get('member.manager');
    }

    private function getBrokerageManager(): BrokerageManager
    {
        return $this->container->get('brokerage.brokerage_manager');
    }

    private function getMemberReferralNameManager(): MemberReferralNameManager
    {
        return $this->get('member.referral_name_manager');
    }

    private function getMemberCommissionManager(): MemberCommissionManager
    {
        return $this->get('member.commission_manager');
    }

    private function getMemberRevenueShareManager(): MemberRevenueShareManager
    {
        return $this->get('member.revenue_share_manager');
    }

    private function getMediaManager(): MediaManager
    {
        return $this->get('media.manager');
    }

    private function getPromoManager(): PromoManager
    {
        return $this->get('promo.manager');
    }

    private function getMemberWebsiteManager(): MemberWebsiteManager
    {
        return $this->get('member.website_manager');
    }

    private function getMemberBannerRepository(): \DbBundle\Repository\MemberBannerRepository
    {
        return $this->getRepository(\DbBundle\Entity\MemberBanner::class);
    }

    private function getReferralToolGenerator(): \AppBundle\Helper\ReferralToolGenerator
    {
        return $this->get('app.referral_tool_generator');
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }

    private function getUpdateRevenueShareRequestHandler(): \MemberBundle\RequestHandler\UpdateRevenueShareRequestHandler
    {
        return $this->container->get('member.handler.update_revenue_share');
    }

    private function getCustomerPaymentOptionManager(): CustomerPaymentOptionManager
    {
	    return $this->get('customer.payment_option_manager');
    }
}
