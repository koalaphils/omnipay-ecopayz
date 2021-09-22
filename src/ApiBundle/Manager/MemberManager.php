<?php

namespace ApiBundle\Manager;

use DateTime;
use AppBundle\Helper\ReferralToolGenerator;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\BannerImage;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\MemberBanner;
use DbBundle\Entity\MemberRequest;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Entity\Notification;
use DbBundle\Entity\Product;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\MemberBannerRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use MediaBundle\Manager\MediaManager;
use MemberBundle\Event\KycFileEvent;
use MemberBundle\Events;
use MemberRequestBundle\Manager\MemberRequestManager;
use Symfony\Component\HttpFoundation\Response;
use MemberBundle\Manager\MemberManager as MemberBundleManager;
use ApiBundle\Event\TransactionCreatedEvent;
use DbBundle\Entity\Transaction;
use ProductIntegrationBundle\ProductIntegrationFactory;
use ApiBundle\Service\JWTGeneratorService;
use Symfony\Component\HttpFoundation\Request;

class MemberManager extends AbstractManager
{
    public function getReferralLinkList(): array
    {
        $referralLinkOptions = $this->getMemberBannerRepository()->getMemberReferralLinkOptions($this->getUser()->getMemberId());

        $referralLinks = [];
        foreach ($referralLinkOptions as $referralLinkOption) {
            $referralLinks[] = $this->getReferralToolGenerator()
                ->generateReferralLink([
                    'type' => BannerImage::getTypeString($referralLinkOption['type']),
                    'language' => $referralLinkOption['language'],
                    'trackingCode' => $referralLinkOption['trackingCode'],
                ]);
        }

        return array_unique($referralLinks);
    }

    public function getCurrentPeriodReferralTurnoversAndCommissions(array $filters): array
    {
        return $this->getMemberBundleManager()->getCurrentPeriodReferralTurnoversAndCommissions(
            $this->getUser()->getCustomer(), new DateTime('now'), $filters
        );
    }

    public function getPreviousSuccessfulMemberRunningCommissions(int $periodCount): array
    {
        $memberRunningCommissions = array_column(
            $this->getMemberRunningCommissionRepository()
                ->getPreviousSuccessfulMemberRunningCommissions(
                    $this->getUser()->getMemberId(), 1, $periodCount
                ),
            null, 'commissionPeriodId'
        );

        $commissionPeriods = array_column(
            $this->getCommissionPeriodRepository()
            ->getSuccessfulPayoutOrComputationCommissionPeriods(1, $periodCount),
            null, 'commissionPeriodId'
        );

        array_walk($commissionPeriods, function(&$result, $key) use ($memberRunningCommissions) {
            $result = array_merge($result, [
                'runningCommission' => 0,
            ]);

            if (array_has($memberRunningCommissions, $key)) {
                $result = array_get($memberRunningCommissions, $key);
            }

            return $result;
        });

        return $commissionPeriods;
    }

    public function getLastSuccessfulMemberRunningCommission(): string
    {
        return $this
            ->getMemberRunningCommissionRepository()
            ->totalRunningCommissionOfMember(
                $this->getUser()->getMemberId()
            );
    }

