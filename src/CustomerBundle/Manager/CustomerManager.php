<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CustomerBundle\Manager;

use DbBundle\Entity\Customer;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Manager\AbstractManager;
use AppBundle\Exceptions\FormValidationException;
use Firebase\JWT\JWT;
use DbBundle\Entity\User;
use DbBundle\Entity\AuditRevisionLog;

class CustomerManager extends AbstractManager
{
    /**
     * @deprecated since version 1.1
     *
     * @param mixed $filters
     *
     * @return array
     */
    public function getCustomerList($filters = null)
    {
        $status = true;
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            $order = (!array_has($filters, 'order')) ? [['column' => 'c.joinedAt', 'dir' => 'desc']] : $filters['order'];
            if (array_get($filters, 'search.value', false) !== false) {
                $filters['search'] = $filters['search']['value'];
            }
            $results['data'] = $this->getRepository()->getCustomerList($filters, $order);
            $currencies = [];
            if (array_get($filters, 'withCurrency', 0)) {
                foreach ($results['data'] as $customer) {
                    $cust = current($customer);
                    $currencies[$cust['currency']['id']] = $cust['currency'];
                }
            }
            if (array_get($filters, 'route', 0) || $withCurrency) {
                $results['data'] = array_map(function ($_data) use ($filters, $currencies) {
                    $data['customer'] = current($_data);
                    $data['referralCount'] = end($_data);
                    if ($data['customer']['isAffiliate'] && array_get($filters, 'route', 0) && isset($filters['isAffiliate'])) {
                        $data['routes'] = [
                            'update' => $this->getRouter()->generate('affiliate.update_page', ['id' => $data['customer']['id']]),
                            'view' => $this->getRouter()->generate('customer.view_page', ['id' => $data['customer']['id']]),
                        ];
                    } elseif (array_get($filters, 'route', 0) || isset($filters['isCustomer'])) {
                        $data['routes'] = [
                            'update' => $this->getRouter()->generate('customer.update_page', ['id' => $data['customer']['id']]),
                            'view' => $this->getRouter()->generate('customer.view_page', ['id' => $data['customer']['id']]),
                        ];
                    }
                    if (array_get($filters, 'withCurrency', 0)) {
                        //$data['currency'] = $currencies[$_data['currencyName']];
                    }

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getCustomerListFilterCount($filters);
            if (array_get($filters, 'isAffiliate', false)) {
                $results['recordsTotal'] = $this->getRepository()->getTotalAffiliate();
            } else {
                $results['recordsTotal'] = $this->getRepository()->getTotalCustomer();
            }
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = $this->getRepository()->getCustomerList($filters);

            if (array_get($filters, 'withCurrency', 0)) {
                $currencies = [];
                foreach ($results['items'] as $customer) {
                    $cust = current($customer);
                    $currencies[$cust['customer']['id']] = $cust['customer'];
                }

                $results['items'] = array_map(function ($data) use ($currencies) {
                    //$data['currency'] = $currencies[$data['currencyName']];

                    return current($data);
                }, $results['items']);
            }

            $results['recordsFiltered'] = $this->getRepository()->getCustomerListFilterCount($filters);
        } else {

            $result = $this->getRepository()->getCustomerList($filters);
            foreach ($result as $customer) {
                $results[] = current($customer);
            }
        }

        return $results;
    }

    public function getList($options = [])
    {
        $options['column'][] = '_main_.id';
        list($filter, $order, $select) = $this->processOption($options);

        $items = $this->getRepository()->getList($filter, $order, $select);
        if (array_has($options, 'select2'))  {
            if (!empty($items)) {
                $items = $this->addCustomersDefaultGroup($items);
            }
        }
        $result = [
            'data' => $items,
            'filtered' => $this->getRepository()->getCustomerListFilterCount($filter),
            'total' => $this->getRepository()->getCustomerListAllCount(),
        ];

        return $result;
    }

    public function getDocuments($customerId)
    {
        $customer = $this->getRepository()->find($customerId);
        $files = $customer->getFiles();

        foreach ($files as &$file) {
            $path = array_get($file, 'folder', '') . '/' . $file['file'];
            $file = array_merge($file, $this->getMediaManager()->getFile($path, true));
        }
        unset($customer);

        return $files;
    }

    public function handleCreateCustomerForm(Form $form, Request $request)
    {
        $form->handleRequest($request);
        $guestType = array_get($request->request->get('Customer'), 'guestType');
        $defaultGroup = $this->getCustomerGroupRepository()->getDefaultGroup();
        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $form->getData();
            $password = $this
                ->get('security.password_encoder')
                ->encodePassword($customer->getUser(), $customer->getUser()->getPassword())
            ;
            $customer->getUser()->setPassword($password);
            $customer->getUser()->setPreferences([
                'ipAddress' => $request->getClientIp(),
            ]);
            $transactionPassword = $this->get('security.password_encoder')->encodePassword($customer->getUser(), '');
            $customer->setTransactionPassword($transactionPassword);

            $customerDetails = $request->request->get('Customer');
            if ($guestType == \DbBundle\Entity\Customer::AFFILIATE) {
                $customerSwitch = array_get($customerDetails, 'isCustomer');
                if ($customerSwitch) {
                    $customer->setIsCustomer(true);
                }
                $customer->getGroups()->add($defaultGroup);
                $customer->setIsAffiliate(true);
            } else if ($guestType == \DbBundle\Entity\Customer::CUSTOMER) {
                $affiliateSwitch = array_get($customerDetails, 'isAffiliate');
                if ($affiliateSwitch) {
                    $customer->setIsAffiliate(true);
                }
                $customer->setIsCustomer(true);
            }
            $customer->setDetails([
                'websocket' => [
                    'channel_id' => uniqid($customer->getId() . generate_code(10, false, 'ld')),
                ],
                'enabled' => false,
            ]);
            $this->save($customer);

            return $customer;
        }

        throw new FormValidationException($form);
    }

    public function getCustomerLoginHistory(array $filters = []): array
    {
        $startingPointOfArray = 0;
        $numberOfItemsToDisplay = 10;
        if (array_get($filters, 'search.value', false) !== false) {
            $filters['search'] = $filters['search']['value'];
        }
        
        $filters['type'] = User::USER_TYPE_MEMBER;
        $filters['category'] = AuditRevisionLog::CATEGORY_LOGIN;
        $filters['operation'] = AuditRevisionLog::OPERATION_LOGIN;
        
        $order = [['column' => 'ar.timestamp', 'dir' => 'desc']]; //order timestamp latest to old ONLY... no need for dynamic
        $returnedData = $this->getAuditRepository()->getHistoryIPList($filters, $order);
        if (array_get($filters, 'datatable', 0)) {
            $results['data'] = array_slice($returnedData, $startingPointOfArray, $numberOfItemsToDisplay);
            $results['recordsTotal'] = $results['recordsFiltered'] = !empty($results['data']) ? count($results['data']) : 0;
        } else {
            $results = array_slice($returnedData, $startingPointOfArray, $numberOfItemsToDisplay);
        }
        
        return $results;
    }

    public function getTagList(): array
    {
        $return = [
            [ 'code' => Customer::ACRONYM_AFFILIATE, 'text' => Customer::AFFILIATE ],
            [ 'code' => Customer::ACRONYM_MEMBER, 'text' => Customer::MEMBER ]
        ];

        return $return;
    }

    /**
     * @return \DbBundle\Repository\CustomerRepository
     */
    public function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    /**
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Currency');
    }

    /**
     * Get media manager.
     *
     * @return \MediaBundle\Manager\MediaManager
     */
    protected function getMediaManager()
    {
        return $this->getContainer()->get('media.manager');
    }

    /**
     * Get Customer Group repository.
     *
     * @return \DbBundle\Repository\CustomerGroupRepository
     */
    protected function getCustomerGroupRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerGroup');
    }

