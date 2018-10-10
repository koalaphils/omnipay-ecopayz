<?php

namespace ApiBundle\Manager;

use ApiBundle\Request\CreateMemberBannerRequest;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\MemberBanner;

class MemberBannerManager extends AbstractManager
{
    public function list(): array
    {
        $memberBanners = $this->getRepository()->findMemberBanners(
            ['member' => $this->getUser()->getMemberId()],
            ['mb.createdAt' => 'DESC']
        );

        return $memberBanners;
    }

    public function generate(CreateMemberBannerRequest $createMemberBannerRequest): MemberBanner
    {
        return $this->getCreateMemberBannerRequestHandler()->handle(
            $createMemberBannerRequest,
            $this->getUser()->getMember()
        );
    }

    private function getCreateMemberBannerRequestHandler(): \ApiBundle\RequestHandler\CreateMemberBannerRequestHandler
    {
        return $this->get('api.member.create_banner.request_handler');
    }

    public function getRepository(): \DbBundle\Repository\MemberBannerRepository
    {
        return $this->getDoctrine()->getRepository(MemberBanner::class);
    }
}