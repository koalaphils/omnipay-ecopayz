<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SecurityController extends Controller
{
    public function loginAction(\Symfony\Component\HttpFoundation\Request $request)
    {
        $authChecker = $this->get('security.authorization_checker');
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app.dashboard_page');
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        return $this->render('AppBundle:Security:login.html.twig', [
            'lastusername' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'fromLogout' => $request->get('flg', 0),
        ]);
    }
}
