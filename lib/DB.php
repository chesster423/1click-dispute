<?php
	include "config.php";

	$DB = new DB( array(
		"servername" => $servername,
		"username"   => $dbusername,
		"password"   => $dbpassword,
		"dbname"     => $dbname
	) );

	class DB {

		private $con;
		private $mySQLi = true;

		public function __construct( $db = array() ) {
			//check for mysqli support
			if ( class_exists( 'mysqli' ) ) {
				//check if we're working on localhost
                if ($_SERVER["SERVER_NAME"] == "localhost" || strpos($_SERVER['SERVER_NAME'], 'ngrok.io') !== false)
                    $this->con = new mysqli( "localhost", "root", "", $db["dbname"] );
				else {
					$this->con = new mysqli( $db["servername"], $db["username"], $db["password"], $db["dbname"] );
				}

				if ( $this->con->connect_error ) {
					echo "Failed to connect, refresh your page!";
				}
			} else {
				$this->mySQLi = false;
				//check if we're working on localhost
				if ( $_SERVER["SERVER_NAME"] == "localhost" ) {
					$this->con = @mysql_connect( "localhost", "root", "" );
				} else {
					$this->con = @mysql_connect( $db["servername"], $db["username"], $db["password"] );
				}

				if ( ! $this->con ) {
					echo "Failed to connect, refresh your page!";
				} else {
					$this->ExecuteQuery( "USE " . $db["dbname"] );
				}
			}
		}

		public function __destruct() {
			if ( ! $this->mySQLi ) {
				mysql_close( $this->con );
			} else {
				mysqli_close( $this->con );
			}
		}

		public function generateRandomString($length = 24) {
            if(function_exists('openssl_random_pseudo_bytes')) {
                $token = base64_encode(openssl_random_pseudo_bytes($length, $strong));
                if($strong == TRUE)
                    return strtr(substr($token, 0, $length), '+/=', '-_,'); //base64 is about 33% longer, so we need to truncate the result
            }

            //fallback to mt_rand if php < 5.3 or no openssl available
            $characters = '0123456789';
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz/+';
            $charactersLength = strlen($characters)-1;
            $token = '';

            //select some random characters
            for ($i = 0; $i < $length; $i++) {
                $token .= $characters[mt_rand(0, $charactersLength)];
            }

            return $token;
        }

		public function getStripeAPIKeys($keys) {
            $apiKeys = array("pk" => "", "sk" => "");

		    if (strpos($keys, ',') !== false) {
                $keys = explode(",", $keys);

                if (strpos($keys[0], 'pk_') !== false) {
                    $apiKeys["pk"] = trim($keys[0]);
                    $apiKeys["sk"] = trim($keys[1]);
                } else {
                    $apiKeys["pk"] = trim($keys[1]);
                    $apiKeys["sk"] = trim($keys[0]);
                }
            }

			return $apiKeys;
		}

		public function getIP() {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $ip;
        }

		/* MYSQL FUNCTIONS */
		public function filterText( $data ) {
			$data = trim( htmlentities( strip_tags( $data ) ) );

			if ( get_magic_quotes_gpc() ) {
				$data = stripslashes( $data );
			}

			return $data;
		}

		public function escape( $data ) {
			if ( ! $this->mySQLi ) {
				$data = mysql_real_escape_string( $data );
			} else {
			    if (is_array($data)) {
	                $tempData = array();

	                foreach($data as $key => $val)
	                    $tempData[$key] = $this->con->real_escape_string($val);

			        $data = $tempData;
                } else
    				$data = $this->con->real_escape_string( $data );
			}

			return $data;
		}

		public function ExecuteQuery( $q ) {
			if ( $this->mySQLi ) {
				$data = $this->con->query( $q );
			} else {
				$data = mysql_query( $q );
			}

			return $data;
		}

		public function NumOfRows( $q ) {
			$sql = $this->ExecuteQuery( $q );
			if ( ! empty( $sql ) ) {
				if ( $this->mySQLi ) {
					$data = $sql->num_rows;
				} else {
					$data = mysql_num_rows( $sql );
				}
			} else {
				$data = 0;
			}

			return $data;
		}

		public function myFetch( $sql ) {
			if ( $this->mySQLi ) {
				return $sql->fetch_array();
			} else {
				return mysql_fetch_array( $sql );
			}
		}

		public function GetDataFromDb( $q ) {
			$sql = $this->ExecuteQuery( $q );
			if ( ! empty( $sql ) ) {
				if ( $this->mySQLi ) {
					$data = $sql->fetch_assoc();
				} else {
					$data = mysql_fetch_array( $sql );
				}
			} else {
				$data = "";
			}

			return $data;
		}

		public function GetRows( $q ) {
			$sql = $this->ExecuteQuery( $q );
			$data = [];
			if ( ! empty( $sql ) ) {
				if ( $this->mySQLi ) {
                    if ($sql->num_rows > 0) {
                    	while ($row = $sql->fetch_assoc()) {
                            $data[] = $row;
                        }
                    }
				} else {
					$data = mysql_fetch_array( $sql );
				}
			} else {
				$data = "";
			}

			return $data;
		}
	}
?>