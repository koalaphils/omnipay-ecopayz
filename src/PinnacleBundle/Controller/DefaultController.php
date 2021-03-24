<?php

namespace PinnacleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('PinnacleBundle:Default:index.html.twig');
    }
}
