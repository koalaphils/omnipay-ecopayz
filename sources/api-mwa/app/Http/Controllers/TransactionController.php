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
        $data = [];
        $data_bo = ['transaction' => []];        

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ]; 
        
        $data_bo['transaction']['amount'] = $post['amount'];        
        $data_bo['transaction']['smsCode'] = $post['smsCode'];
        $data_bo['transaction']['paymentOptionType'] = $post['paymentOptionType'];
        $data_bo['transaction']['paymentOption'] = $post['paymentOptions'];
        $data_bo['transaction']['signupType'] = $post['signupType'];
        $data_bo['transaction']['phoneNumber'] = '';
        $data_bo['transaction']['phoneCode'] = '';
        $data_bo['transaction']['children'] = [];

        if ($post['signupType'] == 0) {
            $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id join country ct on ct.country_id = c.customer_country_id where u.user_phone_number = \''.$post['phoneNumber'].'\' and ct.country_phone_code = ' . $post['phoneCode'];
            $data_bo['transaction']['phoneCode'] = $post['phoneCode'];
            $data_bo['transaction']['phoneNumber'] = $post['phoneNumber'];
        } else {
            $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id where u.user_email = \''.$post['email'].'\'   ';
            $data_bo['transaction']['email'] = $post['email'];

        }
                
        $customer = app('db')->select($sql);
        $customer = (array) $customer[0];
        $customer_id = $customer['customer_id'];
        $data_bo['transaction']['customer'] = $customer_id;
                         
        $url = env('API_PIWI_BO_WITHDRAW');
        // $data_bo = array(
        //     'transaction' => array(
        //         'amount' => $post['amount'],
        //         'phoneCode' => $post['phoneCode'],
        //         'phoneNumber' => $post['phoneNumber'],
        //         'smsCode' => $post['smsCode'],
        //         'paymentOptionType' => $post['paymentOptionType'],
        //         'paymentOption' => $post['paymentOptions'],
        //         'email' => $post['email'], 
        //         'signupType' => $post['signupType'], 
        //         'customer' => $customer_id,
        //         'children' => array(
        //             'subTransactions' => array (
        //                 array(
        //                     'amount' => 0,
        //                     'type' => 2
        //                 ),
        //                 'customer' => '',
        //                 'email' => '',
        //             )
        //         )                
        //     )
        // );


        $res_bo = $this->callApiBo($url, json_encode($data_bo), 'POST', $headers);        
        $res_bo = json_decode($res_bo);

        $data['data_bo'] = $res_bo;        
        return response()->json(['status' => 200, 'data' => $data], 201); 
    }

    /**
     * get transaction list
     *
     * @param Request $request
     * @return Array
     */
    public function getTransactionList(Request $request)
    {                
        $post = $request->all();        
        $data = array();
        $error = null;

        // zimi        
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        
        // refactor later
        $sql = 'select customer_id from customer c join user u on u.user_id = c.customer_user_id where c.customer_pin_user_code = \''.$post['userCode'].'\'';
        $customer = app('db')->select($sql);
        $customer = (array) $customer[0];
        $customer_id = $customer['customer_id'];
        
        $url = env('API_PIWI_BO_TRANSACTIONS');
        // $url = 'http://47.254.197.223:9002/en/api/me/transactions';
        $data_bo = ['cid' => $customer_id, 'search' => $post['search'], 'filter' => $post['filter']];
        
        $res_bo = $this->callApiBo($url,  json_encode($data_bo), 'POST', $headers); 
        $res_bo = json_decode($res_bo);        
        $data = $res_bo;        
        
        if (array_key_exists('error', $res_bo)) {
            $error = [
                'code' => $res_bo->error->code,
                'message' => $res_bo->error->message
            ];            
        }

        return response()->json(['status' => 200, 'error' => $error, 'data' => $data], 201); 
    }

    /**
     * get exchange rates
     *
     * @param Request $request
     * @return Array
     */
    public function getExchangeRates(Request $request)
    {                        
        $data = [];
        $error = null;

        // http://47.254.197.223:9000/api/blockchain/exchange-rates
        $url = $this->base_url_blockchain . '/exchange-rates';
        $res = $this->callApi($url, [], 'GET');        
        // $res = preg_replace( "/\r|\n/", "", $res);
        $res = trim($res, "\n");

        $res = json_decode($res);

        return response()->json(['status' => 200, 'error' => false, 'data' => $res->data], 201); 
    }
}
