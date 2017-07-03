<?php

class Mailer extends PHPMailer{
	private $isSent = false;
	private $domain;

	public function __construct($settings, $verbose = false){
		parent::__construct(true); // Enable exceptions

	    if($verbose) $this->SMTPDebug = 2; // Verbose error messages

	    // Initialize SMTP connection
		$this->isSMTP();
		$this->Host = $settings['host'];
		$this->SMTPAuth = true;
		$this->Username = $settings['username'];
		$this->Password = $settings['password'];
		$this->Port = $settings['port'];
		$this->SMTPSecure = $settings['protocol'];
		$this->domain = $settings['domain'];
	}

	// Compose mail
	public function compose($from, $to, $subject, $msg, $fromName = 'Me', $isHtml = false){
		$this->setSender($from, $fromName);
		$this->execute('addAddress', $to);
		
		$this->Subject = $subject;
		$this->Body = $msg;
		$this->isHTML($isHtml);

		if($isHtml) $this->AltBody = $this->html2text($msg);
	}

	// Set actual "from" address
	private function setSender($from, $name){
		$senderDomain = substr(strrchr($from, '@'), 1);

		if($senderDomain !== $this->domain){
			$this->setFrom('admin@' . $this->domain, $name);
			$this->addReplyTo($from, $name);
		}else{
			$this->setFrom($from, $name);
		}
	}

	public function cc($to){
		$this->execute('addCC', $to);
	}

	public function bcc($to){
		$this->execute('addBCC', $to);	
	}

	public function attachments($attachments){
		$this->execute('addAttachment', $attachments);
	}

	// Dynamically execute parent method
	private function execute($method, $params){
		$params = (array) $params; // Allows for passing string or array

		foreach($params as $key => $val){
			(!is_string($key) || trim($key) == '') ? $this->{$method}($val) : $this->{$method}($val, $key);
		}
	}

	public function sendMail(){
		$this->isSent = $this->send();
	}

	public function isSent(){
		return $this->isSent;
	}

	public function error(){
		return $this->ErrorInfo;
	}
}