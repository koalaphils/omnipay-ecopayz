<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\RequestHandler\AuthHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends AbstractController
{
    /**
     * @ApiDoc(
     *     section="Authentication",
     *     description="Login",
     *     views = {"piwi"},
     *     requirements={
     *         {
     *             "name"="username",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="password",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="client_id",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="client_secret",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="scope",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="grant_type",
     *             "dataType"="string"
     *         }
     *     }
     * )
     */
    public function loginAction(Request $request, AuthHandler $authHandler): View
    {
        return $this->view($authHandler->handleLogin($request));
    }

    /**
     * @ApiDoc(
     *     section="Authentication",
     *     description="Logout",
     *     views = {"piwi"},
     *     requirements={
     *         {
     *             "name"="token",
     *             "dataType"="string"
     *         }
     *     }
     * )
     */
    public function logoutAction(Request $request, AuthHandler $authHandler): View
    {
        $authHandler->handleLogout($request);

        return $this->view();
    }

    /**
     * @ApiDoc(
     *     section="Authentication",
     *     description="Login",
     *     views = {"piwi"},
     *     requirements={
     *         {
     *             "name"="refresh_token",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="client_id",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="client_secret",
     *             "dataType"="string"
     *         }
     *     }
     * )
     */
    public function refreshTokenAction(AuthHandler $authHandler): View
    {
        $request = Request::createFromGlobals();
        $request->request->add([
            'grant_type' => 'refresh_token'
        ]);

        return $this->view($authHandler->handleRefreshToken($request));
    }
}