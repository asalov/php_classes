<?php

class Session{
	private $timeLimit;
	private $timestampSuffix = '-timestamp';
	
	public function __construct($timeLimit = 900){
		if(session_status() !== PHP_SESSION_ACTIVE) session_start();

		$this->timeLimit = $timeLimit;
	}

	public function exists($name){
		return isset($_SESSION[$name]);
	}

	public function get($name){
		return ($this->exists($name)) ? $_SESSION[$name] : null;
	}

	public function set($name, $value, $timestamp = false){
		if($timestamp) $this->setTimestamp($name);

		return $_SESSION[$name] = $value;
	}

	public function delete($name){
		if($this->exists($name)){
			unset($_SESSION[$name]);
			unset($_SESSION[$name . $this->timestampSuffix]);
		}
	}

	public function regenerate($deleteOld = true){
		session_regenerate_id($deleteOld);
	}

	public function destroy(){
		session_destroy();
	}

	// Check if a session has exceeded its time limit
	public function timeout($name, $renew = true){
		$timestamp = $this->getTimestamp($name);

		if($timestamp){
			if($this->exists($name) && time() - $timestamp > $this->timeLimit){
				$this->delete($name);
				return true;
			}

			if($renew) $this->setTimestamp($name);
		}

		return false;
	}

	public function timeLimit(){
		return $this->timeLimit;
	}
	
	private function getTimestamp($name){
		return $this->get($name . $this->timestampSuffix);
	}

	private function setTimestamp($name){
		return $_SESSION[$name . $this->timestampSuffix] = time();
	}

	// Flash a message only once
	public function flash($name, $string = ''){
		if($this->exists($name)){
			$session = $this->get($name);
			$this->delete($name);
			return $session;
		}else{
			$this->set($name, $string);
		}
	}
	
	public function generateToken($tokenName, Hash $hash, $timestamp = false){
		return $this->set($tokenName, $hash->unique(), $timestamp);
	}
	
	public function checkToken($token, $tokenName){
		if($this->exists($tokenName) && $token === $this->get($tokenName) && !$this->timeout($tokenName, false)){
			$this->delete($tokenName);
			return true;
		}

		return false;
	}
}