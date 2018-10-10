<?php

namespace MemberBundle\RequestHandler;

use Doctrine\ORM\EntityManager;
use MemberBundle\Request\UpdateSocialsRequest;

/**
 * Description of UpdateContactRequest
 *
 * @author cydrick
 */
class UpdateSocialsRequestHandler
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(UpdateSocialsRequest $request)
    {
        $customer = $request->getCustomer();
        $socials = $request->getSocials();
        $customer->setSocials($socials);

        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);

        return $customer;
    }
}
