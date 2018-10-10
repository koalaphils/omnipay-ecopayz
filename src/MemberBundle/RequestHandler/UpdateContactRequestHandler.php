<?php

namespace MemberBundle\RequestHandler;

use Doctrine\ORM\EntityManager;
use MemberBundle\Request\UpdateContactRequest;

/**
 * Description of UpdateContactRequest
 *
 * @author cydrick
 */
class UpdateContactRequestHandler
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(UpdateContactRequest $request)
    {
        $customer = $request->getCustomer();
        $contacts = $request->getContacts();
        $customer->setContacts($contacts);

        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);

        return $customer;
    }
}
