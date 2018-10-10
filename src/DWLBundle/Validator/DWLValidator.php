<?php

namespace DWLBundle\Validator;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Description of DWLValidator.
 *
 * @author cnonog
 */
class DWLValidator
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param \DbBundle\Entity\DWL      $object
     * @param ExecutionContextInterface $context
     */
    public function validate($object, ExecutionContextInterface $context)
    {
        if (!($object instanceof \DbBundle\Entity\DWL)) {
            return;
        }
    }

    /**
     * Get dwl repository.
     *
     * @return \DbBundle\Repository\DWLRepository
     */
    public function getRepository()
    {
        return $this->doctrine->getRepository('DbBundle:DWL');
    }
}
