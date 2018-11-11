<?php

namespace App\Controller\ApiPiwiBackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

class ApiPiwiBackOfficeController extends DefaultController
{
    
    /**
     * client
     */
    private $client;    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct () {       
        $this->client = new CLient([
            'base_uri' => 'http://localhost:9000/api/{version}',
            'version' => 'v1',
            'headers' => [],
            'auth' => ['username', 'password', 'Basic']
        ]);
    }   

    public function login()
    {   
        $res = $this->client_test->post('login', []);
        $content_json = $res->getBody()->getContents();

        return new Response($content_json);
    }
}
