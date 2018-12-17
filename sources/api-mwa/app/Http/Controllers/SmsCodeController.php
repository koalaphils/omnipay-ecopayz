<?php

namespace App\Http\Controllers;

use App\SmsCode;
use App\SmsProvider;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;

class SmsCodeController extends Controller
{
    public $url;
    public $auth;
    public $sourcePhoneNumber;

    /**
     * Create a new controller instance.
     * 
     * @return void
     */
    public function __construct($url='', $auth='', $sourcePhoneNumber='')
    {
        $this->url = $url;
        $this->auth = $auth;
        $this->sourcePhoneNumber = $sourcePhoneNumber;
    }

    /**
     * Send sms code to customer's phone number.
     * 
     * @param  string  $toPhoneNumber
     * @param  string  $message
     * @return json
     */
    public function sendCode($toPhoneNumber, $message='')
    {  
        // test
        $sid = 'AC7aa44b04cb254bad79d1036e65617ec4';
        $api_key_sid = 'SKc41c5e1a05eec5dc29974e58a47f36b9';
        $api_key_secret = 'mW6qXm90xm7ZoCVNty6zjMdvsQmHkolr';
        $from = '+18124616835';
        
        // live
        // $sid = 'ACa8414b7fd5f78fa371c0aeb341931c8d';
        // $api_key_sid = 'SK3f5e48d1f2663e53b60964e745bac2bf';
        // $api_key_secret = 'z9xiYh8u7AH0bepYq2XAAxcz0I9CqJHS';
        // $from = '+639179421398';

        $url = 'https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json';
        $api_key = $api_key_sid . ':' . $api_key_secret;
        $data = array(                
            'From' => $from,
            'To' => $toPhoneNumber, 
            'Body'=> $message    
        );     
        $auth = base64_encode($api_key);            
        $result = $this->callApiSms($url, $data, 'POST', $auth);
      
        return $result;
    }

    /**
     * get SmsCode
     *      
     * @param  Request  $request
     * @return json
     */
    public function getSmsCode(Request $request)
    {
        $error = false;
        $error_message = '';

        $res_data = array();        

        $rdata = $request->all();                
        $sms = new SmsCode();        
        $sms->sms_code_id = md5(strtotime(date('Ymdhis')));
        $sms->sms_code_value = rand(100000, 999999);
                            
        // via phone
        if ($rdata['signupType'] == 0) {
            $phoneNumber = $rdata['nationCode'] . ltrim($rdata['phoneNumber'], "0");
            $phoneMessage = 'Piwi verify code is: ' . $sms->sms_code_value;
            $res = $this->sendCode($phoneNumber, $phoneMessage);

            // return response()->json(['error' => true , 'status' => 400, 'data'=> $rdata, 'sms_res'=> $res], 201);

            $sms->sms_code_json = json_encode($res);
            $sms->sms_code_customer_phone_number = $phoneNumber;                                
            $sms->sms_code_provider_id = '1';
            $sms->sms_code_source_phone_number = '+18124616835';
        } else {            
            $sms->sms_code_customer_email = $rdata['email'];
            $customerEmail = $rdata['email'];
            $emailMessage = '<p>Dear user, <br /><br />You are activating your email address. <br />Code:<span style="color: #3366ff;"><strong>'.$sms->sms_code_value.'</strong></span><br />This code will expire in 30 minutes. <br />Please do not share this with anyone. <br />The email address cannot be reset once activated.<br /><br />Thank you for choosing abc.com<br />XYZ Official<br /><br />Contact Us Email:support@abc.com Phone:+xx xxxxxxxxxx</span></p>';
            
            $data = [
                'host' => 'smtp.gmail.com',
                'from_email' => 'piwizmt@gmail.com',
                'from_password' => 'piwi8888@@',
                'from_name' => 'PIWI',
                'to_email' => $customerEmail,
                'to_name' => '',
                'email_subject' => 'XYZ Verification code',
                'email_body' => $emailMessage
            ];
            
            $res = $this->sendCodeViaEmail($data);
            $sms->sms_code_json = json_encode($res);
        }
                
        $sms->sms_code_status = '2';        
        $sms->sms_code_created_at = time();
        
        try {
            $sms->save(); 
            $res_data['smsCode'] = $sms->sms_code_value;
        } catch (Exception $e) {
            $error = true; 
            $error_message = $e;            
        }
        
        return response()->json(['error' => $error, 'error_message' => $error_message , 'status' => 200, 'data'=> $res_data], 201);        
    }

    /**
     * send code via email
     *      
     * @param  Array  $info
     * @return Array
     */
    public function sendCodeViaEmail($info='') 
    {
        try {
            $res = [true, ''];

            $mail = new PHPMailer(true);            
            $mail->isSMTP();
            $mail->Host = $info['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $info['from_email'];
            $mail->Password = $info['from_password'];
            
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
                        
            $mail->setFrom($info['from_email'], $info['from_name']);
            $mail->addAddress($info['to_email'], $info['to_name']);            

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $info['email_subject'];
            $mail->Body    = $info['email_body'];
            $mail->AltBody = '';

            $res[0] = $mail->send();
            
        } catch (Exception $e) {
            $res[1] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
        
        return $res;
    }

}
