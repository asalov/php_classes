<?php

class Hash{

	public function generate($str){
		return hash('sha256', $str);	
	}

	public function password($str){
		return password_hash($str, PASSWORD_DEFAULT);
	}

	public function verifyPass($pass, $hash){
		return password_verify($pass, $hash);
	}
	
	public function salt($length = 32){
		return mcrypt_create_iv($length);
	}
	
	public function unique(){
		return $this->generate($this->salt());
	}
}