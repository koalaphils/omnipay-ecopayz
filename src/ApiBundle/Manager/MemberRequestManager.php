<?php

namespace ApiBundle\Manager;

use AppBundle\Manager\AbstractManager;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\Customer as Member;
use ApiBundle\Repository\MemberRequestRepository;
use DbBundle\Collection\Collection;
use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;

class MemberRequestManager extends AbstractManager
{

    public function getMemberRequestList(Member $member, Request $request)
    {
        $filters = ['memberId' => $member->getId()];

        $filters['limit'] = $request->get('limit', 10);
        $filters['offset'] = (((int) $request->get('page', 1))-1) * $filters['limit'];
        $orders = [];

        if ($request->query->has('orders')) {
            $orders = $request->query->get('orders');
        } else {
            $orders = [['column' => 'date', 'dir' => 'DESC']];
        }

        if ($request->query->has('sort')) {
            $filters['sort'] = $request->query->get('sort');
        }

        if ($request->query->has('from') && !empty($request->query->get('from'))) {
            $filters['from'] = convert_to_timezone($request->query->get('from'))->format('c');
        }

        if ($request->query->has('to') && !empty($request->query->get('to'))) {
            $filters['to'] = convert_to_timezone($request->query->get('to'))->format('c');
        }

        if ($request->query->has('search') && !empty($request->query->get('search'))) {
            $filters['search'] = $request->query->get('search');
        }

        if ($request->query->has('types') && !empty($request->query->get('types'))) {
            $filters['types'] = explode(',', $request->query->get('types'));
        }

        if ($request->query->has('status') && !empty($request->query->get('status'))) {
            $filters['status'] = $request->query->get('status');
            if (!is_array($filters['status'])) {
                $filters['status'] = [$filters['status']];
            }
        }

        $requests = $this->getRepository()->filters($filters, $orders);
        $total = $this->getRepository()->getTotal(['memberId' => $member->getId()]);
        $totalFiltered = $this->getRepository()->getTotal($filters);
        
        return new Collection($requests, $total, $totalFiltered, $filters['limit'], $request->get('page', 1));
    }

    protected function getRepository(): MemberRequestRepository
    {
        return $this->getContainer()->get('api.member_request_repository');
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getDoctrine()->getRepository(User::class);
    }
}