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
use AppBundle\Widget\Page\ListWidget;
use BrokerageBundle\Manager\BrokerageManager;
use MemberBundle\Manager\MemberReferralNameManager;
use MemberBundle\Manager\MemberCommissionManager;
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
use MemberBundle\Event\ReferralEvent;
use MemberBundle\Events;
use MemberBundle\Manager\MemberManager;
use MemberBundle\Manager\MemberWebsiteManager;

class MemberController extends PageController
{
    public function getMemberGroups(): array
    {
        return $this->getCustomerGroupRepository()->findAll();
    }

    public function processListResult(array $result, ListWidget $widget): array
    {
        $processedResult = $result;
        foreach ($processedResult['records'] as &$record) {
            $record = [
                'customer' => $record[0],
                'referralCount' => $record['referralCount'],
                'routes' => [
                    'update' => $this->getRouter()->generate('member.update_page', ['id' => $record[0]['id']]),
                ],
            ];
        }

        return $processedResult;
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

    public function processCustomerProductListResult(array $result, ListWidget $widget)
    {
        $brokerageManager = $this->getBrokerageManager();

        $result['records'] = array_map(function(&$record) use ($brokerageManager) {
            $record['balanceWithBA'] = $record['balance'];
            $record['product'] = [
                'id' => $record['product_id'],
                'details' => $record['product_details'],
                'name' => $record['product_name'],
            ];
            $record['customer'] = [
                'id' => $record['customer_id'],
            ];
            
            $memberProductDetails = !is_null($record['details']) ? json_decode($record['details']) : null;
            if (isset($memberProductDetails->brokerage->sync_id)) {
                $syncId = $memberProductDetails->brokerage->sync_id;
                $balance = $brokerageManager->getCustomerBalance($syncId);
                $record['baBalance'] = $balance;
                $record['balanceWithBA'] .= ' (BA: ' . $balance . ')';
            }

            return $record;
        }, $result['records']);

        return $result;
    }

    public function updateDocumentsOnGetList(PageManager $pageManager, MediaLibraryWidget $widget, array $data): array
    {
        $member = $pageManager->getData('customer');
        $files = $member->getFiles();

        foreach ($files as &$file) {
            $path = array_get($file, 'folder', '') . '/' . $file['file'];
            $file = array_merge($file, $this->getMediaManager()->getFile($path, true));
        }
        unset($customer);

        return $files;
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
        $result = $widget->uploadFile();
        if ($result['success']) {
            $member->addFile([
                'folder' => $result['folder'],
                'file' => $result['filename'],
                'title' => $result['filename'],
                'description' => '',
            ]);
            $this->getCustomerRepository()->save($member);

            return $this->getMediaManager()->getFile($result['folder'] . '/' . $result['filename'], true);
        } else {
            return $result;
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

    public function updateOnUnlinkMember(PageManager $pageManager, array $data): JsonResponse
    {
        $referral = $pageManager->getData('customer');
        $customer = $this->getCustomerRepository()->find($data['id']);
        if ($customer->hasReferral() && $referral->getId() === $customer->getAffiliate()->getId()) {
            $referral = $customer->getReferral();
            $this->getEventDispatcher()->dispatch(Events::EVENT_REFERRAL_UNLINKED, new ReferralEvent($referral, $customer));

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
        $memberId = !empty($pageManager->getCurrentRequest()->get('_route_params')['id']) ? $pageManager->getCurrentRequest()->get('_route_params')['id'] : null;

        $filters = [];
        if (array_has($data, 'search')) {
            $filters['search'] = $data['search'];
        }

        $records = $this->getCustomerRepository()->getPossibleReferrers($memberId, $filters, $data['length'], $data['start']);

        $records = array_map(function ($record) {
            return [
                'id' => $record['id'],
                'text' => $record['fullName'] . ' (' . $record['user']['username'] . ')',
                'refferer' => $record['affiliate']['id'],
            ];
        }, $records);

        return [
            'items' => $records,
            'total' => $this->getCustomerRepository()->getCustomerListAllCount(),
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

    public function onFindSkypeBettors(PageManager $pageManager, array $data): array
    {
        $manager = $this->getBrokerageManager();
        $search = $data['search'] ?? '';

        $results = $manager->searchName($search);

        return [
            'items' => $results['items'],
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

        $filters['limit'] = array_get($data, 'limit', 10);
        $filters['page'] = (int) array_get($data, 'page', 1);
        $filters['offset'] = ($filters['page'] - 1) * $filters['limit'];
        $filters['orderBy'] = $orderBy;

        if (array_get($filters, 'dwlDateFrom')) {
            array_set($filters, 'dwlDateFrom', date('Y-m-d', strtotime($filters['dwlDateFrom'])));
        }

        if (array_get($filters, 'dwlDateTo')) {
            array_set($filters, 'dwlDateTo', date('Y-m-d', strtotime($filters['dwlDateTo'])));
        }

        $currentPeriodReferralTurnoversAndCommissions = $this->getMemberManager()
            ->getCurrentPeriodReferralTurnoversAndCommissions(
                $id, new DateTime('now'), $filters
            );

        return $this->jsonResponse($currentPeriodReferralTurnoversAndCommissions, Response::HTTP_OK);
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

    private function getMediaManager(): MediaManager
    {
        return $this->get('media.manager');
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
}