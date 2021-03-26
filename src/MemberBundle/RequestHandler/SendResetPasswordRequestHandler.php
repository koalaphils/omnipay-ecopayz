<?php

namespace MemberBundle\RequestHandler;

use Doctrine\ORM\EntityManager;
use MemberBundle\Request\SendResetPasswordRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Manager\UserManager;

class SendResetPasswordRequestHandler
{
    private $entityManager;
    private $translator;
    private $userManager;
    private $defaultUrl;

    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        UserManager $userManager,
        string $defaultUrl
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->userManager = $userManager;
        $this->defaultUrl = $defaultUrl;
    }

    public function handleSendResetPassword(SendResetPasswordRequest $request): string
    {
        $user = $request->getMember()->getUser();
        $customer = $user->getCustomer();
        $origin = $request->getOrigin() ?: $this->defaultUrl;

        if ($user->isActivated()) {
            $this->userManager->sendResetPasswordLink($user, $origin);
            return new JsonResponse([
                '__notifications' => [
                    [
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans('notification.resetPassword.success.title', [], 'CustomerBundle'),
                        'message' => $this->getTranslator()->trans(
                            'notification.resetPassword.success.message',
                            ['%name%' => $customer->getFullName() . ' (' . $user->getUsername() . ')'],
                            'CustomerBundle'
                        ),
                    ],
                ],
            ], Response::HTTP_OK);
        } else {
            throw new \Exception(
                $this->getTranslator()->trans(
                    'notification.resetPassword.error.message',
                    [],
                    'CustomerBundle'
                ), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
