<?php
	namespace FatZebra;
	define("GATEWAY_URL", "https://gateway.fatzebra.com.au");
	define("GATEWAY_USERNAME", "TESTbiztech");
	define("GATEWAY_TOKEN", "16a31852daf418050eaf628f4fffecfccbf9571c");

	

	class Gateway {
		public $url = "https://gateway.fatzebra.com.au";
		public $username;
		public $token;
		public $test_mode = true; // This needs to be set to false for production use.

		public function __construct($username, $token, $test_mode, $gateway_url = null) {
			$this->username = $username;
			$this->token = $token;
			$this->test_mode = $test_mode;
			if (!is_null($gateway_url)) {
				$this->url = $gateway_url;
			}
		}

		public function purchase($request) {
			$customer_ip = $_SERVER['REMOTE_ADDR'];

			$payload = array_merge($request->to_array(), array("customer_ip" => $customer_ip));

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $this->url . "/purchases");
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $this->username .":". $this->token);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem');

		}
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
			if(strlen($reference) == 0) throw new \InvalidArgumentException("Reference is a required field.");
			$this->reference = $reference;

			if(is_null($card_holder) || (strlen($card_holder) == 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
			$this->card_holder = $card_holder;
			
			if(is_null($card_number) || (strlen($card_number) == 0)) throw new \InvalidArgumentException("Card Number is a required field.");
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

	function purchase($amount, $card_holder, $card_number, $expiry, $cvv, $reference) {
		$payload = array("card_holder" => $card_holder, "amount" => $amount, "card_number" => $card_number,
						 "card_expiry" => $expiry, "cvv" => $cvv, "reference" => $reference, "customer_ip" => $_SERVER [ 'REMOTE_ADDR']);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, GATEWAY_URL . "/purchases");
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, GATEWAY_USERNAME .":". GATEWAY_TOKEN);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem');
 
		$data = curl_exec($curl); 
		if (curl_errno($curl) !== 0) {
			die("cURL error: " . curl_error($curl));
		}
		curl_close($curl);
		return json_decode($data);
	}

?>