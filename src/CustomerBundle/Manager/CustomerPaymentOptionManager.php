<?php

namespace CustomerBundle\Manager;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Manager\AbstractManager;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Customer;
use Doctrine\ORM\EntityManager;
use DbBundle\Entity\PaymentOption;

/**
 * Customer Payment Option
 */
class CustomerPaymentOptionManager extends AbstractManager
{
    public function handleCreateForm(Form $form, Request $request, Customer $customer)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $customerPaymentOption = $form->getData();
            $customerPaymentOption->setCustomer($customer);
            $paymentOption = $this->getEntityManager()->getReference(
                PaymentOption::class,
                $customerPaymentOption->getType()
            );
            $customerPaymentOption->setPaymentOption($paymentOption);

            $this->save($customerPaymentOption);

            return $customerPaymentOption;
        }

        throw new FormValidationException($form);
    }

    public function handleUpdateForm(Form $form, Request $request)
    {
        $customerBank = $request->get('CustomerBank');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $customerPaymentOption = $form->getData();
            $paymentOption = $this->getEntityManager()->getReference(
                PaymentOption::class,
                $customerPaymentOption->getType()
            );
            if (isset($customerBank['isActive'])) {
                $customerPaymentOption->enable();
            } else {
                $customerPaymentOption->suspend();
            }
            $customerPaymentOption->setPaymentOption($paymentOption);
            $this->save($customerPaymentOption);

            return $customerPaymentOption;
        }

        throw new FormValidationException($form);
    }

	public function searchCustomer($paymentOptionSearch, $paymentOptionsFilter)
	{
		return $this->getRepository()->searchCustomer($paymentOptionSearch, $paymentOptionsFilter);
	}

    /**
     * Get repository
     *
     * @return \DbBundle\Repository\CustomerPaymentOption
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerPaymentOption');
    }
}
