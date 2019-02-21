<?php

namespace App\Controller\ApiPinnacle;

use App\Controller\TokenAuthenticatedController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

// logger
use Psr\Log\LoggerInterface;
use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class ApiPinnacleController extends DefaultController
{
    private $client;    
    private $token;
    private $userCode;
    
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    public function __construct (){        
        $this->logger = new MonoLogger('ApiPinnacleLogger');
        $this->logger->pushHandler(new StreamHandler(__DIR__.'/../../../var/log/debug_'.date('ymd').'.log', MonoLogger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler()); 

        $this->client = new CLient([
            'base_uri' => 'https://paapistg.oreo88.com',
            'headers' => [
                'userCode' => '',
                'token' => ''
            ]
        ]);
    }

    public function generateToken(){
        $agentCode='PS711';
        $agentKey='035c48be-9959-4556-80eb-9a0224814c88';
        $secretKey='P6Aae0ej2dwnJe9B';

        $timestamp = time() * 1000;
        $hashToken = md5($agentCode.$timestamp.$agentKey);
        $tokenPayload = $agentCode . '|' . $timestamp . '|' . $hashToken;
        $token = $this->encryptAES($tokenPayload, $secretKey);

        return $token;
    }   

    private function encryptAES($tokenPayload, $secretKey){
        $iv = 'RandomInitVector';
        $cipherText =  OPENSSL_RAW_DATA;
        $encrypt = openssl_encrypt($tokenPayload, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);

        return base64_encode($encrypt);
    }

    public function callAPI($url, $token, $pdata, $method='GET'){
        $headers = [
            'Content-type: application/json',
            'token: ' . $token,
            'userCode: ' . 'PS711'
        ];

        if ($method == 'GET') {
            $query = http_build_query($pdata);
            $url = $url . '?' . $query;
        }

        $curl = curl_init();            
        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_URL => $url
        ));

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $pdata);
        }
    
        $res = curl_exec($curl);    
        curl_close($curl);

        return $res;
    }
       
    public function login(Request $request)
    {                   
        $response = new Response();
        
        $isPost = $request->isMethod('POST');
        if ($isPost) { 
            $data =  $request->getContent();
            $data = json_decode($data);        
            $pdata = array('userCode' => $data->userCode, 'locale' => 'en');                        
            $this->logger->info('[login] $data:', [$pdata]);

            $url = 'https://paapistg.oreo88.com/b2b/player/login';
            $token = $this->generateToken();        
            $this->logger->info('[login] $token:', [$token]);

            $res = $this->callApi($url, $token, $pdata, 'GET');
            $this->logger->info('[login] pinnacle response:', [$res]);

            $response->setContent(json_encode($res));
        }

        return $response;
    }

    public function logout(Request $request)
    {   
        $response = new Response();
        
        $isPost = $request->isMethod('POST');
        if ($isPost) { 
            $data =  $request->getContent();
            $data = json_decode($data);

            $pdata = array('userCode' => $data->userCode);
            $url = 'https://paapistg.oreo88.com/b2b/player/logout';
            $token = $this->generateToken();        
            $res = $this->callApi($url, $token, $pdata, 'GET');

            $response->setContent(json_encode($res));
        }

        return $response;
    }

    public function deposit(Request $request)
    {   
        $response = new Response();
        
        $isPost = $request->isMethod('POST');
        if ($isPost) { 
            $data =  $request->getContent();
            $data = json_decode($data);

            $pdata = array('userCode' => $data->userCode, 'amount' => $data->amount);
            $url = 'https://paapistg.oreo88.com/b2b/player/deposit';
            $token = $this->generateToken();        
            $res = $this->callApi($url, $token, $pdata, 'GET');

            $response->setContent(json_encode($res));
        }

        return $response;
    }

    public function withdraw(Request $request)
    {   
        $response = new Response();
        
        $isPost = $request->isMethod('POST');
        if ($isPost) { 
            $data =  $request->getContent();
            $data = json_decode($data);

            $pdata = array('userCode' => $data->userCode, 'amount' => $data->amount);
            $url = 'https://paapistg.oreo88.com/b2b/player/withdraw';
            $token = $this->generateToken();        
            $res = $this->callApi($url, $token, $pdata, 'GET');

            $response->setContent(json_encode($res));
        }

        return $response;
    }

    public function users(Request $request)
    {   
        $isPost = $request->isMethod('POST');
        if ($isPost) { 
            $res = $this->create_user();
            return new Response($res);   
        }

        return new Response(json_encode(array('status'=>200, 'data'=> null)));
    }

    public function getUserBalance(Request $request)
    {           
        $response = new Response();
        $isPost = $request->isMethod('POST');
        if ($isPost) {       
            $data =  $request->getContent();
            $data = json_decode($data);

            $pdata = array('userCode' => $data->userCode);
            $url = 'https://paapistg.oreo88.com/b2b/player/info';
            $token = $this->generateToken();        
            $res = $this->callApi($url, $token, $pdata, 'GET');

            $response->setContent(json_encode($res));
                        
            return $response;
        }
        
        return new Response(null);
    }

    private function create_user()
    {                   
        $pdata = array('agentCode' => 'PS711');
        $url = 'https://paapistg.oreo88.com/b2b/player/create';
        $token = $this->generateToken();        
        $res = $this->callApi($url, $token, $pdata, 'POST');

        return $res;
    }
}