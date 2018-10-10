<?php

namespace CustomerBundle\Manager;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\WrongEntityException;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\Customer;

/**
 * Description of CustomerGroupManager
 */
class CustomerGroupManager extends AbstractManager
{
    public function handleCreateGroupFromForm(Form $form, Request $request)
    {
        $submitedData = $request->get('CustomerGroup');
        $form->submit($submitedData);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerGroup = $this->createGroupFromForm($form);
            $this->save($customerGroup);
            if (isset($submitedData['isDefault'])) {
                $this->getRepository()->setDefaultById($customerGroup->getId());
            }

            return ['success' => true, 'data' => $customerGroup];
        }

        return ['success' => false, 'errors' => $this->getErrorMessages($form)];
    }

    public function handleUpdateGroupFromForm(Form $form, Request $request, $groupId)
    {
        $oldGateways = $form->getData()->getGateways();
        $submitedData = $request->get('CustomerGroup');
        $form->submit($submitedData);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->beginTransaction();
                $customerGroup = $this->createGroupFromForm($form);
                foreach ($oldGateways as $oldGateway) {
                    if ($customerGroup->getGateways()->contains($oldGateway) === false) {
                        $this->remove($oldGateway);
                    }
                }

                foreach ($customerGroup->getGateways() as &$gateway) {
                    $this->save($gateway);
                }

                $this->save($customerGroup);
                $this->commit();
                if (isset($submitedData['isDefault'])) {
                    $this->getRepository()->setDefaultById($groupId);
                }
            } catch (\Exception $e) {
                $this->rollback();

                throw $e;
            }

            return ['success' => true, 'data' => $customerGroup];
        }

        return ['success' => false];
    }

    /**
     * Get Errors
     * @param \Symfony\Component\Form\Form $form
     * @param bool $flatten
     *
     * @return array $errors
     */
    public function getErrorMessagesWithFormId($form, $flatten = false)
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $view = $error->getOrigin()->createView();
            if (!$flatten) {
                $errors[$key] = [
                    'message' => $error->getMessage(),
                    'formId' => $view->vars['id'],
                    'fullName' => $view->vars['full_name'],
                ];
            } else {
                $errors[] = [
                    'message' => $error->getMessage(),
                    'formId' => $view->vars['id'],
                    'fullName' => $view->vars['full_name'],
                ];
            }
        }
        foreach ($form as $child) {
            if (!$child->isValid()) {
                if (!$flatten) {
                    $errors[$child->getName()] = $this->getErrorMessagesWithFormId($child, $flatten);
                } else {
                    $errors = array_merge($errors, $this->getErrorMessagesWithFormId($child, $flatten));
                }
            }
        }

        return $errors;
    }

    public function getList($options = [])
    {
        $options['column'][] = '_main_.id';

        if (!array_has($options, 'order')) {
            $options = array_merge($options, [ 'order' => [['column' => '_main_.createdAt', 'dir' => 'desc']]]);
        }
        list($filter, $order, $select) = $this->processOption($options);
        $items = $this->getRepository()->getList($filter, $order, $select);

        $result = [
            'data' => $items,
            'recordsFiltered' => $this->getRepository()->getListFilterCount($filter),
            'recordsTotal' => $this->getRepository()->getListAllCount(),
        ];

        return $result;
    }

    /**
     *
     * @return \DbBundle\Repository\CustomerGroupRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerGroup');
    }

    private function createGroupFromForm(Form $form)
    {
        $customerGroup = $form->getData();
        if (!($customerGroup instanceof CustomerGroup)) {
            throw new WrongEntityException();
        }

        return $customerGroup;
    }
}
