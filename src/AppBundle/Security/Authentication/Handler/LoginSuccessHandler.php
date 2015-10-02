<?php
namespace AppBundle\Security\Authentication\Handler;

use AppBundle\Services\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

class LoginSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    protected $userService;

    public function __construct(HttpUtils $httpUtils, UserService $userService, array $options = array())
    {
        $this->userService = $userService;
        parent::__construct($httpUtils, $options);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $this->userService->importAuthenticatedUser($token);

        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
    }
}
