<?php

namespace PromoBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DbBundle\Entity\Promo;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
    }

    public function searchAction(Request $request)
    {
        $filters = $request->request->all();
        $results = $this->getManager()->getActivePromos($filters);

        return new JsonResponse(['items'=>$results], JsonResponse::HTTP_OK);
    }

    protected function getPromoRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Promo');
    }

    protected function getManager()
    {
        return $this->getContainer()->get('promo.manager');
    }
}