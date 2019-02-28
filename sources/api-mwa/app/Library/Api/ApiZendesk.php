<?php

namespace App\Library\Api;

use GuzzleHttp\Client;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiZendesk
 *
 * @author asus
 */
class ApiZendesk {

    private static function getClient() {
        $client = new Client(array(
            'base_uri' => "https://tieudat102.zendesk.com/api/v2/",
            'headers' => array(
                'Content-Type' => "application/json"
            ),
            'auth' => array('tieu.dat102@gmail.com', '123456a@A')
        ));
        return $client;
    }

    public static function getUser($external_id) {
        $client = self::getClient();
        $query = "type:user external_id:$external_id";
        $response = $client->get("search.json?query=$query");
        $data = json_decode($response->getBody());
        return isset($data->results[0]) ? $data->results[0] : null;
    }
    
    public static function getTickets($zendesk_user_id){
        $client = self::getClient();
        $query = "type:ticket requester_id:$zendesk_user_id";
        $response = $client->get("search.json?query=$query");
        $data = json_decode($response->getBody());
        return isset($data->results) ? $data->results : array();
    }

}
