<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    /**
     * transaction deposit
     *
     * @param Request $request
     * @return Array
     */
    public function deposit(Request $request)
    {                
        $post = $request->all();        
        $data = array();        
        

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];    
        
        // $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id join country ct on ct.country_id = c.customer_country_id where u.user_phone_number = \''.$post['phoneNumber'].'\' and ct.country_phone_code = ' . '\''.$post['phoneCode'].'\'';
        if (array_key_exists('phoneNumber', $post)) {
             $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id join country ct on ct.country_id = c.customer_country_id where u.user_phone_number = \''.$post['phoneNumber'].'\' and ct.country_phone_code = ' . $post['phoneCode'];
         }else{            
             $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id where u.user_email = \''.$post['email'].'\'   ';
        }

                    
        $customer = app('db')->select($sql);
        $customer = (array) $customer[0];
        $customer_id = $customer['customer_id'];
                         
        $url = env('API_PIWI_BO_DEPOSIT');
        $data_bo = array(
            'transaction' => array(
                'amount' => $post['amount'],
                'paymentOptionType' => $post['paymentOptionType'],
                'email' => $post['email'],                
                'customer' => $customer_id,
                'children' => array(
                    'subTransactions' => array (
                        array(
                            'amount' => 0,
                            'type' => 1
                        ),
                        'customer' => '',
                        'email' => '',
                    )
                )                
            )
        );

        $res_bo = $this->callApiBo($url,  json_encode($data_bo), 'POST', $headers); 
        $res_bo = json_decode($res_bo);
         
        // $data['post'] = $post;
        // $data['res_bo_data_type'] = gettype($res_bo);
        $data['data_bo'] = $res_bo;   
        return response()->json(['status' => 200, 'data' => $data], 201); 
    }  

    /**
     * transaction withdraw
     *
     * @param Request $request
     * @return Array
     */
    public function withdraw(Request $request)
    {                
        $post = $request->all();
        $data = array();        
        
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];    
        
        // $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id join country ct on ct.country_id = c.customer_country_id where u.user_phone_number = \''.$post['phoneNumber'].'\' and ct.country_phone_code = ' . '\''.$post['phoneCode'].'\'';
        if (array_key_exists('phoneNumber', $post)) {
             $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id join country ct on ct.country_id = c.customer_country_id where u.user_phone_number = \''.$post['phoneNumber'].'\' and ct.country_phone_code = ' . $post['phoneCode'];
         }else{            
             $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id where u.user_email = \''.$post['email'].'\'   ';
        }

                    
        $customer = app('db')->select($sql);
        $customer = (array) $customer[0];
        $customer_id = $customer['customer_id'];
                         
        $url = env('API_PIWI_BO_WITHDRAW');        
        $data_bo = array(
            'transaction' => array(
                'amount' => $post['amount'],
                'phoneCode' => $post['phoneCode'],
                'phoneNumber' => $post['phoneNumber'],
                'smsCode' => $post['smsCode'],
                'paymentOptionType' => $post['paymentOptionType'],
                'paymentOption' => $post['paymentOptions'],
                'email' => $post['email'], 
                'signupType' => $post['signupType'], 
                'customer' => $customer_id,
                'children' => array(
                    'subTransactions' => array (
                        array(
                            'amount' => 0,
                            'type' => 2
                        ),
                        'customer' => '',
                        'email' => '',
                    )
                )                
            )
        );

        $res_bo = $this->callApiBo($url, json_encode($data_bo), 'POST', $headers); 
        $res_bo = json_decode($res_bo);
                 
        $data['data_bo'] = $res_bo;
        // $data['pdata'] = $post;
        // return response()->json(['status' => 5000, 'data' => $post], 201);          
        return response()->json(['status' => 200, 'data' => $data], 201); 
    }   
}
