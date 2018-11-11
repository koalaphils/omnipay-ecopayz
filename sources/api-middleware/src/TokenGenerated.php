<?php
namespace App;

class TokenGenerated
{
	private $agentCode;
    private $agentKey;
    private $secretKey;

	public function __construct ($agentCode='PS711', $agentKey='035c48be-9959-4556-80eb-9a0224814c88', $secretKey='P6Aae0ej2dwnJe9B'){
		$this->agentCode = $agentCode;
		$this->agentKey = $agentKey;
		$this->secretKey = $secretKey;
	}

	public function generateToken(){
		$timestamp = time() * 1000;
		$hashToken = md5($this->agentCode.$timestamp.$this->agentKey);
		$tokenPayload = $this->agentCode . '|' . $timestamp . '|' . $hashToken;
		$token = $this->encryptAES($tokenPayload);

		return $token;
	}	

	private function encryptAES($tokenPayload){
		$iv = 'RandomInitVector';
		$cipherText =  OPENSSL_RAW_DATA;
		$encrypt = openssl_encrypt($tokenPayload, 'AES-128-CBC', $this->secretKey, OPENSSL_RAW_DATA, $iv);

		return base64_encode($encrypt);
	}
}