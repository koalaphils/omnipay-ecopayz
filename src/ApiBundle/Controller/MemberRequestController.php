<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use ApiBundle\Manager\MemberRequestManager;

class MemberRequestController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Get member requests",
     *  section="Member",
     *  views={"piwi"},
     *  filters={
     *      {"name"="search", "dataType"="string"},
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="orders[0][column]", "dataType"="array"},
     *      {"name"="orders[0][dir]", "dataType"="array"},
     *      {"name"="page", "dataType"="integer"},
     *      {"name"="from", "dataType"="date"},
     *      {"name"="to", "dataType"="date"},
     *      {"name"="types", "dataType"="string"},
     *      {"name"="status"}
     *  }
     * )
     */
    public function memberRequestsAction(Request $request): View
    {
        $member = $this->getUser()->getCustomer();
        $memberRequestList = $this->getMemberRequestManager()->getMemberRequestList($member, $request);

        return $this->view($memberRequestList);
    }

    private function getMemberRequestManager(): MemberRequestManager
    {
        return $this->container->get('api.member_request_manager');
    }

}