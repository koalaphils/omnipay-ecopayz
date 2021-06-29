<?php

namespace AppBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use GuzzleHttp\Psr7;

class HttpService
{
    private $apiGateway;
    private $logger;
    private $client;

    public function __construct(string $apiGateway,  Logger $logger)
    {
        $this->apiGateway = $apiGateway;
        $this->logger = $logger;
        $this->client = new Client([
            'base_uri' => $this->apiGateway,
            'timeout' => 180,
            'decode_content' => 'gzip',
            'synchronous' => true,
            'verify' => false
        ]);
    }

    public function get(string $url, $options = [])
    {
        $contents = "";
        try{
            $this->logger->info('HTTP SERVICE REQUEST: ' . 'GET ' . $url);
            $response = $this->client->request('GET', $url, $options);
            
            if($response->getStatusCode() >= 200 && $response->getStatusCode() < 300){
                $contents = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
            }

            $this->logger->info('HTTP SERVICE RESPONSE: ' . $response->getStatusCode() . ' '. print_r($contents, true));

            return $contents;
        } catch (RequestException $ex){
            if ($ex->hasResponse()) {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. Psr7\Message::toString($ex->getResponse()));
            } else {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. $ex->getTraceAsString());
            }

            return $contents;
        }
    }


    public function post(string $url, $options = [])
    {
        $contents = "";

        try{
            $this->logger->info('HTTP SERVICE REQUEST: ' . 'POST ' . $url . ' ' . print_r($options, true));
            $options['headers'] = array_merge($options['headers'], [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json, text/plain, */*'
            ]);

            $response = $this->client->request('POST', $url, $options);
            if($response->getStatusCode() >= 200 && $response->getStatusCode() < 300){
                $contents = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
            }

            $this->logger->info('HTTP SERVICE RESPONSE: ' . $response->getStatusCode() . ' '. print_r($contents, true));

            return $contents;
        } catch (RequestException $ex){
            if ($ex->hasResponse()) {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. Psr7\Message::toString($ex->getResponse()));
            } else {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. $ex->getTraceAsString());
            }

            return $contents;
        }
    }

    public function delete(string $url, $options = [])
    {
        $contents = "";

        try{
            $this->logger->info('HTTP SERVICE REQUEST: ' . 'DELETE ' . $url . ' ' . print_r($options, true));
            $options['headers'] = array_merge($options['headers'], [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json, text/plain, */*'
            ]);

            $response = $this->client->request("DELETE", $url, $options);
            if($response->getStatusCode() >= 200 && $response->getStatusCode() < 300){
                $contents = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
            }

            $this->logger->info('HTTP SERVICE RESPONSE: ' . $response->getStatusCode() . ' '. print_r($contents, true));

            return $contents;
        } catch (RequestException $ex){
            if ($ex->hasResponse()) {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. Psr7\Message::toString($ex->getResponse()));
            } else {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. $ex->getTraceAsString());
            }

            return $contents;
        }
    }

    public function put(string $url, $options = [])
    {
        $contents = "";

        try{
            $this->logger->info('HTTP SERVICE REQUEST: ' . 'PUT ' . $url . ' ' . print_r($options, true));
            $options['headers'] = array_merge($options['headers'], [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json, text/plain, */*'
            ]);

            $response = $this->client->request('PUT', $url, $options);
            if($response->getStatusCode() >= 200 && $response->getStatusCode() < 300){
                $contents = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
            }

            $this->logger->info('HTTP SERVICE RESPONSE: ' . $response->getStatusCode() . ' '. print_r($contents, true));

            return $contents;
        } catch (RequestException $ex){
            if ($ex->hasResponse()) {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. Psr7\Message::toString($ex->getResponse()));
            } else {
                $this->logger->info('HTTP SERVICE ERROR: ' . $ex->getCode() . ' '. $ex->getTraceAsString());
            }

            return $contents;
        }
    }
}
