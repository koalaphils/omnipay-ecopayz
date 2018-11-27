<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
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
        $error = true;
        $message = '';
        
        $post = $request->all();
        $res_data = array();
        
        $headers = [
            'Content-type: application/json',
            'Authorization: Bearer OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw'
        ];
        $url = 'http://47.254.197.223:9002/en/api/customer/credentials/check-if-exists';
        $res_data['post_data'] = $post;
        
        // zimi
        unset($res_data['post_data']['password']);

        if (array_key_exists('nationCode', $post)) {
            $post['countryPhoneCode'] = $post['nationCode'];
        }
        
        $res_bo = $this->callApiBo($url, json_encode($post), 'POST', $headers); 
        $res_bo = json_decode($res_bo);
        
        $res_data['res_bo'] = $res_bo;
        
                
        if ($res_bo->error == 'false') {
            $data_login = array('userCode' => $res_bo->data->pin_user_code);
            $error = $res_bo->error;
            $message = $res_bo->message;
        
            $url = $this->base_url_pinnacle . '/login';
            $res_pin_login = $this->callApi($url, json_encode($data_login), 'POST');        
            $res_pin_login = json_decode($res_pin_login, true);
            $res_data['res_pin_login'] = $res_pin_login;
            
            // zimi
            // string
            $resPin = json_decode($res_pin_login);
            // $res_data['user'] = gettype($resPin);
            $res_data['user'] = $resPin;

        }else{
            $error = true;
            $message = $res_bo->message;
        }                   
                
        return response()->json(['error' => $error, 'message'=> $message, 'status' => 200, 'data' => $res_data], 201);
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
        $url = 'http://47.254.197.223:9002/en/api/customer/email-phone/check-if-exists';
        
        
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
        
        $data['pdata'] = $post;
        $validate = $this->checkApiBoExistsAcount($post);
        
        if ($validate['error'] == 'true'){            
            return response()->json(['error' => $validate['error'], 'message'=> $validate['error_message'], 'status' => 200, 'data' => $data], 201);            
            exit();
        }
        
        $url = $this->base_url_pinnacle . '/users';
        $res_pin = $this->callApi($url, array(), 'POST');        
        $res_pin = json_decode($res_pin, true);

        $data['res_pin'] = $res_pin;
        $data['type_of_res_pin'] = gettype($res_pin);
        $data['res_pin_loginId'] = $res_pin['loginId'];
        $data['res_pin_userCode'] = $res_pin['userCode'];
        
        $url = $this->base_url_pinnacle . '/login';
        $data_login = array('userCode'=> $res_pin['userCode']);
        $res_pin_login = $this->callApi($url, json_encode($data_login), 'POST');        
        $res_pin_login = json_decode($res_pin_login, true);

        $data['res_pin_login'] = $res_pin_login;        
        $data['res_pin'] = $res_pin;       
        $data['type_of_res_pin'] = gettype($res_pin);
        $data['res_pin_loginId'] = $res_pin['loginId'];
        $data['res_pin_userCode'] = $res_pin['userCode'];
        
        $url = 'http://47.254.197.223:9002/en/api/customers/register';
        
        // via phone
        if ($post['signupType'] == 0) {
            $post['email'] = 'fake_email_' . $post['nationCode'] . $post['phoneNumber'] . '@gmail.com';
        } else {
            // via email
            $post['phoneNumber'] = '00' . rand(10000000,99999999);
            $post['nationCode'] = '+00';            
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
        
        $data['pdata'] = $post;        
        $data['data_bo'] = $res_bo;
        $validate = $this->validateApiBo($res_bo);
                                
        return response()->json(['error' => $validate['error'], 'message'=> $validate['error_message'], 'status' => 200, 'data' => $data], 201); 
    }   
}
