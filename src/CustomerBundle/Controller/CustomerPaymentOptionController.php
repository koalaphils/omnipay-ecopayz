<?php

namespace CustomerBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use CustomerBundle\Form\PaymentType;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\CustomerPaymentOption;

class CustomerPaymentOptionController extends AbstractController
{
    public function listByCustomerAction(Request $request, $id)
    {
        $customer = $this->getRepository('DbBundle:Customer')->find($id);

        if ($customer === null) {
            throw $this->createNotFoundException();
        }

        // $paymentOptions = $this->getRepository('DbBundle:CustomerPaymentOption')->findBy(['customer' => $id]);
        /* @var $paymentOptions \Doctrine\ORM\PersistentCollection */
        $paymentOptions = $customer->getPaymentOptions();
        $paymentOptions->initialize();

        return $this->response($request, $paymentOptions, ['groups' => ['Default', 'payment_option.email']]);
    }

    public function saveAction(Request $request, $id, $paymentOptionId = 'new')
    {
        if ($paymentOptionId === 'new') {
            return $this->createAction($request, $id);
        }

        return $this->updateAction($request, $id, $paymentOptionId);
    }

    public function createAction(Request $request, $id)
    {
        $customer = $this->getRepository('DbBundle:Customer')->find($id);

        $customerPaymentOption = new CustomerPaymentOption();
        $form = $this->createForm(PaymentType::class, $customerPaymentOption, []);
        $response = ['success' => false];
        try {
            $customerPaymentOption = $this->getManager()->handleCreateForm($form, $request, $customer);
            $response['success'] = true;
            $response['data'] = $customerPaymentOption;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, []);
    }

    public function updateAction(Request $request, $id, $paymentOptionId)
    {
        $customerPaymentOption = $this->getRepository('DbBundle:CustomerPaymentOption')->find($paymentOptionId);
        $form = $this->createForm(PaymentType::class, $customerPaymentOption, []);
        $response = ['success' => false];
        try {
            $customerPaymentOption = $this->getManager()->handleUpdateForm($form, $request);
            $response['success'] = true;
            $response['data'] = $customerPaymentOption;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, []);
    }

    /**
     * Get Payment Option Manager
     *
     * @return \CustomerBundle\Manager\CustomerPaymentOptionManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('customer.payment_option_manager');
    }
}
