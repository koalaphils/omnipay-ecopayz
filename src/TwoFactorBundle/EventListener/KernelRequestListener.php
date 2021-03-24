<?php

namespace TwoFactorBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;

use DbBundle\Entity\User;
use DbBundle\Entity\TwoFactorCode;
use TwoFactorBundle\Provider\TwoFactorRegistry;

class KernelRequestListener
{   
    private $templating;    
    private $token;
    private $twoFactorRegistry;
    private $router;

    public function __construct(
        EngineInterface $templating,
        TokenStorageInterface $token,
        Router $router,
        TwoFactorRegistry $twoFactorRegistry)
    {
        $this->templating = $templating;
        $this->token = $token;
        $this->twoFactorRegistry = $twoFactorRegistry;
        $this->router = $router;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->token->getToken();
        $request = $event->getRequest();

        if (!$token)
        {
            return;

        }
        if (!$token instanceof UsernamePasswordToken)
        {
            return;
        }

        $key = LoginSuccessListener::getKey($token);
        $session = $request->getSession();

        if (!$session->has($key))
        {
            return;
        }

        if ($request->getMethod() == 'POST')
        {
           $submittedCode = $request->get('_auth_code');
           $isValid = $this->twoFactorRegistry->validateCode($submittedCode, [
               'provider' => 'email',
               'email' => $token->getUser()->getEmail(),
               'purpose' => 'default'
           ]);
            if ($isValid) {
                $session->remove($key);

                //Redirect to user's dashboard
                $redirect = new RedirectResponse($this->router->generate('app.dashboard_page'));
                $event->setResponse($redirect);

                return;
            } else {
                $session->getFlashBag()->set('2faError', 'The verification code is not valid.');
            }
           
        }

        $response = $this->templating->renderResponse('TwoFactorBundle::2fa-form.html.twig', [ 'email' => $token->getUser()->getEmail()]);
        $event->setResponse($response);
    }
}   