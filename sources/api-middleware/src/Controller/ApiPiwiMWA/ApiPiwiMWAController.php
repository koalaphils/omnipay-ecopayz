<?php

namespace App\Controller\ApiPiwiMWA;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

class ApiPiwiMWAController extends DefaultController
{    
    private $client;    

    public function __construct () {        
        $this->client = new CLient([
            'base_uri' => 'http://localhost/api/{version}',
            'version' => 'v1',
            'headers' => [],
            'auth' => ['username', 'password', 'Basic']
        ]);
    }
   
    public function login()
    {   
        $res = $this->client->post('login', []);
        $content_json = $res->getBody()->getContents();

        return new Response($content_json);
    }
}
