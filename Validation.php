<?php

class Validation{
	private $errorHandler;
	private $db;
	private $rules = ['required', 'min', 'max', 'email', 'url', 'matches', 'num', 'pattern', 'unique'];
	private $messages = [
		'required' => ':field is required.',
		'min' => ':field must be longer than :rule characters.',
		'max' => ':field must be shorter than :rule characters.',
		'email' => 'Invalid email.',
		'url' => 'Invalid URL.',
		'matches' => ':field does not match.',
		'num' => ':field is not a numeric value.',
		'pattern' => ':field does not match the required format.',
		'unique' => ':field already exists.',
	];
	private $items;
	private $validated = [];
	
	public function __construct(ErrorHandler $errorHandler, DB $db = null){
		$this->errorHandler = $errorHandler;		
		$this->db = $db;
	}
	
	public function check($items, $rules){
		$this->items = $items;

		foreach($items as $item => $value){
			// Check if an input field needs to be validated
			if(in_array($item, array_keys($rules))) $this->validate($item, $value, $rules[$item]);
		}
		
		return $this;
	}
	
	private function validate($item, $value, $rules){
		foreach($rules as $rule => $cond){
			// Check if the specified rule exists
			if(in_array($rule, $this->rules)){
				$value = trim($value);

				// Call the corresponding method for each rule that has to be checked
				if(!call_user_func_array([$this, $rule], [$value, $cond, $item])){
					$this->errorHandler->add(str_replace([':field', ':rule'], 
											[$this->formatOutput($item), $cond], $this->messages[$rule]), $item);
				}else{
					$this->validated[$item] = $value;
				}
			}
		}
	}
	
	// Transform string into human readable format
	private function formatOutput($str){
		return ucfirst(str_replace('_', ' ', $str));
	}

	private function required($value){
		return $value !== '';
	}
	
	private function min($value, $rule){
		return mb_strlen($value, 'UTF-8') >= $rule;
	}
	
	private function max($value, $rule){
		return mb_strlen($value, 'UTF-8') <= $rule;	
	}
	
	private function email($value){
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}
	
	private function url($value){
		return filter_var($value, FILTER_VALIDATE_URL);
	}

	private function matches($value, $rule){
		return $value === $this->items[$rule];
	}

	private function num($value){
		return is_numeric($value);
	}

	private function pattern($value, $rule){
		return preg_match($rule, $value);
	}
	
	private function unique($value, $rule, $field){
		if($this->db !== null){
			$query = $this->db->select($rule, $field, [$field => $value]);

			return !$query->hasResults();			
		}else{
			throw new Exception('Validation check failed: Database not provided.');
		}
	}
	
	public function errors(){
		return $this->errorHandler;
	}
	
	public function success(){
		return !$this->errorHandler->hasErrors();
	}
	
	// Return validated item
	public function valid($item){
		return $this->validated[$item];
	}
}