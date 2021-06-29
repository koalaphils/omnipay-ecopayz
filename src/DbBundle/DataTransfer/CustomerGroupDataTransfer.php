<?php

namespace DbBundle\DataTransfer;

use Symfony\Component\Routing\Router;
use AppBundle\DataTransfer\DataTransferInterface;
use AppBundle\Exceptions\WrongEntityException;

/**
 * Description of CustomerGroupDataTransfer
 */
class CustomerGroupDataTransfer implements DataTransferInterface
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function transform($object, $args = array())
    {
        if (!($object instanceof \DbBundle\Entity\CustomerGroup)) {
            throw new WrongEntityException();
        }

        return [
            '_ref' => $this->router->generate('customer.group_edit_page', ['id' => $object->getId()]),
            'id' => $object->getId(),
            'name' => $object->getName(),
        ];
    }

    public function reverseTransform($object, $args = array())
    {
    }
}
