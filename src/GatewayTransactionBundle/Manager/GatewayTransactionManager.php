<?php

namespace GatewayTransactionBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\GatewayTransaction;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\FormValidationException;
use GatewayTransactionBundle\Form\GatewayTransactionType;

class GatewayTransactionManager extends AbstractManager
{
    public function handleForm(Form $form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gatewayTransaction = $form->getData();

            $gatewayTransaction->setStatusAs(
                $form->has('save') ? $form->get('save')->isClicked() : false,
                $form->has('saveApproved') ? $form->get('saveApproved')->isClicked() : false,
                $form->has('saveVoid') ? $form->get('saveVoid')->isClicked() : false
            );

            $this->getRepository()->save($gatewayTransaction);

            return $gatewayTransaction;
        }

        throw new FormValidationException($form);
    }

    public function prepareForm($type, $id = 'new'): Form
    {
        if ($id === 'new') {
            $gatewayTransaction = new GatewayTransaction();

            $intType = GatewayTransaction::translateType($type);
            $gatewayTransaction->setType($intType);
            $gatewayTransaction->setNumber($this->generateNumber($intType));
        } else {
            $gatewayTransaction = $this->getRepository()->find($id);
        }

        $form = $this->getFormFactory()->create(GatewayTransactionType::class, $gatewayTransaction, [
            'action' => $this->getRouter()->generate('gateway_transaction.save', [
                'id' => $id,
                'type' => $type
            ]),
            'type' => $type,
        ]);

        return $form;
    }

    public function getList($filters = null)
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }
            $orders = (!array_has($filters, 'order')) ? [['column' => 'gt.date', 'dir' => 'desc']] : $filters['order'];

            $results['data'] = $this->getRepository()->getList($filters, $orders, \Doctrine\ORM\Query::HYDRATE_OBJECT);
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getListAllCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = array_map(function ($group) use ($filters) {
                return [
                    'id' => $group[array_get($filters, 'idColumn', 'id')],
                    'text' => $group['name'],
                ];
            }, $this->getRepository()->getCountryList($filters));

            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getList($filters);
        }

        return $results;
    }

    protected function getRepository(): \DbBundle\Repository\GatewayTransactionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:GatewayTransaction');
    }

    private function generateNumber($type): string
    {
        return date('Ymd-His-') . $type;
    }
}
