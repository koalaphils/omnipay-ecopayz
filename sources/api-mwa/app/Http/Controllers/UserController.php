<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use App\Repositories\UserRepository;
use App\Repositories\TransactionRepository;
use App\Library\Api\ApiZendesk;
use PHPHtmlParser\Dom;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $repoUser;
    private $repoTransaction;
    
    public function __construct(UserRepository $repoUser, TransactionRepository $repoTransaction)
    {
        $this->repoUser = $repoUser;
        $this->repoTransaction = $repoTransaction;
    }

    
    /**
     * Auto login
     *
     * @param Request $request
     * @return response
     */
    public function refresh(Request $request)
    {
        $error = true;
        $message = '';
        
        $post = $request->all();
        $res_data = array();        
        $res_data['post_data'] = $post;
      
        $data_login = array('userCode' => $post['userCode']);    
        $url = $this->base_url_pinnacle . '/login';

        $res_pin_login = $this->callApi($url, json_encode($data_login), 'POST');        
        $res_pin_login = json_decode($res_pin_login, true);
        $res_data['res_pin_login'] = $res_pin_login;
                
        $error = false;        
        return response()->json(['error' => $error, 'message'=> $message, 'status' => 200, 'data' => $res_data], 201);        
    }
    
    /**
     * Request login pinnacle
     *
     * @param Request $request
     * @return response
     */
    public function login(Request $request)
    {
        $error = [false, ''];            
        $post = $request->all();
        $data = array();
        
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        $url = $this->base_url_piwi_bo . '/customer/credentials/check-if-exists';
        // phone
        if ($post['loginType'] == 0) {
            $post['countryPhoneCode'] = $post['nationCode'];            
        }
        
        $res_bo = $this->callApiBo($url, json_encode($post), 'POST', $headers);         
        $res_bo = json_decode($res_bo);
        
        // catching error
        if (array_key_exists('error', $res_bo)) {
            if ($res_bo->error == 'true') {
                $error[0] = true;
                $error[1] = $res_bo->message; 

                return response()->json(['error' => $error[0], 'message'=> $error[1], 'status' => 200, 'data' => null], 201);           
            }            
        }

        // $data['res_bo'] = $res_bo;                
        $res_bo_data = $res_bo->data;        
        if ($res_bo->error == 'false') {            
            $data_login = array('userCode' => $res_bo->data->userCode);
            $error = false;
            $message = $res_bo->message;
        
            $url = $this->base_url_pinnacle . '/login';
            $res_pin_login = $this->callApi($url, json_encode($data_login), 'POST');        
            $res_pin_login = json_decode($res_pin_login);
            $res_pin = json_decode($res_pin_login);  

            // validate pin response
            $obj_type = gettype($res_pin);
            if ($obj_type != 'object') {
                return response()->json(['error' => true, 'message'=> 'Logging failure', 'status' => 200, 'data' => null], 200);
            }
            
            $res_login_url = 'https://' . $res_pin->loginUrl;

            $data['session'] = array();
            $data['session']['fullName'] = $res_bo_data->fullName;
            $data['session']['availableBalance'] = $res_bo_data->balance;
            $data['session']['joinedAt'] = '';            
            $data['session']['verified'] = $res_bo_data->isVerified;
            $data['session']['configs'] = $res_bo_data->configs;
            $data['session']['cid'] = $res_bo_data->customerId;
            $data['session']['paymentOptions'] = $res_bo_data->paymentOptions;
            $data['session']['paymentProcessed'] = $this->getPaymentProcessed($res_bo_data->customerId);

            $data['session']['loginType'] = $post['loginType'];
            $data['session']['userLog'] = $res_pin->loginId;            
            $data['session']['userCode'] = $res_pin->userCode;
            $data['session']['url'] = $res_login_url;

            // phone
            if ($post['loginType'] == 0) {
                $data['session']['phoneCode'] = $post['countryPhoneCode'];
                $data['session']['phoneNumber'] = $post['phoneNumber'];   
            } else {
                $data['session']['email'] = $post['email'];
            }

            return response()->json(['error' => false, 'message'=> '', 'status' => 200, 'data' => $data], 201);

        }

        return response()->json(['error' => true, 'message'=> $res_bo->message, 'status' => 200, 'data' => null], 201);
    }
    
    /**
     * Request logout pinnacle
     *
     * @param Request $request
     * @return response
     */
    public function logout(Request $request)
    {
        $post = $request->all();
        $data = array("userCode" => $post['userCode']);
        $url = $this->base_url_pinnacle . '/logout';
        $json = $this->callApi($url, json_encode($data), 'POST');        
        $res = json_decode($json);
        
        return response()->json(['error' => false, 'message'=> '', 'status' => 200, 'data' => $res], 200);
    }

    /**
     * validate for ApiBo
     *
     * @param Array $data
     * @return Array
     */
    private function validateApiBo($data){
        $res = array('error' => false, 'error_message' => '');        
        if (array_key_exists('children', $data)) {
            foreach ($data->children as $key => $value){               
                if (array_key_exists('errors', $value)) {
                    // $res[$key] = $value->errors;
                    $res['error_message'] = $value->errors;
                    $res['error'] = true;
                }
            }
        }

        return $res;
    }

    /**
     * check exitst account on BO
     *
     * @param Array $data
     * @return Array
     */
    private function checkApiBoExistsAcount($data)
    {     
        $res = array('error' => false, 'error_message' => '');        

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];        
        $url = $this->base_url_piwi_bo . '/customer/email-phone/check-if-exists';
        
        
        if (array_key_exists('phoneNumber', $data)) {
            $data['phoneNumber'] = trim($data['phoneNumber']);
            $data['countryPhoneCode'] = $data['nationCode'];
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = trim($data['email']);
        }

        $res_bo = $this->callApiBo($url, json_encode($data), 'POST', $headers); 
        $res_bo = json_decode($res_bo);

        $res['error'] = $res_bo->exist;      
        $res['error_message'] =  $res_bo->message;

        return $res;
    }

    /**
     * create customer
     *
     * @param Request $request
     * @return Array
     */
    public function create(Request $request)
    {        
        $post = $request->all();
        $data = array();

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        
        // $data['pdata'] = $post;
        $validate = $this->checkApiBoExistsAcount($post);
        
        if ($validate['error'] == 'true'){            
            return response()->json(['error' => $validate['error'], 'message'=> $validate['error_message'], 'status' => 200, 'data' => $data], 201);            
            exit();
        }
        
                 
        $url = $this->base_url_pinnacle . '/users';
        $res_pin = $this->callApi($url, array(), 'POST');        
        $res_pin = json_decode($res_pin, true);

                
        $url = $this->base_url_pinnacle . '/login';
        $data_login = array('userCode'=> $res_pin['userCode']);
        $res_pin_login = $this->callApi($url, json_encode($data_login), 'POST');        
        $res_pin_login = json_decode($res_pin_login);
        
        $data_type = gettype($res_pin_login);
        if ($data_type == 'string'){
            $res_pin_login = json_decode($res_pin_login);    
        }
        $res_login_url = 'https://' . $res_pin_login->loginUrl; 
        
        
        $url = $this->base_url_piwi_bo . '/customers/register';
        
        // via phone
        if ($post['signupType'] == 0) {
            $post['email'] = ltrim($post['nationCode'], "+") . ltrim($post['phoneNumber'], 0) . '@' . env("SUFFIX_EMAIL");
            // $post['email'] = '';
        } else {
            // via email
            $post['phoneNumber'] = '00' . rand(10000000,99999999);
            $post['nationCode'] = !empty($post['nationCode']) ? $post['nationCode'] : "+63";      
        }

        $data_backoffice = array(
            'register' => array(
                'countryPhoneCode' => $post['nationCode'],
                'email' => $post['email'],
                'phoneNumber' => $post['phoneNumber'],
                'password' => $post['password'],
                'pinLoginId' => $res_pin['loginId'],
                'pinUserCode' => $res_pin['userCode'],
                'smsCode' => $post['smsCode'],
                'signupType' => $post['signupType']
            )
        );
        
        $res_bo = $this->callApiBo($url,  json_encode($data_backoffice), 'POST', $headers); 
        $res_bo = json_decode($res_bo);
        
        // validate sms code
        if (array_key_exists('status', $res_bo)) {
            if ($res_bo->status == 400) {
               return response()->json(['error' => true, 'message'=> $res_bo->message, 'status' => 400], 201); 
            }   
        }                         
        
        // info                
        $validate = $this->validateApiBo($res_bo);        
        if ($validate['error'] == true) {
            $data['session'] = null;
        }else{
            $data['session'] = [
                'fullName' => '', 
                'availableBalance' => '', 
                'joinedAt' => '',
                'signupType' => $post['signupType'],
                'email' => $post['email'],
                'phoneCode' => $post['nationCode'],
                'phoneNumber' => $post['phoneNumber'],
                'userCode' => $res_pin['userCode'],
                'userLog'=>$res_pin['loginId'],
                'url'=> $res_login_url,
                'cid' => $res_bo->id,
                'paymentProcessed' => $this->getPaymentProcessed($res_bo->id)
            ];    
        }                    
        
        return response()->json(['error' => $validate['error'], 'message'=> $validate['error_message'], 'status' => 200, 'data' => $data, $res_bo], 201); 
    }
    
    /**
     * forgot password
     *
     * @param Request $request
     * @return Array
     */
    public function forgotPassword(Request $request)
    {        
        $post = $request->all();
        $data = [];
        $data_bo = [];
        $validate = ['error'=>'', 'error_message'=>''];
        $api_forgot_password = $this->base_url_piwi_bo . '/customer/forgot-password';

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
                   
        $validate = $this->checkApiBoExistsAcount($post);           
        if ($validate['error'] == 'false'){
            if ($post['signupType'] == 0) {
                $error_message = 'Country code and Phone number does not match';
            } else {
                $error_message = 'Your email is incorrect';
            }            

            return response()->json(['error' => true, 'message'=> $error_message, 'status' => 200, 'data' => null], 201);            
        }
    
        $data_bo = $post;
        $data_bo['viaType'] = $post['signupType'];

        // via email                 
        if (!array_key_exists('phoneNumber', $post)) {
            $data_bo['phoneNumber'] = '';
            $data_bo['phoneCode'] = '';
        } else {
            $data_bo['phoneCode'] = $post['nationCode'];
        }            

        $res_bo = $this->callApiBo($api_forgot_password,  json_encode($data_bo), 'POST', $headers);        
        $res_bo = json_decode($res_bo);
        
        // validate sms code
        if (array_key_exists('error', $res_bo)) {
            if ($res_bo->error == true) {
               return response()->json(['error' => true, 'message'=> $res_bo->message, 'status' => 200], 201); 
            }   
        }                         
                        
        return response()->json(['error' => false, 'message'=> '', 'status' => 200, 'data' => null], 201); 
    }

    /**
     * update password
     *
     * @param Request $request
     * @return Array
     */
    public function updatePassword(Request $request)
    {        
        $post = $request->all();
        $data = [];
        $data_bo = [];
        $validate = ['error'=>'', 'error_message'=>''];
        $api_update_password = $this->base_url_piwi_bo . '/customer/update-password';

        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
                          
        $data_bo = $post; 
        $data_bo['phoneCode'] = $post['nationCode'];
        $res_bo = $this->callApiBo($api_update_password,  json_encode($data_bo), 'POST', $headers);                        
        $res_bo = json_decode($res_bo);        

        // validate sms code
        if (array_key_exists('error', $res_bo)) {
            if ($res_bo->error == true) {                                
                return response()->json(['error' => true, 'message'=> $res_bo->message, 'status' => 200], 201); 
            }   
        }                         
                        
        return response()->json(['error' => false, 'message'=> '', 'status' => 200, 'data' => null], 201); 
    } 
    
    public function getConfig(){
        $rows = app("db")->table("setting")
                ->whereIn("setting_code", array("piwi247.session", "transaction.validate", "bitcoin.setting"))
                ->get();
        $data = array();
        foreach($rows as $row){
            $data[$row->setting_code] = json_decode($row->setting_value);
        }
        return response()->json(['error' => false, 'message'=> '', 'status' => 200, 'data' => $data], 200); 
    }
    
    public function getTokenZendesk(Request $request) {
        $key = env("ZENDESK_SHARED_SECRET"); // {my zendesk shared key
        $now = time();
        $params = $request->all();
        $cid = $params['cid'];
        $user = $this->repoUser->getByCustomerID($cid);
        if(!$user){
            echo ""; 
            exit;
        }
        $token = array(
            "jti" => md5($now . rand()),
            "iat" => $now,
            "name" => $user->user_email,
            "email" => $user->user_email,
            "external_id" => "$user->customer_id"
        );
//        var_dump($token);
        $jwt = JWT::encode($token, $key);
        
        return response()->json(['jwt' => $jwt, 'email' => $user->user_email], 200);
    }
    
    public function getListTickets(Request $request){
        $list = array();
        $cid = $request->get('cid');
        $user = $this->repoUser->getByCustomerID($cid);
        $user = ApiZendesk::getUser($user->user_email);
        
        $count = array("open" => 0, "pending" => 0, "solved" => 0, "closed" => 0);
        if($user){
            $tickets = ApiZendesk::getTickets($user->id);
            foreach($tickets as $ticket){
                $item = new \stdClass();
                $item->chat_id = $this->getChatIDByDescription($ticket->description);
                $item->ticket_id = $ticket->id;
                $item->requester_id = $ticket->requester_id;
                $item->created_at = date("d/m/Y", strtotime($ticket->created_at));
                $item->status = $ticket->status;
                if(isset($count[$item->status])){
                    $count[$item->status]++;
                } 
                $list[] = $item;
            }
        }
        
        return response()->json(['tickets' => $list, 'count' => $count], 200);
    }
    
    public function getListTicketComment(Request $request){
        $list = array();
        $ticket_id = $request->get('ticket_id');
        $chat_id = $request->get('chat_id');
        
        if($chat_id == "0"){
            $comments = ApiZendesk::getTicketComments($ticket_id);
            if($comments){
                $first = $comments[0];
                $name = isset($first->via->source->to->address) ? $first->via->source->to->address : "";
            }
            foreach($comments as $i => $comment){
                $item = new \stdClass();
                $item->name = $name;
                $item->timestamp = date("H:i A", strtotime($comment->created_at));
                $item->is_visitor = $i == 0 ? 1 : 0;
                $item->msg = $comment->plain_body;
                $list[] = $item;
            }
        }
        else{
            $chat = ApiZendesk::getChat($chat_id);
            foreach($chat->history as $history){
                if($history->type == "chat.msg"){
                    $item = new \stdClass();
                    $item->name = $history->name;
                    $item->timestamp = date("H:i A", strtotime($history->timestamp));
                    $item->is_visitor = $history->sender_type == "Visitor" ? 1 : 0;
                    $item->msg = $history->msg;
                    $list[] = $item;
                }
            }
        }
        
        return response()->json(['comments' => $list], 200);
    }
    
    public function getAuthorizationCode(Request $request){
        echo "<pre>";
        print_r($request->all());
        exit;
    }
    
    public function getListPaymentProcessed(Request $request){
        $cid = $request->get("cid");
        $payments = $this->getPaymentProcessed($cid);
        
        return response()->json(['payments' => $payments], 200);
    }
    
    private function parseHTMLToListComment($html_body){
        $comments = array();
        $html_body = $html_body;
        $html_body = str_replace("<br>", "------", $html_body);
        $html_body = preg_replace("/<a\s(.+?)>(.+?)<\/a>/is", "%CUSTOMER%", $html_body);

        $dom = new Dom();
        $dom->load($html_body);
        $p = $dom->find(".zd-comment p");
        if(isset($p[1])){
            $html = $p[1]->text;
            $html = explode("------", $html);

            foreach($html as $text){
                if(strpos($text, "AM) ") || strpos($text, "PM) ")){
                    if(substr_count($text, "***") == 2){
                        continue;
                    }
                    $msg = substr($text, 13);
                    $pos = strpos($msg, ":");
                    $created_at = date("h:i A",strtotime(substr($text, 1, 11)));
                    
                    $comment = new \stdClass();
                    $comment->created_at = $created_at;
                    $comment->is_customer = strpos($text, "%CUSTOMER%") !== false ? 1 : 0;
                    $comment->name = substr($msg, 0, $pos);
                    $comment->message = substr($msg, $pos + 1);
                    
                    $comments[] = $comment;
                }
            }
        }
        
        return $comments;
    }
    
    private function getChatIDByDescription($description) {
        $chat_id = "0";
        $desc_list = explode("\n", $description);
        foreach ($desc_list as $desc) {
            if (strpos($desc, "Chat ID") !== false){
                $list = explode(" ", $desc);
                $chat_id = end($list);
                break;
            }
        }
        return $chat_id;
    }
    
    private function getServedByInDescription($description) {
        $served_by = "";
        $desc_list = explode("\n", $description);
        foreach ($desc_list as $desc) {
            if (starts_with($desc, "Served by")) {
                $served_by = substr($desc, 11);
                break;
            }
        }
        return $served_by;
    }
    
    private function getPaymentProcessed($cid){
        // payment processed 
        $items = $this->repoTransaction->getListPaymentProcessed($cid);
        $payments = [];
        foreach($items as $item){
            $payments[$item->transaction_payment_option_type] = true;
        }
        return $payments;
    }

}

