<?php

namespace PaymentOptionBundle\Manager;

use AppBundle\Exceptions\FormValidationException;
use AppBundle\Manager\AbstractManager;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Entity\PaymentOption;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class PaymentOptionManager extends AbstractManager
{
    public function handleCreateForm(Form $form, Request $request)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $paymentOption = $form->getData();
            $this->save($paymentOption);

            return $paymentOption;
        }

        throw new FormValidationException($form);
    }

    public function filter(array $params = [])
    {
        $filters = array_get($params, 'filters', []);
        $orders = array_get($params, 'orders', [['column' => 'createdAt', 'dir' => 'desc']]);
        $limit = array_get($params, 'limit', 20);
        if (array_has($params, 'page') && !array_has($params, 'offset')) {
            $offset = ((int) $params['page'] - 1) * $limit;
        } else {
            $offset = array_get($params, 'offset', 0);
        }

        $filteredTotal = $this->getRepository()->total($filters);
        $total = $this->getRepository()->total();

        $result = [
            'recordsFiltered' => $filteredTotal,
            'recordsTotal' => $total,
        ];

        if (array_get($params, 'datatable', 0) == 1 && array_has($params, 'draw')) {
            $result['data'] = $this->getRepository()->filter($filters, $orders, $limit, $offset);
            $result['draw'] = $params['draw'];
        } elseif (array_get($params, 'select2', 0)) {
            $result['items'] = array_map(function ($group) use ($filters) {
                return [
                    'id' => $group[array_get($filters, 'idColumn', 'po_code')],
                    'text' => $group['po_name'],
                ];
            }, $this->getRepository()->filter($filters, $orders, $limit, $offset, \Doctrine\ORM\Query::HYDRATE_SCALAR));
            $result['recordsFiltered'] = $filteredTotal;
        } else {
            $result['data'] = $this->getRepository()->filter($filters, $orders, $limit, $offset);
        }

        return $result;
    }

    protected function getRepository(): PaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository(PaymentOption::class);
    }
}
