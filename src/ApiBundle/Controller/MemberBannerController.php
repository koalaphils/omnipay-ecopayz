<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberBannerController extends AbstractController
{
    /**
     * @ApiDoc(
     *     description="Gets list of generated member banners"
     * )
     */
    public function listAction(): \FOS\RestBundle\View\View
    {
        $memberBanners = $this->getMemberBannerManager()->list();

        return $this->view($memberBanners);
    }

    /**
     * @ApiDoc(
     *  description="Create member banner",
     *  input={
     *     "name"="memberBanner",
     *     "class"="ApiBundle\Request\CreateMemberBannerRequest"
     *  }
     * )
     */
    public function generateAction(Request $request): \FOS\RestBundle\View\View
    {
        $memberBannerRequest = $request->request->get('memberBanner');
        $user = $this->getUser();

        $memberBannerForm = $this->createForm(
            \ApiBundle\Form\Member\MemberBannerType::class,
            \ApiBundle\Request\CreateMemberBannerRequest::create(),
            ['user' => $user]
        );

        $memberBannerForm->submit($memberBannerRequest);

        if ($this->isFormProcessable($memberBannerForm)) {
            $memberBanner = $this->getMemberBannerManager()->generate(
                $memberBannerForm->getData()
            );

            return $this->view($memberBanner);
        }

        return $this->view($memberBannerForm, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function isFormProcessable(FormInterface $form): bool
    {
        return $form->isSubmitted() && $form->isValid();
    }

    private function getMemberBannerManager(): \ApiBundle\Manager\MemberBannerManager
    {
        return $this->get('api.member_banner.manager');
    }
}