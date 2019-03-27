<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\Request\ForgotPasswordRequest;
use ApiBundle\RequestHandler\AuthHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        try {
            $view = $this->view($authHandler->handleLogin($request));
            $view->getContext()->setGroups(['Default', 'API', 'paymentOptions']);

            return $view;
        } catch (OAuth2ServerException $exception) {
            return $this->view(['success' => false, 'error' => $exception->getDescription()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
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

    /**
     * @ApiDoc(
     *     section="Authentication",
     *     description="Forgot Password",
     *     views={"piwi"},
     *     requirements={
     *         { "name"="verification_code", "dataType"="string" },
     *         { "name"="email", "dataType"="string" },
     *         { "name"="country_phone_code", "dataType"="string" },
     *         { "name"="phone_number", "dataType"="string" },
     *         { "name"="password", "dataType"="string" },
     *         { "name"="repeat_password", "dataType"="string" }
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function forgotPasswordAction(Request $request, AuthHandler $authHandler, ValidatorInterface $validator): View
    {
        $forgotPasswordRequest = ForgotPasswordRequest::createFromRequest($request);

        $violations = $validator->validate($forgotPasswordRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }

        $authHandler->handleForgotPassword($forgotPasswordRequest);

        return $this->view(['success' => true]);
    }
}