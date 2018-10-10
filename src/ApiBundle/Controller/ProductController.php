<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Collection\Collection;

use ApiBundle\Repository\ProductRepository;

class ProductController extends AbstractController
{
     /**
     * @ApiDoc(
     *  description="Get all products",
     *  output={
     *      "class"="ArrayCollection<DbBundle\Entity\Product>",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "API" }
     *  },
     *  filters={
     *      {
     *          "name"="is_active",
     *          "dataType"="boolean"
     *      },
     *      {
     *          "name"="exclude",
     *          "dataType"="string",
     *          "description"="Exclude specific products. For ex: AO,BI,ACW"
     *      },
     *      {
     *          "name"="limit",
     *          "dataType"="integer"
     *      },
     *      {
     *          "name"="page",
     *          "dataType"="integer"
     *      }
     *  }
     * )
     */
    public function productListAction(Request $request)
    {
        $repository = $this->getProductRepository();
        $filters = [];

        $filters['limit'] = $request->get('limit', 20);
        $filters['offset'] = (((int) $request->get('page', 1))-1) * $filters['limit'];

        if ($request->query->has('is_active')) {
            $filters['is_active'] = $request->query->get('is_active');
        }

        if ($request->query->has('exclude')) {
            $filters['exclude'] = $request->query->get('exclude');
        }

        $products = $repository->list($filters);
        $total = $repository->getTotal();

        $collection = new Collection($products, $total, $total, $filters['limit'], $request->get('page', 1));

        $view = $this->view($collection);
        $view->getContext()->setGroups(['Default', 'API', 'items' => ['Default', 'API']]);

        return $view;
    }

    protected function getProductRepository() : ProductRepository
    {
        return $this->get('api.product_repository');
    }
}
