<?php

namespace DbBundle\Listener;

use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class ActionListener implements \AppBundle\Interfaces\UserAwareInterface
{
    use \AppBundle\Traits\UserAwareTrait;

    private $token;
    private $appEnvironment = '';
    private $entityManager;

    public function __construct(string $appEnvironment, $entityManager)
    {
        $this->appEnvironment = $appEnvironment;
        $this->entityManager = $entityManager;
    }

    private function isUnderTestEnvironment()
    {
        return $this->appEnvironment === 'test';
    }

    private function getSuperAdmin(): User
    {
        return $this->entityManager->getRepository(User::class)->findAdminByUsername('admin');
    }

    private function getSystemUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findAdminByUsername('system');
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof \DbBundle\Entity\User && property_exists($entity, 'forSetup')) {
            if (!($this->_getUser() instanceof \DbBundle\Entity\User)) {
                return;
            }
        }

        if ($entity instanceof \DbBundle\Entity\Interfaces\ActionInterface) {
            $user = $this->_getUser();
            
            if ($user === null && ($entity instanceof User || $entity instanceof CustomerProduct)) {
                //For Member Creation
                $system = $this->getSystemUser();
                $entity->setCreatedBy($system->getId());
            } else {
                if ((!$user instanceof User) && $this->isUnderTestEnvironment()) {
                    $user = $this->getSuperAdmin();
                }
    
                if (!$user instanceof User) {
                    throw new \Exception('There is no logged-in user');
                }
                $entity->setCreatedBy($user->getId());
                if ($entity instanceof Transaction || $entity instanceof User) {
                    # this is just a workaround
                    # until transaction.createdBy or user.createdBy is associated to User entity (requires others who implement ActionListener to do the same)
                    $entity->setCreator($user);
                }
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof \DbBundle\Entity\Interfaces\ActionInterface) {
            $entity->setUpdatedBy($this->_getUser()->getId());
        }
    }

    public function setTokenStorage(TokenStorageInterface $token)
    {
        $this->token = $token;
    }

    /**
     * @return \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    protected function getSecurityTokenStorage()
    {
        return $this->token;
    }

    protected function hasSecurityTokenStorage()
    {
        if ($this->token instanceof TokenStorageInterface) {
            return true;
        }

        return false;
    }
}
