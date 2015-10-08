<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\User;

/**
 * This custom Doctrine repository is empty because so far we don't need any custom
 * method to query for application user information. But it's always a good practice
 * to define a custom repository that will be used when the application grows.
 * See http://symfony.com/doc/current/book/doctrine.html#custom-repository-classes
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class UserRepository extends EntityRepository
{
    /**
     * Inserts data of authenticated user to User table
     * if user has already been inserted then this function
     * updates following db values: display_name, manager_id, title
     *
     * @param User $user
     * @throws \Doctrine\DBAL\DBALException
     */
    public function importAuthenticatedUser(User $user)
    {
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'INSERT OR IGNORE INTO user(username, email, uuid, password, displayName, roles)
                    VALUES(:username, :email, :uuid, :password, :displayName, :roles)',
                [
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'uuid' => $user->getUuid(),
                    'password' => md5(microtime()),
                    'displayName' => $user->getDisplayName(),
                    'roles' => ''
                ]
            );
    }
}
