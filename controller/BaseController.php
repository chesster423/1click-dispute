<?php
/**
 * 
 */
class BaseController 
{
	public $conn = null;
	private $table;

	public function __construct($db) {
		$this->conn = $db;
	}

	public function getTable() {

		if (isset($_GET['entity'])) {
			$this->table = $_GET['entity'] . "s";
		}else{
			header("HTTP/1.1 401 Unauthorized");
    		exit;
		}

		return $this->table;
	}

	/**
	* Gets row(s) from the table
	*
	* @param $conn DB database connection
	* @param integer $start Determines starting row, defaults to 
	* @return array
	*/
	public function index($start = 0) {

		$data = [];

		$query = "SELECT * FROM " . $this->getTable() . " ORDER BY id DESC LIMIT $start, 20";

		$data = $this->conn->GetRows($query);

		return $this->response(true, "", $data);

	}

	/**
	* Gets 1 result from the table
	*
	* @param $conn DB database connection
	* @return array of info
	*/
	public function find($request) {

		$data = [];

		$query = "SELECT * FROM " . $this->getTable() . " WHERE id = '" . $request['id'] .  "'";

		$data = $this->conn->GetDataFromDb($query);

		if ($data) {			
			return $this->response(true, "Data found", $data);
		}

		return $this->response(false, "No user found");

	}
	
	/**
	* Save new row to the table
	*
	* @param $conn DB database connection
	* @param array $data necessary information to insert to the table
	* @return array
	*/
	public function create($data) {
	    $success = false;
	    $responseMessage = "An error occurred, please try again.";

		$fields = '';
		$values = '';

		$password = $data['password'];
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

		if ($data['user_type'] == 'admin') {
            if ($this->conn->NumOfRows("SELECT id FROM admins WHERE email='".$data['email']."'") > 0)
                return $this->response(false, "Account with this email already exists!");

		    $query = "INSERT INTO admins (name,email,password,picture,createdOn) VALUES ('".$data['name']."','".$data['email']."','".$data['password']."','user.png',NOW())";

            if ($this->conn->ExecuteQuery($query)) {
                $success = true;
                $responseMessage = "Data saved! Password: $password";
            }
        } else {

            if ($this->conn->NumOfRows("SELECT id FROM users WHERE email='".$data['email']."'") > 0)
                return $this->response(false, "Account with this email already exists!");

            foreach ($data as $key => $value) {
                $fields .= $this->conn->escape($key) . ", ";
                $values .= "'" . $this->conn->escape($value) . "', ";
            }

            $fields = rtrim($fields, ", ");
            $values = rtrim($values, ", ");

            $query = "INSERT INTO " . $this->getTable() . " ($fields) VALUES ($values)";

            if ($this->conn->ExecuteQuery($query)) {
                $success = true;
                $responseMessage = "Data saved! Password: $password";
            }
        }

		return $this->response($success, $responseMessage);
	}

	/**
	* Save new multiple row to the table
	*
	* @param $conn DB database connection
	* @param array $data necessary information to insert to the table
	* @return bool
	*/
	public function create_bulk($request) {

		$fields = [];
		$values = '';

		if (isset($request[0])) {
			foreach ($request[0] as $key => $value) {
				if (!in_array($key, $fields)) {
					$fields[] = $key;
				}
			}
		}

		foreach ($request as $key => $value) {

			array_walk($value, function(&$x) {$x = "'$x'";});

			$values .= "(".implode(', ', $value)."),";
		}

		$fields = implode(', ', $fields);
		$values = rtrim($values, ", ");

		$query = "INSERT INTO " . $this->getTable() . " ($fields) VALUES $values";

		if ($this->conn->ExecuteQuery($query)) {			
			return $this->response(true, "Data saved!");
		}

		return $this->response(false, "An error occured");

	}

	/**
	* Update existing record in the table
	*
	* @param $conn DB database connection
	* @param array $params necessary information to update to the table
	* @return bool
	*/
	public function update($request) {

		try {

			$data = '';
			$param_data = $request;

			unset($param_data['action']);
			unset($param_data['entity']);

			foreach ($param_data as $key => $value) {
				if ($key != 'id') {

					if ($value != 'NOW()') {
						$value = "'" . $this->conn->escape($value) . "'";
					}

					$data .= $this->conn->escape($key) . " = {$value}, ";
				}		
			}

			$data = rtrim($data,', ');

			$query = "UPDATE " . $this->getTable() . " SET $data WHERE id = '" . $this->conn->escape($param_data['id']) . "' ";

			$update = $this->conn->ExecuteQuery($query);

			if ($update) {
				return $this->response(true, "Data updated!");
			}
			
		} catch (Exception $e) {
			return $this->response(false, "An error occured", $e);
		}

		return $this->response(false, "An error occured");

	}


	/**
	* Single single row
	*
	* @param $conn DB database connection
	* @param array $params necessary information to delete data
	* @return bool
	*/
	public function delete($request) {

		$query = "DELETE FROM " . $this->getTable() . " WHERE id = " . $request['id'];

		$delete = $this->conn->ExecuteQuery($query);

		if ($delete) {			
			return $this->response(true, "Data deleted");
		}

		return $this->response(false, "An error occured");
	}

	public function response($success = false, $msg = '', $data = null){
		return [
			'success' => $success,
			'msg' 	  => $msg,
			'data' 	  => $data
		];
	}


	public function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

}
