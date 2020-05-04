<?php

namespace MemberRequestBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\MemberRequest;
use DbBundle\Repository\MemberRequestRepository;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use \Exception;
use AppBundle\Manager\SettingManager;
use MemberRequestBundle\Form\MemberRequestType;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\FormValidationException;
use MemberRequestBundle\Event\RequestProcessEvent;
use MemberRequestBundle\Model\MemberRequest\Kyc as KycModel;
use MemberRequestBundle\Model\MemberRequest\ProductPassword as ProductPasswordModel;
use \DateTime;
use MediaBundle\Manager\MediaManager;
use UserBundle\Manager\UserManager;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use MemberBundle\Manager\MemberManager;
use Doctrine\ORM\PersistentCollection;
use AppBundle\Helper\Publisher;
use DbBundle\Repository\UserRepository;
use DbBundle\Entity\User;
use MemberRequestBundle\WebsocketTopics;

class MemberRequestManager extends AbstractManager
{
    public function getRequestsList(array $filters = []): array
    {
        $results = [];
        try {
            if (array_get($filters, 'datatable', 0)) {
                if (false !== array_get($filters, 'search.value', false)) {
                    $filters['search'] = $filters['search']['value'];
                }
                $orders = (!array_has($filters, 'order')) ? [['column' => 'mrs.createdAt', 'dir' => 'desc']] : $filters['order'];
                $results['data'] = array_map(function ($memberRequest) {
                    return [
                        'memberRequest' => $memberRequest,
                        'routes' => [
                           'view' => $this->getRouter()->generate('member_request.update_page', [
                               'type' => $memberRequest['type'],
                               'id' => $memberRequest['id'],
                            ])
                       ],
                    ];
                }, $this->getRepository()->getRequestList($filters, $orders));
                
                $results['draw'] = $filters['draw'];
                $results['recordsFiltered'] = $this->getRepository()->getRequestListFilterCount($filters);
                $results['recordsTotal'] = $this->getRepository()->getRequestListAllCount();
            } else {
                $results = $this->getRepository()->getRequestList($filters);
            }
        } catch (Exception $e) {
            $errorMessage = 'Line error: ' . $e->getCode() . ' Message: ' . $e->getMessage();
            $results = ['error' => $errorMessage];
        }

        return $results;
    }
    
    public function getNonPendingRequestStatus(array $statuses): array
    {
        $returnStatus = [];
        $nonPendingStatus = MemberRequest::getNonPendingStatus();
        foreach ($nonPendingStatus as $index => $key) {
            if (array_key_exists($key, $statuses)) {
                $returnStatus[$key] = $statuses[$key];
            }
        }

        return $returnStatus;
    }

    public function getPendingRequestStatus(array $statuses): array
    {
        $returnStatus = [];
        $nonPendingStatus = MemberRequest::getPendingStatus();
        foreach ($nonPendingStatus as $index => $key) {
            if (array_key_exists($key, $statuses)) {
                $returnStatus[$key] = $statuses[$key];
            }
        }

        return $returnStatus;
    }

    public function createUpdateForm(MemberRequest $memberRequest, $forSave = true, $requestToDecline = false): Form
    {
        $actions = array_map(function ($action) {
            $action['class'] = $action['class'] . ' btn-action';

            return $action;
        }, array_get($this->getSettingStatus($memberRequest), 'actions', []));

        $formElementsToUnmap = $forSave ? $this->getFormElementsToUnmap($memberRequest) : [];
        $formElementsViewOnly = $forSave ? [] : $this->getFormElementsViewOnly($memberRequest);
        
        $form = $this->getFormFactory()->create(MemberRequestType::class, $memberRequest, [
            'action' => $this->getRouter()->generate('member_request.save', [
                'type' => $this->translateRequestType($memberRequest->getType(), true),
                'id' => $memberRequest->getId()]),
            'actions' => $actions,
            'formElementsToUnmap' => $formElementsToUnmap,
            'formElementsViewOnly' => $formElementsViewOnly,
            'validation_groups' => function () use ($memberRequest, $requestToDecline, $forSave) {
                $groups = ['default'];
                if ($requestToDecline && $memberRequest->isKyc()) {
                    $groups[] = 'withKycToDecline';
                }

                if ($forSave && !$requestToDecline && $memberRequest->isProductPasswordHadBeenAcknowledged()) {
                    $groups[] = 'withProductPassword';
                }

                return $groups;
            },
        ]);

        return $form;
    }