    /**
     * Get AuditRevision repository.
     *
     * @return \DbBundle\Repository\AuditRevisionRepository
     */
    protected function getAuditRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:AuditRevision');
    }
    /**
     * Add default/temporary group to migrated data with no existing group
     * @param array $customers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection $returnedData
     */
    private function addCustomersDefaultGroup(array $customers = [])
    {
        $returnedData = new \Doctrine\Common\Collections\ArrayCollection();
        $defaultGroup = $this->getCustomerGroupRepository()->getDefaultGroup();
        if (!empty($customers)) {
            foreach ($customers as $customer) {
                if ($customer->getGroups()->isEmpty()) {
                    $customer->getGroups()->add($defaultGroup);
                }
                $returnedData->add($customer);
            }
        }

        return $returnedData;
    }

    private function addDefaultGroup(Customer $customer)
    {
        if (!$customer->hasGroups()) {
            $this->addCustomersDefaultGroup([$customer]);
        }
    }

    /**
     * this is a factory method.
     * I recommend to replace all calls of CustomerRepository::findById() with this so could return "fixed" / "complete" customer entity
     * @param int $customerId
     * @return Customer|null
     */
    public function findById(int $customerId) :? Customer
    {
        $customer = $this->getRepository()->findById($customerId);
        if ($customer instanceof  Customer) {
            $this->addDefaultGroup($customer);
            return $customer;
        }
    }
}
