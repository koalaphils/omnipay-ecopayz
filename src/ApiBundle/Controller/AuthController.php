<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\Request\ChangePasswordRequest;
use ApiBundle\Request\ForgotPasswordRequest;
use ApiBundle\RequestHandler\AuthHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    /**
     * @ApiDoc(
     *     description="Check if token can still login",
     *     section="Auth",
     *     views={"piwi"},
     *     requirements={
     *         {
     *             "name"="pinnacle_token",
     *             "dataType"="string"
     *         }
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function checkIfAuthenticatedAction(Request $request, AuthHandler $handler): View
    {
        ;

        return $this->view($handler->handleCheckSession($request));
    }

    /**
     * @ApiDoc(
     *     section="Auth",
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
            $view->getContext()->setGroups(['Default', 'API', 'paymentOptions', 'details']);

            return $view;
        } catch (UsernameNotFoundException $exception) {
            return $this->view(['success' => false, 'error' => $exception->getMessage(), 'usernameExists' => false], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (OAuth2ServerException $exception) {
            return $this->view(['success' => false, 'error' => $exception->getDescription(), 'usernameExists' => true], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @ApiDoc(
     *     section="Auth",
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

        return $this->view(['success' => true]);
    }

    /**
     * @ApiDoc(
     *     section="Auth",
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
     *         },
     *         {
     *             "name"="grant_type",
     *             "dataType"="string"
     *         }
     *     }
     * )
     */
    public function refreshTokenAction(Request $request, AuthHandler $authHandler): View
    {
        return $this->view($authHandler->handleRefreshToken($request));
    }

    /**
     * @ApiDoc(
     *     section="Auth",
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

    /**
     * @ApiDoc(
     *     section="Auth",
     *     description="Change password",
     *     views={"piwi"},
     *     requirements={
     *         { "name"="verification_code", "dataType"="string" },
     *         { "name"="current_password", "dataType"="string" },
     *         { "name"="password", "dataType"="string" },
     *         { "name"="repeat_password", "dataType"="string" }
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function changePasswordAction(Request $request, AuthHandler $authHandler, ValidatorInterface $validator): View
    {
        $changePasswordRequest = ChangePasswordRequest::createFromRequestWithUser($request, $this->getUser());

        $violations = $validator->validate($changePasswordRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }

        $authHandler->handleChangePassword($changePasswordRequest);

        return $this->view(['success' => true]);
    }
}