    public function handleFormMemberRequest(Form $form, $memberRequest, Request $request)
    {
        $requestNumber  = $memberRequest->getNumber();
        $memberRequests = $request->request->get('MemberRequest');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $memberRequestForm = $form->getData();
            $memberRequestForm->setNumber($requestNumber);
            $memberRequestForm->setKycSubRequests($memberRequests['subRequests']);

            $btn = $form->getClickedButton();
            $action = array_get($btn->getConfig()->getOption('attr', []), 'value', 'process');
            $this->processRequest($memberRequestForm, $action);
 
            return $memberRequestForm;
        }

        throw new FormValidationException($form);
    }

    public function handleResetGoogleAuthentication(User $user): void
    {
        $member = $user->getMember();
        $channel = $member->getWebsocketDetails()['channel_id'];
        $message = 'Google Authenticator has been reset';
        $this->getUserRepository()->resetGoogleAuthentication($user);
        $this->createMemberNotification($member, $message);
        $this->getPublisher()->publish(WebsocketTopics::TOPIC_MEMBER_REQUEST_GAUTH_PROCESSED . '.' . $channel, json_encode(['status' => true, 'message' => $message]));
    }

    public function createMemberNotification(Member $member, string $message): void
    {
        
        $notification = new \stdClass();
        $notification->read = false;
        $notification->message = $message;

        $dateTime = new \DateTime('now');
        $notification->dateTime = $dateTime->format('M j, Y g:i a');

        $member->addNotification($notification);
        $this->save($member);
    }

    public function processRequest(MemberRequest &$memberRequest, $action): void
    {
        if ($memberRequest->getId()) {
            $action = $this->getSettingAction($memberRequest->getStatus(), $memberRequest->getTypeText(), $action);
        } else {
            $action = ['label' => 'Save', 'status' => MemberRequest::MEMBER_REQUEST_STATUS_START];
        }

        $event = new RequestProcessEvent($memberRequest, $action);
        try {
            $this->getRepository()->beginTransaction();
            $this->dispatchEvent('request.saving', $event);
            $this->getRepository()->save($event->getRequest());
            $this->getRepository()->commit();
            $this->dispatchEvent('request.saved', $event);
        } catch (\Exception $e) {
            $this->getRepository()->rollback();

            throw $e;
        }
    }

    public function makeDocumentRequestValidated(int $memberRequestId, int $key, string $remark, int $status): MemberRequest
    {
        $memberRequest = $this->getRepository()->find($memberRequestId);
        $memberRequest->setDocumentValidated($key, $remark, $status);
        $this->save($memberRequest);
        
        return $memberRequest;
    }

    public function endRequest(&$memberRequest): void
    {
        $subRequests = $memberRequest->getKycSubRequests();
        $i = 0;
        foreach ($subRequests as $request) {
            if ($request instanceof KycModel) {
                $memberRequest->setDetail('sub_requests.' . $i . '.remark', $request->getRemark());
                if (!$request->wasStatusValidated()) {
                    $memberRequest->setDocumentAsValid($i);
                }
            }

            $i++;
        }
    }

    public function declineRequest(&$memberRequest): void
    {
        $subRequests = $memberRequest->getKycSubRequests();
        $i = 0;
        foreach ($subRequests as $request) {
            if ($request instanceof KycModel) {
                $memberRequest->setDocumentValidated($i, '', 0);
            }

            $i++;
        }
    }

    public function deleteRequestDocument(int $memberRequestId, int $requestId, string $remark = ''): MemberRequest
    {        
        $memberRequest = $this->getRepository()->find($memberRequestId);
        
        $requestDocument = $memberRequest->getRecordByIndex($requestId);
        $folder = $this->getMediaManager()->getPath($this->getMediaDocumentRoot());
        $file = $this->getMediaManager()->getFile($folder . $requestDocument['filename'], true);
        $this->getMediaManager()->deleteFile($folder . $file['filename']);

        $memberRequest->getMember()->deleteFile($file['filename'], $file['folder']);
        $memberRequest->deleteDocumentRecord($requestId);
        $memberRequest->setDetail('sub_requests.'. $requestId .'.remark', trim($remark));
        $this->save($memberRequest);

        return $memberRequest;
    }

    public function getMediaDocumentRoot(): string
    {
        return $this->getMediaManager()->getCustomerDocumentRoot();
    }

    public function getSettingStatus(MemberRequest $memberRequest): array
    {
        return $this->getSettingManager()->getSetting('transaction.status.' . $memberRequest->getStatus());
    }

    public function getRequestByIdAndType(int $memberRequestId, string $type): MemberRequest
    {
        return $this->getRepository()->findByIdAndType($memberRequestId, $this->translateRequestType($type));
    }

    public function getMemberKYCPendingRequest(PersistentCollection $memberRequests): ?MemberRequest
    {
        foreach ($memberRequests as $key => $memberRequest) {
            if ($memberRequest->hasPendingRequest()) {
                return $memberRequest;
            }
        }

        return null;
    }

    public function getMemberKYCDocumentToBeDeleted(PersistentCollection $memberRequests, string $filename): ?array
    {
        foreach ($memberRequests as $key => $memberRequest) {
            if ($index = $memberRequest->hasDocumentFilename($memberRequest, $filename)) {

                 return [
                     'memberRequest' => $memberRequest,
                     'index' => $index,
                 ];
            }   
        }

        return null;
    }

    public function generateRequestNumber(string $type, string $suffix = ''): string
    {
        return date('Ymd-His-') . generate_code(6, false, 'd') . '-' . $this->translateRequestType($type) . $suffix;
    }

    private function getAction($status, $action, $type = null): array
    {
        if ($type !== null) {
            $path = 'transaction.type.workflow.' . $type . '.' . $status . '.actions.' . $action;
            $typeAction = $this->getSettingManager()->getSetting($path, null);
            if ($typeAction !== null) {
                return $typeAction;
            }
        }
        $action = $this->getSettingManager()->getSetting("transaction.status.$status.actions.$action");

        return $action;
    }

    public function handleMailSendingProductPassword(Member $member, array $emailDetails): void
    {
        try {
            $this->getUserManager()->sendProductPasswordEmail([
                'fullName' => $member->getFullName(),
                'email' => $member->getUser()->getEmail(),
                'originFrom' => $this->getParameter('asianconnect_url'),
                'memberProducts' => $emailDetails['memberProducts'],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getFormElementsToUnmap(MemberRequest $memberRequest): array
    {
        $subRequests = [];
        $isMemberProductMapped = true;

        $subRequests = [
            'filename' => false,
            'is_deleted' => false,
            'remark' => $this->isRemarkFieldEditable($memberRequest),
        ];

        return [
            'number' => false,
            'date' => false,
            'subRequests' => $subRequests,
        ];
    }

    private function getFormElementsViewOnly(MemberRequest $memberRequest): array
    {
        $isRemarkReadonly = !$this->isRemarkFieldEditable($memberRequest);
        $isProductPasswordEditable = !$this->isPasswordFieldEditable($memberRequest);
        $readOnly = true;
        $subRequests = [
            'remark' => $isRemarkReadonly,
        ];

        return [
            'number' => $readOnly,
            'date' => $readOnly,
            'subRequests' => $subRequests,
            'notes' => $memberRequest->isEnd() || $memberRequest->isDeclined(),
        ];
    }

    private function isRemarkFieldEditable(MemberRequest $memberRequest) : bool
    {
        return array_get($this->getSettingStatus($memberRequest), 'editRemark', false);
    }

    private function isPasswordFieldEditable(MemberRequest $memberRequest) : bool
    {
        return array_get($this->getSettingStatus($memberRequest), 'editPassword', false);
    }

    private function getSettingAction($status, $type, $action): array
    {
        if ($type !== null) {
            $path = 'transaction.type.workflow.' . $type . '.' . $status . '.actions.' . $action;
            $typeAction = $this->getSettingManager()->getSetting($path, null);
            if ($typeAction !== null) {
                return $typeAction;
            }
        }
        $action = $this->getSettingManager()->getSetting("transaction.status.$status.actions.$action");

        return $action;
    }

    private function translateRequestType($type, $inverse = false)
    {
        if (!$inverse) {
            $types = [
                'product_password' => MemberRequest::MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD,
                'kyc' => MemberRequest::MEMBER_REQUEST_TYPE_KYC,
                'google_auth' => MemberRequest::MEMBER_REQUEST_TYPE_GAUTH,
            ];
        } else {
            $types = [
                MemberRequest::MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD => 'product_password',
                MemberRequest::MEMBER_REQUEST_TYPE_KYC => 'kyc',
                MemberRequest::MEMBER_REQUEST_TYPE_GAUTH => 'google_auth',
            ];
        }

        return array_get($types, $type);
    }

    private function getSettingManager(): SettingManager
    {
         return $this->get('app.setting_manager');
    }

    private function getPublisher(): Publisher
    {
        return $this->getContainer()->get('app.publisher');
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getDoctrine()->getRepository(User::class);
    }

    protected function getFormFactory(): FormFactory
    {
        return $this->getContainer()->get('form.factory');
    }

    protected function getRepository(): MemberRequestRepository
    {
        return $this->getDoctrine()->getRepository(MemberRequest::class);
    }

    protected function getMediaManager(): MediaManager
    {
        return $this->getContainer()->get('media.manager');
    }

    protected function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getDoctrine()->getRepository(MemberProduct::class);
    }

    protected function getUserManager(): UserManager
    {
        return $this->getContainer()->get('user.manager');
    }

    protected function getMemberManager(): MemberManager
    {
        return $this->get('member.manager');
    }
}
