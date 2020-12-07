<?php

namespace ApiBundle\Security;

use Doctrine\ORM\NoResultException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider implements UserProviderInterface
{
    private $userRepository;

    private function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function loadUserByUsername($username): \Symfony\Component\Security\Core\User\UserInterface
    {
        $qb = $this->userRepository->createQueryBuilder('u');
        $qb
            ->select('u, c')
            ->leftJoin('u.customer', 'c')
            ->where('u.username = :username AND u.deletedAt IS NULL')
            ->andWhere('u.type <> :affiliateType')
            ->setParameter('username', $username)
            ->setParameter('affiliateType', User::USER_TYPE_AFFILIATE)
        ;

        try {
            $user = $qb->getQuery()->getSingleResult();

            return $user;
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException(sprintf('Username (%s) not found ', $username));
        }
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->userRepository->find($user->getId());
    }

    public function supportsClass($class): bool
    {
        return $this->userRepository->getClassName() === $class || is_subclass_of($class, $this->userRepository->getClassName());
    }

    public static function generate(Registry $doctrine)
    {
        $userRepository = $doctrine->getRepository('DbBundle:User');

        return new self($userRepository);
    }
}
