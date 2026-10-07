<?php

// Namespace declaration
namespace App\Repository;

// Import the User entity (the entity this repository manages)
use App\Entity\User;
// Import the base repository class
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
// Import ManagerRegistry
use Doctrine\Persistence\ManagerRegistry;
// Import exception for unsupported user types
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
// Import interfaces for password upgrade feature
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * UserRepository — custom database queries for the User entity.
 *
 * Also implements PasswordUpgraderInterface, which allows Symfony to automatically
 * re-hash user passwords when the hashing algorithm is updated (e.g. when migrating
 * from bcrypt to argon2). This happens transparently during login.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * Constructor — tells Doctrine that this repository manages the User entity.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Automatically upgrade (re-hash) the user's password when needed.
     * This method is called by Symfony's security system during login if the
     * current password hash uses an outdated algorithm.
     *
     * For example, if the app switches from bcrypt to argon2, existing users'
     * passwords will be automatically re-hashed the next time they log in.
     *
     * @param PasswordAuthenticatedUserInterface $user The user whose password to upgrade
     * @param string $newHashedPassword The newly hashed password
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // Safety check: ensure we received an instance of our User entity
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        // Update the user's password with the new hash
        $user->setPassword($newHashedPassword);
        // Save to database
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Count the total number of registered customers.
     * Used on the admin dashboard to display the "Total Customers" statistic.
     *
     * @return int Total number of users in the database
     */
    public function countCustomers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')     // SQL: SELECT COUNT(id) FROM users
            ->getQuery()
            ->getSingleScalarResult();
    }
}
