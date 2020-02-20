<?php

namespace MemberRequestBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use MemberRequestBundle\Manager\MemberRequestManager;
use TransactionBundle\Manager\TransactionManager;
use AppBundle\Exceptions\FormValidationException;
use \DbBundle\Entity\MemberRequest ;

class MemberRequestController extends AbstractController
{
    public function historyAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_VIEW']);
        $this->getSession()->save();
        $statuses = $this->getTransactionManager()->getTransactionStatus();
        $nonPendingStatus = $this->getManager()->getNonPendingRequestStatus($statuses);

        return $this->render('MemberRequestBundle:MemberRequest:list.html.twig', [
            'listLabel' => 'History',
            'statuses' => $nonPendingStatus,
        ]);
    }

    public function pendingAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_VIEW']);
        $this->getSession()->save();
        $statuses = $this->getTransactionManager()->getTransactionStatus();
        $pendingStatus = $this->getManager()->getPendingRequestStatus($statuses);

        return $this->render('MemberRequestBundle:MemberRequest:list.html.twig', [
            'listLabel' => 'Pending',
            'statuses' => $pendingStatus,
        ]);
    }

    public function searchAction(Request $request): JsonResponse
    {
        $this->getSession()->save();
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_VIEW']);
        $filters = $request->request->all();

        $results = $this->getManager()->getRequestsList($filters);
        $context = $this->createSerializationContext(['Default']);
        
        return $this->jsonResponse($results, Response::HTTP_OK, [], $context);
    }

    public function updatePageAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_UPDATE']);
        $this->getSession()->save();
        $memberRequest = $this->getManager()->getRequestByIdAndType($id, $type);
        $form = $this->getManager()->createUpdateForm($memberRequest, false);
        $attributes = [];
        if ($memberRequest->isKyc()) {
            $attributes['documentRoot'] = $this->getManager()->getMediaDocumentRoot();
            $attributes['locale'] = $request->getLocale();
        }

        return $this->render("MemberRequestBundle:MemberRequest/Type:$type.html.twig", [
            'form' => $form->createView(),
            'type' => $type,
            'memberRequest' => $memberRequest,
            'attributes' => $attributes,
        ]);
    }

    public function deleteDocumentAction(Request $request, int $memberRequestId, int $requestId): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_UPDATE']);
        $response = ['success' => true];
        try {
            $allRequest = $request->request->all();
            $memberRequest = $this->getManager()->deleteRequestDocument($memberRequestId, $requestId, $allRequest['remark']);
            $response['data'] = $memberRequest;
        } catch (FormValidationException $e) {
            $response['success'] = false;
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, []);
    }

    public function saveAction(Request $request, string $type, int $id): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_UPDATE']);
        $response = ['success' => true];
        try {
            $memberRequest = $this->getManager()->getRequestByIdAndType($id, $type);
            $requestToDecline = $this->isRequestToDecline($memberRequest, $request);
            $memberRequests = $request->request->get('MemberRequest');
            
            if (array_get($memberRequests, 'subRequests', false)) {   
                $memberRequest->setSubRequests($memberRequests['subRequests']);
            }

            $form = $this->getManager()->createUpdateForm($memberRequest, true, $requestToDecline);
            $handledMemberRequest = $this->getManager()->handleFormMemberRequest($form, $memberRequest, $request);
            $response['data'] = $handledMemberRequest;
        } catch (FormValidationException $e) {
            $response['success'] = false;
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, []);
    }

    public function validAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_UPDATE']);
        $requestId = $request->query->get('requestId');
        $requestKey = $request->query->get('requestKey');
        $allRequest = $request->request->all();

        $memberRequest = $this->getManager()->makeDocumentRequestValidated($requestId, $requestKey, $allRequest['remark'], 1);
        
        return new JsonResponse(['status' => true, JsonResponse::HTTP_OK]);
    }

    public function invalidAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_MEMBER_REQUEST_UPDATE']);
        $requestId = $request->query->get('requestId');
        $requestKey = $request->query->get('requestKey');
        $allRequest = $request->request->all();

        $memberRequest = $this->getManager()->makeDocumentRequestValidated($requestId, $requestKey, $allRequest['remark'], 0);
        
        return new JsonResponse(['status' => true, JsonResponse::HTTP_OK]);
    }

    private function isRequestToDecline(MemberRequest $memberRequest, Request $request) : bool
    {
        $memberRequest = $request->request->all();
        $buttonName = key($memberRequest['MemberRequest']['actions']);
        $buttonName = str_replace('btn_',''  , $buttonName);
        if ($buttonName == 'decline') {
            return true;
        }

        return false;
    }

    private function getTransactionManager() : TransactionManager
    {
        return $this->get('transaction.manager');
    }

    protected function getManager(): MemberRequestManager
    {
        return $this->get('member_request.manager');
    }
}
