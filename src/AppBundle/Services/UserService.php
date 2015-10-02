<?php

namespace AppBundle\Services;

use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserService
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function importAuthenticatedUser(UsernamePasswordToken $token)
    {
        $this->userRepository->importAuthenticatedUser($token->getUser());
    }
}
