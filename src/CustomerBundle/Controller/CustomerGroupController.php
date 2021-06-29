<?php

namespace CustomerBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\AbstractController;
use CustomerBundle\Form\CustomerGroupType;
use DbBundle\DataTransfer\CustomerGroupDataTransfer;
use DbBundle\DataTransfer\CustomerGroupListDataTransfer;
use DbBundle\Entity\CustomerGroup;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CustomerGroupController
 */
class CustomerGroupController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_GROUP_VIEW']);

        return $this->render('CustomerBundle:Group:index.html.twig');
    }

    public function listAction(Request $request)
    {
        $this->getSession()->save();

        //$this->denyAccessUnlessGranted(['ROLE_DWL_VIEW']);
        $options = $request->request->all();
        $options = array_merge($options, $request->query->all());
        $results = $this->getManager()->getList($options);

        $results['data'] = $this->transform(CustomerGroupListDataTransfer::class, $results['data']);
        if ($request->get('datatable')) {
            $results['draw'] = $request->get('draw');
        }

        return new JsonResponse($results);

        //return new JsonResponse($this->transform(CustomerGroupListDataTransfer::class, $records));
    }

    public function createPageAction(Request $request)
    {
        $customerGroup = new CustomerGroup('', [], false);
        $form = $this->createForm(CustomerGroupType::class, $customerGroup, [
            'action' => $this->getRouter()->generate('customer.group_create'),
        ]);

        return $this->render('CustomerBundle:Group:create.html.twig', ['form' => $form->createView()]);
    }

    public function createAction(Request $request)
    {
        $customerGroup = new CustomerGroup('', new ArrayCollection(), false);
        $form = $this->createForm(CustomerGroupType::class, $customerGroup, []);
        $result = $this->getManager()->handleCreateGroupFromForm($form, $request);

        if ($result['success'] === true) {
            $result['data'] = $this->transform(CustomerGroupDataTransfer::class, $result['data'], [
                '_format' => $this->getResponseTypeFromRequest($request),
            ]);
        } else {
            $result['errors'] = $this->getManager()->getErrorMessagesWithFormId($form, true);
        }

        return new JsonResponse($result);
    }

    public function updatePageAction($id)
    {
        $customerGroup = $this->getRepository('DbBundle:CustomerGroup')->find($id);
        if ($customerGroup === null) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(CustomerGroupType::class, $customerGroup, [
            'action' => $this->getRouter()->generate('customer.group_update', ['id' => $id]),
        ]);

        return $this->render('CustomerBundle:Group:update.html.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, $id)
    {
        $result = [];
        $groupData = $request->get('CustomerGroup');
        $customerGroup = $this->getRepository('DbBundle:CustomerGroup')->find($id);
        if ($customerGroup === null) {
            throw $this->createNotFoundException();
        }
        if ($customerGroup->getIsDefault() && !isset($groupData['isDefault'])) {
            $notification = [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notifications.submit.error.title', [], 'CustomerGroupBundle'),
                'message' => $this->getTranslator()->trans('notifications.submit.error.message', [], 'CustomerGroupBundle'),
                'location' => $this->getRouter()->generate('customer.group_update', ['id' => $id]),
            ];

            return new JsonResponse(['_notifications' => $notification]);
        }
        
        $form = $this->createForm(CustomerGroupType::class, $customerGroup, []);
        $result = $this->getManager()->handleUpdateGroupFromForm($form, $request, $id);
        if ($result['success'] === true) {
            $result['data'] = $this->transform(CustomerGroupDataTransfer::class, $result['data'], [
                '_format' => $this->getResponseTypeFromRequest($request),
            ]);
        } else {
            $result['errors'] = $this->getManager()->getErrorMessagesWithFormId($form, true);
        }

        return new JsonResponse($result);
    }


    public function getCustomerGroupGatewayAction($id)
    {
        $customerGroup = $this->getRepository('DbBundle:CustomerGroup')->find($id);
    }

    /**
     * Get customer group manager
     *
     * @return \CustomerBundle\Manager\CustomerGroupManager
     */
    protected function getManager()
    {
        return $this->get('customer.group_manager');
    }
}