    public function confirmReferrerTermsAndConditions(int $hasConfirm)
    {
        $member = $this->getUser()->getMember();

        try {
            if ($hasConfirm == true) {
                $member->confirmReferrerTermsAndConditions();
            } else {
                $member->unconfirmReferrerTermsAndConditions();
            }

            $this->save($member);
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), $e->getCode()];
        }

        return ['member' => $member, 'code' => Response::HTTP_OK];
    }

    public function getMemberFiles(): array
    {
        $member = $this->getUser()->getMember();

        return $this->getMemberBundleManager()->getMemberFiles($member, false);
    }

    public function uploadMemberFile(array $files, ?string $folder = null, ?string $filename = null): array
    {
        $member = $this->getUser()->getMember();
        $memberRequest = $this->getMemberRequestManager()->getMemberKYCPendingRequest($member->getMemberRequests());
        $subRequestCount = null;

        if (is_null($memberRequest)) {
            $memberRequest = new MemberRequest();
            $memberRequest->setMember($member);
            $memberRequest->setDate(new DateTime());
            $memberRequest->setType(MemberRequest::MEMBER_REQUEST_TYPE_KYC);
            $memberRequest->setNumber($this->getMemberRequestManager()->generateRequestNumber('kyc'));
            $memberRequest->setNotes();
        } else {
            $subRequestCount = count($memberRequest->getSubRequests());
        }

        $response = ['title'=> 'KYC', 'details' => [], 'otherDetails' => ['id' => $member->getId(), 'type' => 'docs']];
        $success = 0;
        foreach($files as $file){
            $result = $this->getMediaManager()->uploadFile($file, $folder, $filename);
            if(array_get($result, 'success', false)){
                $member->addFile([
                    'folder' => $result['folder'],
                    'file' => $result['filename'],
                    'title' => $result['filename'],
                    'description' => '',
                ]);

                $memberRequest->setRequests($subRequestCount ?? $success, 'remark', '');
                $memberRequest->setRequests($subRequestCount ?? $success, 'status', null);
                $memberRequest->setRequests($subRequestCount ?? $success, 'filename', $result['filename']);
                $memberRequest->setRequests($subRequestCount ?? $success, 'is_deleted', false);
                $memberRequest->setRequests($subRequestCount ?? $success, 'requested_at', new DateTime());

                if (!is_null($subRequestCount)) {
                    $subRequestCount++;
                }
                $success++;
            }
            $response['details'][] = $result;
        }

        $memberRequestKycRoute = '';
        if($success){
            $response['message'] = sprintf($this->getTranslator()->trans('KYC %d file(s) uploaded. (%s)'), $success, $member->getFullName());
            $this->save($member);
            $this->save($memberRequest);
            $memberRequestKycRoute = $this->getRouter()->generate('member_request.update_page', [
                    'type' => $memberRequest->getTypeText(),
                    'id' => $memberRequest->getId()]
            );
            $response['member_request_route'] = $memberRequestKycRoute;
            $response['id'] = $memberRequest->getId();
            $response['type'] = $memberRequest->getTypeText();
        }

        $notifMessage = sprintf('KYC %d file(s) uploaded. (%s)', $success, $member->getFullName());
        $response['notificationTitle'] = 'KYC Request';
        $response['notificationMessage'] = $notifMessage;
        $response['fromApi'] = true;
        $response['success'] = $success;
        $response['id'] = $memberRequest->getId();


        $notification = new Notification();
        $notification->setMessage($notifMessage);
        $notification->setUserId(1);
        $notification->setStyle('green');
        $notification->setCreatedAt(new \DateTime());
        $notification->type ='docs';

       if($success){
           $this->save($notification);
       }

        $event = new KycFileEvent($member, $result, $response);
        $this->get('event_dispatcher')->dispatch(Events::EVENT_MEMBER_KYC_FILE_UPLOADED, $event);

        return $response;
    }

    public function deleteMemberFile(string $filename, ?string $folder = null): array
    {
        try {
            $member = $this->getUser()->getMember();

            $documentToBeDeleted = $this->getMemberRequestManager()->getMemberKYCDocumentToBeDeleted($member->getMemberRequests(), $filename);
            if (!is_null($documentToBeDeleted)) {
                $memberRequest = $documentToBeDeleted['memberRequest'];
                $memberRequest->deleteDocumentRecord($documentToBeDeleted['index']);
                $this->save($memberRequest);
            }
            $result = ['title'=> 'KYC', 'message' => sprintf($this->getTranslator()->trans('KYC File %s deleted. (%s)'), $filename, $member->getFullName()), 'otherDetails' => ['id' => $member->getId(), 'type' => 'docs']];

            $folder = $this->getMediaManager()->getPath($folder);
            $file = $this->getMediaManager()->getFile($folder . $filename, true);
            $member->deleteFile($file['filename'], $file['folder']);

            $result['details'] = $this->getMediaManager()->deleteFile($folder . $filename);

            $notification = new Notification();
            $notification->setChannel(Notification::NOTIFICATION_CHANNEL_BACKOFFICE);
            $notification->setTitle('KYC');
            $notification->setDetail('files', [$file]);
            $notification->setMessage(sprintf('KYC File %s deleted. (%s)', $filename, $member->getFullName()));
            $notification->setMember($member);
            $notification->setType('docs');

            $response['notificationMessage'] = $notification->getMessage();
            $response['fromApi'] = true;
            $event = new KycFileEvent($member, $result);
            $this->get('event_dispatcher')->dispatch(Events::EVENT_MEMBER_KYC_FILE_DELETED, $event);

            $this->save($member);
            $this->save($notification);

        } catch (FileNotFoundException $fne) {
            return ['message' => $fne->getMessage(), 'code' => Response::HTTP_NOT_FOUND];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'code' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }

        return $result;
    }

    public function loginToPinnacle(Member $member): ?array
    {
        $member = $this->getRepository()->getById($member->getId());
        $pinnacleProduct = $this->getProductRepository()->getProductByCode('pinbet');
        $memberProduct = $this->getMemberProductRepository()->getPinnacleProduct($member);  
        $integration = $this->getProductIntegrationFactory()->getIntegration('pinbet');
        
        if ($memberProduct == null) {
            try {
                $response = $integration->create();

                $memberProduct = new MemberProduct();
                $memberProduct->setProduct($pinnacleProduct);
                $memberProduct->setUserName($response['userCode']);
                $memberProduct->setBalance('0.00');
                $memberProduct->setIsActive(true);
    
                $userCode = $response['userCode'];
                $loginId = $response['loginId'];
                $member->addProduct($memberProduct);

                $member->setPinLoginId($loginId);
                $member->setPinUserCode($userCode);      

                $this->save($member);
            } catch(\Exception $ex) {
                return null;
            } 
        }

        try {
            return $integration->auth($member->getPinUserCode(), ['locale' => $member->getLocale()]);
        } catch (\Exception $ex) {
            return null;
        }
    }

    public function loginToEvolution(Member $member, Request $request): ?array
    {
        $ip = $request->getClientIp();
        $sessionId = $request->get('sessionId');
        $member = $this->getRepository()->getById($member->getId());
        $evolutionProduct = $this->getProductRepository()->getProductByCode(Product::EVOLUTION_PRODUCT_CODE);
        $memberProduct = $this->getMemberProductRepository()->findOneByCustomerAndProductCode($member, Product::EVOLUTION_PRODUCT_CODE);  
        $integration = $this->getProductIntegrationFactory()->getIntegration(Product::EVOLUTION_PRODUCT_CODE);

        $memberLocale = $member->getLocale();
        $memberLocale = strtolower(str_replace('_', '-', $memberLocale));
        $locale = !empty($memberLocale) ? $memberLocale : 'en';

        if ($memberProduct == null) {
            try {
                $memberProduct = new MemberProduct();
                $memberProduct->setProduct($evolutionProduct);
                $memberProduct->setUsername('Evolution_' . uniqid());
                $memberProduct->setBalance('0.00');
                $memberProduct->setIsActive(true);

                $member->addProduct($memberProduct);
     
                $this->save($member);
            } catch(\Exception $ex) {
                throw $ex;
            } 
        }

        try {
            $jwt = $this->getJWTGeneratorService()->generate([]);
            return $integration->auth($jwt, [
                'id' => $memberProduct->getUsername(),
                'lastName' => $member->getLName() ? $member->getLname() : $member->getUsername(),
                'firstName' => $member->getFName() ? $member->getFName() : $member->getUsername(),
                'nickname' => str_replace("Evolution_","", $memberProduct->getUsername()),
                'country' => $member->getCountry() ? $member->getCountry()->getCode() : 'UK',
                'language' => $locale ?? 'en',
                'currency' => $member->getCurrency()->getCode(),
                'ip' => $ip,
                'sessionId' => $sessionId
            ]);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    protected function getJWTGeneratorService(): JWTGeneratorService
    {
        return $this->get('jwt_generator');
    }

    protected function getRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository(Member::class);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->getDoctrine()->getRepository(Product::class);
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getDoctrine()->getRepository(MemberProduct::class);
    }

    private function getMemberBannerRepository(): MemberBannerRepository
    {
        return $this->getDoctrine()->getRepository(MemberBanner::class);
    }

    private function getProductIntegrationFactory(): ProductIntegrationFactory
    {
        return $this->get(ProductIntegrationFactory::class);
    }

    private function getMemberRunningCommissionRepository(): MemberRunningCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberRunningCommission::class);
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }

    private function getReferralToolGenerator(): ReferralToolGenerator
    {
        return $this->get('app.referral_tool_generator');
    }

    private function getMediaManager(): MediaManager
    {
        return $this->get('media.manager');
    }

    private function getMemberBundleManager(): MemberBundleManager
    {
        return $this->get('member.manager');
    }

    private function getMemberRequestManager(): MemberRequestManager
    {
        return $this->get('member_request.manager');
    }
}