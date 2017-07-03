<?php

class FileLogger{
	private $logFile;
	private $dateFormat;

	public function __construct($file, $dateFormat = 'Y-m-d H:i:s'){		
		if(!file_exists($file)) throw new Exception('Log file could not be found at this location - ' . $file);

		// Check if date format is valid
		$date = DateTime::createFromFormat($dateFormat, $this->timestamp($dateFormat));

		if(!$date) throw new Exception('Invalid date format - ' . $dateFormat);

		$this->logFile = $file;
		$this->dateFormat = $dateFormat;
	}

	public function log($msg, $flag = 'info'){
		file_put_contents($this->logFile, $this->format($msg, $flag), FILE_APPEND);
	}

	public function error($msg){
		error_log($this->format($msg, 'error'), 3, $this->logFile);
	}

	// Format output
	private function format($msg, $flag){
		return '[' . strtoupper($flag) . '] [' . $this->timestamp($this->dateFormat) . '] ' . $msg . "\n\n";
	}

	// Get timestamp in a specified format
	private function timestamp($format, $date = 'now'){
		$time = new DateTime($date);
		
		return $time->format($format);
	}
}