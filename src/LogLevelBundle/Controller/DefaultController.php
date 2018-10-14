<?php

namespace LogLevelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('LogLevelBundle:Default:index.html.twig');
    }
}
