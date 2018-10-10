<?php

namespace ApiBundle\Controller;

use DbBundle\Collection\Collection;
use DbBundle\Entity\AffiliateCommission;
use DbBundle\Repository\AffiliateCommissionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AffiliateController extends AbstractController
{
    /**
     * @Nelmio\ApiDocBundle\Annotation\ApiDoc (
     *  description="Get affiliate commissions",
     *  headers={
     *      {
     *          "name"="AUTHORIZATION",
     *          "description"="Token Authorization"
     *      }
     *  },
     *  statusCode={
     *      200="Returned affiliate commissions",
     *      401="Returned when the user is not authorized",
     *      404="Returned if affiliate does not exists"
     *  },
     *  filters={
     *      {
     *          "name"="status",
     *          "dataType"="string"
     *      }
     *  },
     *  output={
     *      "class"="ArrayCollection<DbBundle\Entity\AffiliateCommission>",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "API" }
     *  }
     * )
     */
    public function affiliateCommissionAction(Request $request)
    {
        $user = $this->getUser();
        if ($user->getCustomer()->getIsAffiliate()) {
            throw $this->createNotFoundException('Affiliate not found');
        }
        $filters = array_merge($request->request->all(), $request->query->all());

        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        $commissions = $this->getAffiliateCommissionRepository()->findCommissions($filters, [], $limit, $offset);
        $totalFiltered = $this->getAffiliateCommissionRepository()->countCommissions($filters);
        $total = $this->getAffiliateCommissionRepository()->countCommissions();

        $collection = new Collection($commissions, $total, $totalFiltered, $limit, $page);

        return $this->view($collection);
    }

    public function getAffiliateCommissionRepository(): AffiliateCommissionRepository
    {
        return $this->getDoctrine()->getRepository(AffiliateCommission::class);
    }
}
