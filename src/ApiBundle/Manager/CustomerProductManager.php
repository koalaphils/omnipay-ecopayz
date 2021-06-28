<?php

namespace ApiBundle\Manager;

use AppBundle\Manager\AbstractManager;

class CustomerProductManager extends AbstractManager
{
    public function checkIfProductUsernameExists($product): array
    {
        $customerProduct = $this->getRepository()->findByCodeAndUsername($product);

        if ($customerProduct !== null) {
            return ['message' => 'Product username exists.', 'code' => 422];
        }

        return ['message' => 'Product username does not exist.', 'code' => 200];
    }

    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }
}
