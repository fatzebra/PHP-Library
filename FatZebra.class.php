<?php
	namespace FatZebra;
	/**
	* Fat Zebra PHP Gateway Library
	* 
	* Created February 2012 - Matthew Savage (matthew.savage@fatzebra.com.au)
	* Updated 20 February 2012 - Matthew Savage (matthew.savage@fatzebra.com.au)
	*
	* The original source for this library, including its tests can be found at
	* https://github.com/fatzebra/PHP-Library
	*
	* Please visit http://docs.fatzebra.com.au for details on the Fat Zebra API
	* or https://www.fatzebra.com.au/help for support.
	*
	* Patches, pull requests, issues, comments and suggestions always welcome.
	*/
	
	class Gateway {
		public $url = "https://gateway.fatzebra.com.au";
		public $api_version = "1.0";
		public $username;
		public $token;
		public $test_mode = true; // This needs to be set to false for production use.

		public function __construct($username, $token, $test_mode = true, $gateway_url = null) {
			if (is_null($username) || strlen($username) === 0) throw new \InvalidArgumentException("Username is required");
			$this->username = $username;
			
			if (is_null($token) || strlen($token) === 0) throw new \InvalidArgumentException("Token is required");
			$this->token = $token;

			$this->test_mode = $test_mode;
			if (!is_null($gateway_url)) {
				$this->url = $gateway_url;
			}
		}

		public function purchase($request) {
			$customer_ip = $_SERVER['REMOTE_ADDR'];
			if (is_null($customer_ip)) $customer_ip = "127.0.0.1";

			$payload = array_merge($request->	to_array(), array("customer_ip" => $customer_ip));
			return $this->do_request("POST", "/purchases", $payload);
		}

		public function get_purchase($reference) {
			if (is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is required");
			return $this->do_request("GET", "/purchases/" . $reference);
		}

		private function do_request($method, $uri, $payload) {
			$curl = curl_init();
			if(is_null($this->version)) {
				curl_setopt($curl, CURLOPT_URL, $this->url . $uri);
			} else {
				curl_setopt($curl, CURLOPT_URL, $this->url . "/v" . $this->version . $uri);	
			}
			
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $this->username .":". $this->token);
			curl_setopt($curl, CURLOPT_POST, $method == "POST");
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem');

			$data = curl_exec($curl); 
			
			if (curl_errno($curl) !== 0) {
				throw new \Exception("cURL error: " . curl_error($curl));
			}
			curl_close($curl);

			$response =  json_decode($data);
			if (is_null($response)) {
				$err = json_last_error();
				if ($err == JSON_ERROR_SYNTAX) {
					throw new \Exception("JSON Syntax error. JSON attempted to parse: " . $data);
				} elseif ($err == JSON_ERROR_UTF8) {
					throw new \Exception("JSON Data invalid - Malformed UTF-8 characters. Data: " . $data);
				} else {
					throw new \Exception("JSON parse failed. Unknown error. Data:" . $data);
				}
			}

			return $response;
		}

		// TODO: refunds, captures, recurring
	}


	class PurchaseRequest {
		private $amount = 0.00;
		private $reference = "";
		private $card_holder = "";
		private $card_number = "";
		private $expiry = "";
		private $cvv = "";

		public function __construct($amount, $reference, $card_holder, $card_number, $expiry, $cvv) {
			if(is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
			if((float)$amount < 0) throw new \InvalidArgumentException("Amount is invalid.");
			$this->amount = $amount;

			if(is_null($reference)) throw new \InvalidArgumentException("Reference is a required field.");
			if(strlen($reference) === 0) throw new \InvalidArgumentException("Reference is a required field.");
			$this->reference = $reference;

			if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
			$this->card_holder = $card_holder;
			
			if(is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
			$this->card_number = $card_number;
			
			if(is_null($expiry)) throw new \InvalidArgumentException("Expiry is a required field.");
			$this->expiry = $expiry;
			
			if(is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");
			$this->cvv = $cvv;
		}		

		public function to_array() {
			$amount_as_int = (int)($this->amount * 100);
			return array("card_holder" => $this->card_holder,
						 "card_number" => $this->card_number,
						 "card_expiry" => $this->expiry,
						 "cvv" => $this->cvv,
						 "reference" => $this->reference,
						 "amount" => $amount_as_int);
		}
	}

?>