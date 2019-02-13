<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use ApiBundle\Form\MemberProduct;
use ApiBundle\Manager\MemberProductManager;
use ApiBundle\Request\CreateMemberProductRequest;

class MemberProductController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Create new member product",
     *  input={
     *     "class"="ApiBundle\Form\MemberProduct\MemberProductListType"
     *  }
     * )
     */
    public function createAction(Request $request): View
    {
        $memberProductRequest = $request->request->get('memberProductList');

        $memberProductForm = $this->createForm(
            MemberProduct\MemberProductListType::class,
            CreateMemberProductRequest\MemberProductList::create()
        );

        $memberProductForm->submit($memberProductRequest);

        if ($this->isFormProcessable($memberProductForm)) {
            $memberProducts = $this->getMemberProductManager()->create(
                $memberProductForm->getData()
            );

            return $this->view($memberProducts);
        }

        return $this->view($memberProductForm, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function isFormProcessable(FormInterface $form): bool
    {
        return $form->isSubmitted() && $form->isValid();
    }

    private function getMemberProductManager(): MemberProductManager
    {
        return $this->get('api.member_product.manager');
    }
}