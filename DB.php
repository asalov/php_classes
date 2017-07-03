<?php
class DB extends PDO{
	private $results;
	private $rows;
	private $errorHandler;
	
	// Throws an exception if parameters are incorrect
	public function __construct($host, $dbName, $user, $pass, ErrorHandler $errorHandler, $dbType = 'mysql'){ 		
		$this->errorHandler = $errorHandler;
		
		parent::__construct($dbType . ':host=' . $host . ';dbname=' . $dbName . ';charset=utf8', $user, $pass);

		// Throw an exception in case of an error
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	}

	public function query($sql, $params = [], $returnType = ''){
		$this->rows = 0;
		$this->results = [];

		try{
			$stmt = $this->prepare($sql);

			if(!empty($params)){
				foreach($params as $key => $val){
					$stmt->bindValue($key, $val, $this->getDataType(gettype($val), true));
				}	
			}

			$stmt->execute();

			// Check if the statement returns a result set and fetch it
			if($stmt->columnCount()) $this->results = $stmt->fetchAll($this->getDataType($returnType));

			$this->rows = $stmt->rowCount();	
		}catch(PDOException $e){
			$this->errorHandler->add($e->getMessage());
		}

		return $this;
	}

	public function select($table, $fields = '*', $where = [], $options = []){
		$limit = '';
		$order = '';
		$returnType = '';

		if(!empty($options)){
			foreach($options as $key => $val){
				// Set the value of a each variable corresponding to the name of the array key
				$$key = $val;
			}
		}

		$columns = '';
		if(is_array($fields)){
			foreach($fields as $field){
				$columns .= $field;

				if($field !== end($fields)) $columns .= ', ';
			}	
		}else{
			$columns = $fields;
		}

		$sql = "SELECT $columns FROM $table";

		// Add condition
		$condition = $this->buildQueryStr($where);
		if($condition !== '') $sql .= " WHERE $condition";

		// Add order
		if($order !== ''){
			$orderField = (is_array($order) && isset($order['field'])) ? $order['field'] : $order;
			$desc = (is_array($order) && isset($order['desc'])) ? 'DESC' : '';

			$sql .= " ORDER BY $orderField $desc";
		}

		// Add limit
		if($limit !== ''){
			$sql .= " LIMIT ";			
			$sql .= (is_array($limit)) ? implode(', ', $limit) : $limit;
		}
		
		return $this->query($sql, $where, $returnType);
	}

	public function insert($table, $params){
		$keys = array_keys($params);

		$fields = implode(', ', $keys);
		$values = ':' . implode(', :', $keys);

		$sql = "INSERT INTO $table($fields) VALUES($values)";

		return $this->query($sql, $params);
	}

	public function update($table, $params, $where){
		$fields = $this->buildQueryStr($params, ', ');
		$condition = $this->buildQueryStr($where);
		$keys = array_keys($where);

		/*	
			Change parameter name if it exists in both the SET and the WHERE clauses
		 	(so that the same parameter isn't bound twice) 
		*/
		foreach($keys as $key){
			if(array_key_exists($key, $params)){
				$condition = str_replace(':' . $key, ':old_' . $key, $condition);
				$where['old_' . $key] = $where[$key];
				unset($where[$key]);
			}
		}

		$params = array_merge($params, $where);
		$sql = "UPDATE $table SET $fields WHERE $condition";

		return $this->query($sql, $params);
	}

	public function delete($table, $where){
		$condition = $this->buildQueryStr($where);
		$sql = "DELETE FROM $table WHERE $condition";
		
		return $this->query($sql, $where);
	}

	// Call a stored procedure (parameters must be passed in the expected order)
	public function call($procedure, $params = []){
		$sql = "CALL $procedure";

		if(!empty($params)){
			$fields = ':' . implode(', :', array_keys($params));
			$sql .= "($fields)";
		}

		return $this->query($sql, $params);
	}

	private function buildQueryStr($params, $delimiter = ' AND'){
		$queryStr = '';
		$keys = array_keys($params);
		$last = end($keys);

		foreach($keys as $key){
			$queryStr .= $key . ' = :' . $key;

			if($key !== $last) $queryStr .= $delimiter . ' ';
		}

		return $queryStr;
	}

	// Get PDO constant corresponding to the required type
	private function getDataType($dataType, $isParam = false){
		$type;

		if($isParam){
			switch($dataType){
				case 'integer':
					$type = PDO::PARAM_INT;
				break;
				case 'boolean':
					$type = PDO::PARAM_BOOL;
				break;
				case 'NULL':
					$type = PDO::PARAM_NULL;
				break;						
				default:
					$type = PDO::PARAM_STR;
				break;
			}		
		}else{
			$type = ($dataType !== 'array') ? PDO::FETCH_OBJ : PDO::FETCH_ASSOC;
		}

		return $type;
	}

	public function first(){
		return (!empty($this->results)) ? $this->results[0] : null;
	}

	public function results(){
		return $this->results;
	}
	
	public function rows() {
		return $this->rows;
	}

	public function hasResults(){
		return $this->rows > 0;
	}
	
	public function lastId(){
		return $this->lastInsertId();
	}
	
	public function success(){
		return !$this->errorHandler->hasErrors();
	}

	public function error(){
		return $this->errorHandler->first();
	}
}