<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Controller\TokenAuthenticatedController;

class DefaultController extends Controller implements TokenAuthenticatedController {
	private $agentCode;
    private $agentKey;
    private $secretKey;

	public function __construct ($agentCode='PS711', $agentKey='035c48be-9959-4556-80eb-9a0224814c88', $secretKey='P6Aae0ej2dwnJe9B'){
		$this->agentCode = $agentCode;
		$this->agentKey = $agentKey;
		$this->secretKey = $secretKey;
	}

	public function index(){
		$index = "<!DOCTYPE html>
<html>
<head>
    <title>index</title>
</head>
<body>
    <pre id='taag_output_text' style='float:left;' class='fig' contenteditable='true'>                                                                                
               ,-.----.                             ____                        
   ,---,       \    /  \     ,---,                ,'  , `.   ,---,    ,---,     
  '  .' \      |   :    \ ,`--.' |             ,-+-,.' _ |,`--.' |  .'  .' `\   
 /  ;    '.    |   |  .\ :|   :  :          ,-+-. ;   , |||   :  :,---.'     \  
:  :       \   .   :  |: |:   |  '         ,--.'|'   |  ;|:   |  '|   |  .`\  | 
:  |   /\   \  |   |   \ :|   :  |        |   |  ,', |  ':|   :  |:   : |  '  | 
|  :  ' ;.   : |   : .   /'   '  ;        |   | /  | |  ||'   '  ;|   ' '  ;  : 
|  |  ;/  \   \;   | |`-' |   |  |        '   | :  | :  |,|   |  |'   | ;  .  | 
'  :  | \  \ ,'|   | ;    '   :  ;        ;   . |  ; |--' '   :  ;|   | :  |  ' 
|  |  '  '--'  :   ' |    |   |  '        |   : |  | ,    |   |  ''   : | /  ;  
|  :  :        :   : :    '   :  |        |   : '  |/     '   :  ||   | '` ,/   
|  | ,'        |   | :    ;   |.'         ;   | |`-'      ;   |.' ;   :  .'     
`--''          `---'.|    '---'           |   ;/          '---'   |   ,.'       
                 `---`                    '---'                   '---'         
                                                                                </pre></body>
</html>";


              return new Response($index);

       }
	
        public function generateToken_(){
		$timestamp = time() * 1000;
		$hashToken = md5($this->agentCode.$timestamp.$this->agentKey);
		$tokenPayload = $this->agentCode . '|' . $timestamp . '|' . $hashToken;
		$token = $this->encryptAES($tokenPayload);

		return $token;
	}	

	private function encryptAES_($tokenPayload){
		$iv = 'RandomInitVector';
		$cipherText =  OPENSSL_RAW_DATA;
		$encrypt = openssl_encrypt($tokenPayload, 'AES-128-CBC', $this->secretKey, OPENSSL_RAW_DATA, $iv);

		return base64_encode($encrypt);
	}

	public function callAPI_($url, $token, $pdata, $method='GET'){
			$headers = [
				'Content-type: application/json',
			    'token: ' . $token,
			    'userCode: ' . $this->agentCode
			];

			
			if ($method == 'GET') {
				$query = http_build_query($pdata);
				$url = $url . '?' . $query;
			}

			$curl = curl_init();			
			curl_setopt_array($curl, array(
				CURLOPT_HTTPHEADER => $headers,
			    CURLOPT_RETURNTRANSFER => 0,
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
}