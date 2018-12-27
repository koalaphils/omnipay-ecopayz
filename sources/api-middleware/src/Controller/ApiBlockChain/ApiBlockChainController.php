<?php

namespace App\Controller\ApiBlockChain;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

// call api
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

class ApiBlockChainController extends DefaultController
{    
    /**
     * @var object
     */
    private $client;

    /**
     * @var string
     */
    const BLOCKCHAIN_DOMAIN = 'https://www.blockchain.com/api';

    /**
     * @var string
     */
    const BLOCKCHAIN_INFO_DOMAIN = 'https://api.blockchain.info';
        
    /**
     * @var array
     */
    const BLOCKCHAIN_APIS = [
        'exchangeRates' => '/ticker',        
        'generateBitcoinAddress' => '/v2/receive',
        'balanceUpdate' => '/v2/receive/balance_update',
        'convertToBitcoin' => '/v2/tobtc',
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct () {                
        // $this->client = new CLient([
        //     'base_uri' => 'http://localhost:9000/api/{version}',
        //     'version' => 'v2',
        //     'headers' => ['Content-type: application/json'],
        //     'auth' => ['username', 'password', 'Basic']
        // ]);  
    }

    /**
     * Call blockchain api
     *
     * @param array  $data
     * @param string $method GET, POST
     *
     * @return json
     *
     * @throws \RuntimeException
     */
    public function callAPI($url, $pdata, $method='GET'){
        $headers = [
            'Content-type: application/json'            
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

    /**
     * get exchange rates list
     *
     * @param Request  $request     
     *
     * @return json
     *
     * @throws \RuntimeException
     */
    public function exchangeRates(Request $request)
    {   
        $response = new Response();                                
        $url = self::BLOCKCHAIN_INFO_DOMAIN . self::BLOCKCHAIN_APIS['exchangeRates'];        
        $res = $this->callAPI($url, [], 'GET');
        
        return new JsonResponse(['error' => false, 'message' => null, 'status' => 200, 'data' => $res]);        
    }

    /**
     * Generate Bitcoin Address
     *
     * @param Request  $request     
     *
     * @return json
     *
     * @throws \RuntimeException
     */
    public function generateBitcoinAddress(Request $request)
    {   
        $response = new Response();                
        $isPost = $request->isMethod('POST');
        $data = $request->request->all();
        
        return new JsonResponse([200]);
    }

    /**
     * Balance update
     *
     * @param Request  $request     
     *
     * @return json
     *
     * @throws \RuntimeException
     */
    public function balanceUpdate(Request $request)
    {   
        $response = new Response();                
        $isPost = $request->isMethod('POST');
        $data = $request->request->all();
        
        return new JsonResponse([200]);
    }

    /**
     * Currency Conversion
     *
     * @param Request  $request     
     *
     * @return json
     *
     * @throws \RuntimeException
     */
    public function convertToBitcoin(Request $request)
    {   
        $response = new Response();                
        $isPost = $request->isMethod('POST');
        $data = $request->request->all();
        
        return new JsonResponse([200]);
    }    
}
