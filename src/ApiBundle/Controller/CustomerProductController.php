<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class CustomerProductController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Check if the product username input on registration exists or not",
     *  requirements={
     *      {
     *          "name"="product[code]",
     *          "dataType"="string",
     *          "description"="Product code of selected product"
     *      },
     *     {
     *          "name"="product[username]",
     *          "dataType"="string",
     *          "description"="Desired username of selected product"
     *      }
     *  }
     * )
     */
    public function checkIfProductUsernameExistsAction(Request $request)
    {
        $product = $request->request->get('product');

        $result = $this->getCustomerProductManager()->checkIfProductUsernameExists($product);

        return $this->view($result, $result['code']);
    }

    private function getCustomerProductManager(): \ApiBundle\Manager\CustomerProductManager
    {
        return $this->container->get('api.customer_product_manager');
    }
}
