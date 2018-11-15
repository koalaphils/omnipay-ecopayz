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
        $dev = array();
        $dev['post_data'] = $post;

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        
        $data['pdata'] = $post;        
        // get customer via email or phone
        // later..        
       
        $phone_code = app('db')->select($sql);
        $phone_code = (array) $phone_code[0];
        $phone_code = $phone_code['country_phone_code'];

        $url = 'http://47.254.197.223:9002/en/api/me/transactions/deposit';        
        $data_bo = array(
            'transaction' => array(
                'amount' => $post['amount'],
                'paymentOptionType' => $post['paymentOptionType'],
                'email' => $post['email'],
                'paymentOption' => 30046,
                'customer' => 20137,
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
                
        $data['type_of_res_bo'] = gettype($res_bo);
        $data['data_bo'] = $res_bo;                
        
        return response()->json(['status' => 200, 'data' => $data, 'dev' => $dev], 201); 
    }   
}
