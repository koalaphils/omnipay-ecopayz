<?php

namespace ApiBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use PromoBundle\Manager\PromoManager;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Doctrine\ORM\Query;

class PromoController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Get promo list"
     * )
     */
    public function searchAction(Request $request): JsonResponse
    {
        $code = $request->get('code', '');
        $params = ['search' => $code];
        $promo = $this->getPromoRepository()->findByCode($params);

        return new JsonResponse($promo);
    }

    /**
     * @ApiDoc(
     *  description="Get promo members"
     * )
     */
    public function membersAction(Request $request): JsonResponse
    {
        $code = $request->get('code', '');
        $customer = $this->getUser()->getCustomer();
        $promo = $this->getPromoRepository()->findByCode(['search' => $code], Query::HYDRATE_OBJECT);

        $filters = [
            'referrer' => $customer->getIdentifier(),
            'promo' => $promo->getIdentifier(),
            'hasTransaction' => true
        ];

        $memberPromo = $this->getMemberPromoRepository()->findReferredMembers($filters);

        return new JsonResponse($memberPromo);
    }


    private function getPromoRepository(): \DbBundle\Repository\PromoRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Promo');
    }

    private function getMemberPromoRepository(): \DbBundle\Repository\MemberPromoRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:MemberPromo');
    }
}