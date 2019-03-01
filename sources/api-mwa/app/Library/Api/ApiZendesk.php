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
            'base_uri' => "https://piwi247help.zendesk.com/api/v2/",
            'headers' => array(
                'Content-Type' => "application/json",
                'Authorization' => "Basic YWRtaW5AcGl3aTI0Ny5jb20vdG9rZW46OElWQ1MzN0JNNzAwcW4xTkFCS3RFYkxjQUpXV3Q5S0tGbFg1VktOdg=="
            ),
//            'auth' => array('tieu.dat102@gmail.com', '123456a@A')
        ));
        return $client;
    }

    public static function getUser($email) {
        $client = self::getClient();
        $query = "type:user email:$email";
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
    
    public static function getTicketComments($ticket_id){
        $client = self::getClient();
        $response = $client->get("tickets/$ticket_id/comments.json");
        $data = json_decode($response->getBody());
        return isset($data->results) ? $data->results : array();
    }

}
