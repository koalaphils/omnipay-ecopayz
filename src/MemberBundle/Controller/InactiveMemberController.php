<?php

namespace MemberBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class InactiveMemberController extends Controller
{
    public function listAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);

        return $this->render('MemberBundle:InactiveMember:inactive-list.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $itemsPerPage = $request->get('length', 10);
        $offset = $request->get('start', 0);
        $page = ($offset+1) * $itemsPerPage;
        $draw = $request->get('draw', 1);
        $filters = $request->get('filter', []);
        $customerNameToSearch = $filters['search'] ?? null;
        $memberData = $this->get('member.inactive.manager')->getInactiveMembersForListing($itemsPerPage, $offset, $customerNameToSearch);
        $inactiveMembersCount = $this->get('member.inactive.manager')->getInactiveMembersCount();
        $filteredResultsCount = $inactiveMembersCount;
        if (!empty($customerNameToSearch)) {
            $filteredResultsCount = $this->get('member.inactive.manager')->getFilteredInactiveMembersCount($customerNameToSearch);
        }

        $data = [
            'data'=> $memberData,
            'draw' => $draw,
            'recordsFiltered'=> $filteredResultsCount,
            'recordsTotal' => $inactiveMembersCount,
            'limit' => 10,
            'page' => $page,
        ];

         return new JsonResponse($data);
    }

    public function updateListAction()
    {
        $this->get('member.inactive.manager')->updateInactiveList();

        return new Response();
    }

    public function removeMemberAction(Request $request, int $memberId)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);

        $result = $this->get('member.inactive.manager')->removeMemberIdFromList($memberId);

        $responseCode = Response::HTTP_BAD_REQUEST;
        if ($result === true) {
            $responseCode = Response::HTTP_OK;
        }

        return new JsonResponse(['success' => $result], $responseCode);
    }

}
