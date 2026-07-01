<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Returns the agents the given user is allowed to see results for:
     * - chef de plateau: everyone;
     * - superviseur: the agents who report to them;
     * - agent: only themselves.
     *
     * @return list<User>
     */
    public function findAccessibleAgents(User $current): array
    {
        if (in_array('ROLE_CHEF_PLATEAU', $current->getRoles(), true)) {
            return $this->findBy([], ['email' => 'ASC']);
        }

        if (in_array('ROLE_SUPERVISEUR', $current->getRoles(), true)) {
            return $this->findBy(['manager' => $current], ['email' => 'ASC']);
        }

        return [$current];
    }
}
