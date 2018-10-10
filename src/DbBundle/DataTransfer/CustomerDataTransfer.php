<?php

namespace DbBundle\DataTransfer;

use Symfony\Component\Routing\Router;
use JMS\Serializer\SerializerInterface;
use AppBundle\DataTransfer\DataTransferInterface;
use AppBundle\Exceptions\WrongEntityException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use DbBundle\Entity\Customer;
use JMS\Serializer\SerializerBuilder;

/**
 * Description of CustomerGroupDataTransfer
 */
class CustomerDataTransfer implements DataTransferInterface
{
    private $router;
    private $jmsSerializer;

    public function __construct(SerializerInterface $jmsSerializer, Router $router)
    {
        $this->router = $router;
        $this->jmsSerializer = $jmsSerializer;
    }

    public function transform($object, $args = array())
    {
        if (!($object instanceof Customer)) {
            throw new WrongEntityException();
        }
        //$serialize['_ref'] = $this->router->generate('customer.update_page', ['id' => $object->getId()]);

        return $serialize;
    }

    public function reverseTransform($object, $args = array())
    {
    }
}
