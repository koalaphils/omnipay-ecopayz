<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\RequestHandler\MemberHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class CurrentMemberController extends AbstractController
{
    /**
     * @ApiDoc(
     *     section="Current Login Member",
     *     description="Get Pinnacle Balance",
     *     views={"default", "piwi"},
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getPinnacleBalanceAction(MemberHandler $memberHandler): View
    {
        $user = $this->getUser();

        return $this->view($memberHandler->handleGetBalance($user->getCustomer()));
    }

    public function transactionListAction(): View
    {

    }
}