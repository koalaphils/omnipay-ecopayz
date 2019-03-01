<?php

namespace App\Http\Controllers;
use Validator;
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
     * customer id
     *
     * @param Request $request
     * @return Array
     */
    private function _getCustomer($post){
        $query = app('db')->table('users')
                ->select('c.customer_id')
                ->from("customer as c")
                ->join('user as u', 'u.user_id', '=', 'c.customer_user_id');
        if (!empty($post['phoneCode']) && !empty($post['phoneNumber'])) {
            $query->join('country as ct', 'ct.country_id', '=', 'c.customer_country_id')
                    ->where('u.user_phone_number', $post['phoneNumber'])
                    ->where('ct.country_phone_code', $post['phoneCode']);
        } else {
            $query->where('u.user_email', '=', $post['email']);
        }
        $row = $query->first();
        $customer = $row ? (array) $row : array();
        
        return $customer;
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
        
        if(!$this->validate_deposit($post)){
            return response()->json(['status' => 201, 'error' => $post['error_message'], 'data' => null], 201); 
        }
        
        $data = array();
        $data_bo = ['transaction' => []];        
        
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];

        $data_bo['transaction']['amount'] = $post['amount'];                
        $data_bo['transaction']['paymentOptionType'] = $post['paymentOptionType'];        
        $data_bo['transaction']['signupType'] = $post['signupType'];
        $data_bo['transaction']['phoneNumber'] = '';
        $data_bo['transaction']['phoneCode'] = '';
        $data_bo['transaction']['children'] = [];
        $data_bo['transaction']['email'] = $post['email'];
        $data_bo['transaction']['product'] = $post['product'];

        if ($post['signupType'] == 0) {            
            $data_bo['transaction']['phoneCode'] = $post['phoneCode'];
            $data_bo['transaction']['phoneNumber'] = $post['phoneNumber'];
        }
        

        $customer = $this->_getCustomer($post);       
        $customer_id = $customer['customer_id'];
        $data_bo['transaction']['customer'] = $customer_id;

        $url = env('API_PIWI_BO_DEPOSIT');        
        $res_bo = $this->callApiBo($url,  json_encode($data_bo), 'POST', $headers); 
        $res_bo = json_decode($res_bo);
                 
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
        if(!$this->validate_withdraw($post)){
            return response()->json(['status' => 201, 'error' => $post['error_message'], 'data' => null], 201);
        }
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
            $data_bo['transaction']['phoneCode'] = $post['phoneCode'];
            $data_bo['transaction']['phoneNumber'] = $post['phoneNumber'];
        } else {            
            $data_bo['transaction']['email'] = $post['email'];
        }
                
        $customer = $this->_getCustomer($post);                
        $customer_id = $customer['customer_id'];
        $data_bo['transaction']['customer'] = $customer_id;
                         
        $url = env('API_PIWI_BO_WITHDRAW');
        $res_bo = $this->callApiBo($url, json_encode($data_bo), 'POST', $headers);        
        $res_bo = json_decode($res_bo);

        $data['data_bo'] = $res_bo;        
        return response()->json(['status' => 200, 'data' => $data], 201); 
    }


    /**
     * transaction deposit bitcoin
     *
     * @param Request $request
     * @return Array - namdo
     */
    public function getBalance(Request $request){
        $data = array();                        
        $post = $request->all();         
                
        $data_user = array('userCode' => $post['userCode']);        
        $url = $this->base_url_pinnacle . '/users/balance';
        
        $res_pin = $this->callApi($url, json_encode($data_user), 'POST');        
        $res_pin = json_decode($res_pin);

        // object
        $res_pin = json_decode($res_pin);        
        // $res_pin->availableBalance
        return response()->json(['status' => 200, 'error' => false , 'error_message' => '', 'data' => $res_pin], 201);
    }

    /**
     * transaction deposit bitcoin
     *
     * @param Request $request
     * @return Array - namdo
     */
    public function withdrawBitcoin(Request $request)
    {                        
        $data = array();                        
        $post = $request->all();  
//        if(!$this->validate_withdraw($post)){
//            return response()->json(['status' => 201, 'error' => $post['error_message'], 'data' => null], 201); 
//        }
        
        // check balance pinacle
        $data_user_amount = $post['eurAmount'];
        $data_user = array('userCode' => $post['userCode']);        
        $url = $this->base_url_pinnacle . '/users/balance';
        
        $res_pin = $this->callApi($url, json_encode($data_user), 'POST');        
        $res_pin = json_decode($res_pin);        
        
        // object
        $res_pin = json_decode($res_pin);        
        if ($data_user_amount > $res_pin->availableBalance ) {
            return response()->json(['status' => 200, 'error' => true , 'error_message' => 'Available Balance is not enought', 'data' => $res_pin], 201);
        }
                
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        $data_bo = ['transaction' => []];                    
        $data_bo['transaction']['paymentOptionType'] = 'bitcoin';
        $data_bo['transaction']['bitcoinAmount'] = $post['bitcoinAmount'];
        $data_bo['transaction']['eurAmount'] = $post['eurAmount'];
        $data_bo['transaction']['amount'] = $post['eurAmount'];
        $data_bo['transaction']['smsCode'] = $post['smsCode'];
        $data_bo['transaction']['paymentOption'] = $post['paymentOptions'];
        $data_bo['transaction']['currentRate'] = $post['currentRate'];
        $data_bo['transaction']['phoneNumber'] = '';
        $data_bo['transaction']['phoneCode'] = '';
        $data_bo['transaction']['signupType'] = $post['signupType'];        
        $data_bo['transaction']['children'] = [];   

        if ($post['signupType'] == 0) {            
            $data_bo['transaction']['phoneCode'] = $post['phoneCode'];
            $data_bo['transaction']['phoneNumber'] = $post['phoneNumber'];
        } else {            
            $data_bo['transaction']['email'] = $post['email'];
        }
        
        $customer = $this->_getCustomer($post);
        $customer_id = $customer['customer_id'];
        $data_bo['transaction']['customer'] = $customer_id;
                
        // $url = env('API_PIWI_BO_WITHDRAW');        
        $url = $this->base_url_piwi_bo . '/me/transactions/withdraw';                                
        $res_bo = $this->callApiBo($url,  json_encode($data_bo), 'POST', $headers);         
        $res_bo = json_decode($res_bo);
        
        $data['data_bo'] = $res_bo;           
        return response()->json(['status' => 200, 'error' => false, 'error_message' => '', 'data' => $data], 201); 
    } 

    /**
     * transaction deposit bitcoin
     *
     * @param Request $request
     * @return Array - namdo
     */
    public function depositBitcoin(Request $request)
    {                        
        /**"currentRate":3327.6,"bitcoinAmount":"0.0001","eurAmount":"0.33","isActive":true*/
        $post = $request->all();         
//        if(!$this->validate_deposit($post)){
//            return response()->json(['status' => 201, 'error' => $post['error_message'], 'data' => null], 201); 
//        }
        // return response()->json([300, 'TransactionController::depositBitcoin', $post], 201); 

        $data = array();                
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        $data_bo = ['transaction' => []];            

        $trans_data = $post['status']['request'];
        $data_bo['transaction']['paymentOptionType'] = 'bitcoin';
        $data_bo['transaction']['bitcoinAmount'] = $trans_data['bitcoinAmount'];
        $data_bo['transaction']['eurAmount'] = $trans_data['eurAmount'];
        $data_bo['transaction']['amount'] = $trans_data['eurAmount'];
        $data_bo['transaction']['currentRate'] = $trans_data['currentRate'];
        $data_bo['transaction']['phoneNumber'] = '';
        $data_bo['transaction']['phoneCode'] = '';
        $data_bo['transaction']['children'] = [];        
        $data_bo['transaction']['product'] = $post['product'];
        
        if ($post['signupType'] == 0) {            
            $data_bo['transaction']['phoneCode'] = $post['phoneCode'];
            $data_bo['transaction']['phoneNumber'] = $post['phoneNumber'];
        } else {            
            $data_bo['transaction']['email'] = $post['email'];
        }
        
        $customer = $this->_getCustomer($post);
        $customer_id = $customer['customer_id'];
        $data_bo['transaction']['customer'] = $customer_id;
                
        // $url = env('API_PIWI_BO_DEPOSIT');        
        $url = $this->base_url_piwi_bo . '/me/transactions/deposit';                                
        $res_bo = $this->callApiBo($url,  json_encode($data_bo), 'POST', $headers);         
        $res_bo = json_decode($res_bo);
        
        $data['data_bo'] = $res_bo;   
        // return response()->json([300, 'TransactionController::depositBitcoin', $post, $data_bo, $customer_id, $res_bo], 201); 
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
        
        // $url = env('API_PIWI_BO_TRANSACTIONS');
        $url = $this->base_url_piwi_bo . '/me/transactions';
        $data_bo = ['cid' => $customer_id, 'search' => $post['search'], 'filter' => $post['filter']];
        
        $res_bo = $this->callApiBo($url,  json_encode($data_bo), 'POST', $headers); 
        $res_bo = json_decode($res_bo);        
        $data = $res_bo;        
        
        // if (array_key_exists('error', $res_bo)) {
        //     $error = [
        //         'code' => $res_bo->error->code,
        //         'message' => $res_bo->error->message
        //     ];            
        // }

        return response()->json(['status' => 200, 'error' => $error, 'data' => $data], 201); 
    }

    
    /**
     * get ticket list
     *
     * @param Request $request
     * @return Array
     */
    public function getTicketList(Request $request)
    {                
        $data = [];
        $error = null;
        $post = $request->all();

        $headers = [
            'Content-type: application/json',
            'Authorization: Basic RzcwTk9GYW5PVEhtcGZFVWZLdWo6WA=='
        ];
    
        $url = $this->base_url_fresh_desk . '/tickets';
        $res = $this->callApiBo($url, ['email' => $post['email']], 'GET', $headers);                
        $res = json_decode($res);

        return response()->json(['status' => 200, 'error' => false, 'data' => $res], 201);
    }

    /**
     * get ticket list
     *
     * @param Request $request
     * @return Array
     */
    public function getTicketConversation(Request $request)
    {                
        $data = [];
        $post = $request->all();
        $error = null;

        $headers = [
            'Content-type: application/json',
            'Authorization: Basic RzcwTk9GYW5PVEhtcGZFVWZLdWo6WA=='
        ];
    
        $url = $this->base_url_fresh_desk . '/tickets/' . $post['id'] .'/conversations';
        $res = $this->callApiBo($url, [], 'GET', $headers);                
        $res = json_decode($res);

        return response()->json(['status' => 200, 'error' => false, 'data' => $res], 201);
    }

    public function getLockTime($updateTime){
        $interval = 20;
        $d = new \DateTime($updateTime);        
        $updatedAt = \DateTimeImmutable::createFromMutable($d);
        $expiresAt = $updatedAt->add(new \DateInterval('PT' . $interval . 'M'));
        $now = new \DateTimeImmutable();

        if ($now < $expiresAt) {
            $remainingInterval = $expiresAt->diff($now);
            $remaining = $remainingInterval->format('%H:%I:%S');

            return $remaining;
        } else {
            return '00:00:00';
        }

        return '00:00:00';
    }

    /**
     * get bitcoin transaction
     *
     * @param Request $request
     * @return Array
     */
    public function getBitcoinTransaction(Request $request)
    {                
        $post = $request->all();        
        $data = array();
        $error = null;
        
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        
        // $url = env('API_PIWI_BO_TRANSACTIONS');
        $url = $this->base_url_piwi_bo . '/me/transactions/'.$post['tid'].'/'. $post['cid'];
        $res_bo = $this->callApiBo($url, [], 'GET', $headers);                
        $res_bo = json_decode($res_bo);                
        // $trans = $res_bo->data;
        // if (array_key_exists('error', $res_bo)) {
        //     $error = [
        //         'code' => $res_bo->error->code,
        //         'message' => $res_bo->error->message
        //     ];            
        // }

        // process lock time
        // $lockTime = $this->getLockTime($trans->transaction_updated_at);
        $updateTime = $res_bo->data->transaction_updated_at;
        // $lockTime = $this->getLockTime('2019-01-31 14:20:00');
        $lockTime = $this->getLockTime($updateTime);
        return response()->json(['status' => 200, 'error' => $error, 'data' => $res_bo, $lockTime], 201); 
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

    /**
     * get bitcoin rate
     *
     * @param Request $request
     * @return Array
     */
    public function getBitcoinRate(Request $request)
    {                        
        $data = [];
        $error = null;

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];

        $url = $this->base_url_piwi_bo . '/paymentoptions/bitcoin-adjustment';        
        $res = $this->callApiBo($url, [], 'GET', $headers);              
        $res = json_decode($res);

        return response()->json(['status' => 200, 'error' => false, 'data' => $res], 201); 
    }

    /**
     * get bitcoin rate
     *
     * @param Request $request
     * @return Array
     */
    public function getBitcoinLockRate(Request $request)
    {                        
        $data = [];
        $error = null;
        $post = $request->all();        
        // return response()->json(['status' => 200, 'error' => false, $post], 201);
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];

        $customer = $this->_getCustomer($post);                
        $data_bo = ['cid' => $customer['customer_id']];

        $url = $this->base_url_piwi_bo . '/me/transactions/lock-rate-bitcoin-transaction';        
        $res = $this->callApiBo($url, json_encode($data_bo), 'POST', $headers); 
        // return response()->json([800, $post, $res], 201); 
        $res = json_decode($res);

        // check transaction status
        if ($res !== null) {
            $res = (array)$res;
            if (array_key_exists('status', $res)) {
                if ($res['status'] == 2) {
                    $res = null;
                }
            }            
        }

        return response()->json(['status' => 200, 'error' => false, 'data' => $res, $post], 201); 
    }

    public function ping(){ return response()->json([600], 201); }
    
    private function validate_deposit(&$post){
        $setting = app('db')->table('setting')->select('setting_value')->where("setting_code", "=", "transaction.validate")->first();
        $payment_type = strtolower($post['paymentOptionType']);
        $config = $setting ? json_decode($setting->setting_value) : null;
        $min = !empty($config->$payment_type->deposit->min_amount) ? $config->$payment_type->deposit->min_amount : 10;  
        $max = !empty($config->$payment_type->deposit->max_amount) ? $config->$payment_type->deposit->max_amount : 10000000; 
        $currency = $payment_type == "bitcoin" ? "BTC" : "EUR";
        $validate = [
            'amount' => "required|numeric|min:$min|max:$max"
        ];
        $messages = [
            'amount.min' => "Your requested amount is less than minimum deposit. Please deposit :min $currency or more. Thank you.",
        ];
        if($payment_type != "bitcoin"){
            $validate['email'] = "required|email";
        }
        $validator = Validator::make($post, $validate,$messages);
        foreach ($validator->errors()->all() as $message) {
            $post['error_message'] = $message;
            return false;
        }
        return true;
    }
    
    private function validate_withdraw(&$post){
        $setting = app('db')->table('setting')->select('setting_value')->where("setting_code", "=", "transaction.validate")->first();
        $payment_type = strtolower($post['paymentOptionType']);
        $config = $setting ? json_decode($setting->setting_value) : null;
        $min = !empty($config->$payment_type->withdraw->min_amount) ? $config->$payment_type->withdraw->min_amount : 10; 
        $max = !empty($config->$payment_type->withdraw->max_amount) ? $config->$payment_type->withdraw->max_amount : 10000000;  
        $currency = $payment_type == "bitcoin" ? "BTC" : "EUR";
        $validate = [
            'amount' => "required|numeric|min:$min|max:$max",
            'smsCode' => "required",
            'email' => "required"
        ];
        $messages = [
            'amount.min' => "Your requested amount is less than minimum withdraw. Please withdraw :min $currency or more. Thank you.",
        ];
        if($payment_type == "bitcoin"){
            $validate['bitcoinAddress'] = "required";
        }
        $validator = Validator::make($post, $validate,$messages);
        foreach ($validator->errors()->all() as $message) {
            $post['error_message'] = $message;
            return false;
        }
        return true;
    }
}
