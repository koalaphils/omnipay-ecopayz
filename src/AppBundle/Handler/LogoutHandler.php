<?php

namespace AppBundle\Handler;

use DbBundle\Entity\AuditRevisionLog;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LogoutHandler implements LogoutSuccessHandlerInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public function onLogoutSuccess(Request $request)
    {
        $token = $this->getTokenStorage()->getToken();
        if ($token !== null) {
            $this->container->get('audit.manager')->audit(
                $token->getUser(),
                AuditRevisionLog::OPERATION_LOGOUT,
                AuditRevisionLog::CATEGORY_LOGOUT
            );
        }
        
        return new RedirectResponse($this->getRouter()->generate('app.login_page'));
    }

    /**
     * Get router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }

    private function getTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }
}
