<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $base_url_pinnacle = 'http://47.254.197.223:9000/api/pinnacle';
    public $base_url_blockchain = 'http://47.254.197.223:9000/api/blockchain';
    public $base_url_piwi_bo = 'http://internal.pinny88.com/en/api';
    public $base_url_fresh_desk = 'https://piwi247.freshdesk.com/api/v2';

    public function callApi($url, $pdata=null, $method='GET')
    {
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

	public function callApiBo($url, $pdata=null, $method='GET', $headers)
	{
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

	public function callApiSms($url, $pdata=null, $method='GET', $username)
	{
		$headers = [
			'Authorization: Basic ' . $username
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
		    CURLOPT_URL => $url,
		    CURLOPT_USERNAME => $username
		));

		if ($method == 'POST') {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $pdata);
		}
	
		$res = curl_exec($curl);	
		curl_close($curl);

		return $res;
    }

}