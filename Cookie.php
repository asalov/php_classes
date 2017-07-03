<?php

class Cookie{

	public function exists($name){
		return (isset($_COOKIE[$name])) ? true : false;
	}
	
	public function get($name){
		return $_COOKIE[$name];
	}

	public function set($name, $value, $expiry, $options = []){
		$path = '/';
		$domain = '';
		$https = false;
		$httponly = false;

		if(!empty($options)){
			foreach($options as $key => $val){
				// Set the value of a each variable corresponding to the name of the array key
				$$key = $val;
			}
		}

		$_COOKIE[$name] = $value;
		return setcookie($name, $value, strtotime($expiry), $path, $domain, $https, $httponly);
	}

	// Creates a secure cookie (HTTPONLY)
	public function secure($name, $value, $expiry, $https = false, $options = []){
		$options['https'] = $https;
		$options['httponly'] = true;

		$this->set($name, $value, $expiry, $options);
	}
	
	public function delete($name, $path = '/'){
		unset($_COOKIE[$name]);
		return setcookie($name, '', -1, $path);
	}
}