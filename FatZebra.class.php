<?php
	/**
	* Fat Zebra PHP Gateway Library
	* Version 1.1.7
	*
	* The original source for this library, including its tests can be found at
	* https://github.com/fatzebra/PHP-Library
	*
	* Please visit http://docs.fatzebra.com.au for details on the Fat Zebra API
	* or https://www.fatzebra.com.au/help for support.
	*
	* Patches, pull requests, issues, comments and suggestions always welcome.
	*
	* @package FatZebra
	*/
	namespace FatZebra;

	require 'Helpers.php';
	/**
	* The Fat Zebra Gateway class for interfacing with Fat Zebra
	*/
	class Gateway {
		/**
		* The version of this library
		*/
		public $version = "1.1.7";

		/**
		* The URL of the Fat Zebra gateway
		*/
		public $url = "https://gateway.fatzebra.com.au";

		/**
		* The sandbox URL of the Fat Zebra gateway
		*/
		public $sandbox_url = "https://gateway.sandbox.fatzebra.com.au";

		/**
		* The API version for the requests
		*/
		public $api_version = "1.0";

		/**
		* The gateway username
		*/
		public $username;

		/**
		* The gateway token
		*/
		public $token;

		/**
		* Indicates if test mode should be used or not
		*/
		public $test_mode = true; // This needs to be set to false for production use.

		/**
		* The connection timeout - the maximum processing time for Fat Zebra is 30 seconds,
		* however in the event of a timeout the transaction will be re-queried which could increase the
		* processing time up to 50 seconds. Currently this is, on average, below 10 seconds.
		*/
		public $timeout = 50;

		/**
		* Creates a new instance of the Fat Zebra gateway object
		* @param string $username the username for the gateway
		* @param string $token the token for the gateway
		* @param boolean $test_mode indicates if the test mode should be used or not
		* @param string $gateway_url the URL for the Fat Zebra gateway
		* @return Gateway
		*/
		public function __construct($username, $token, $test_mode = true, $gateway_url = null) {
			if (is_null($username) || strlen($username) === 0) throw new \InvalidArgumentException("Username is required");
			$this->username = $username;

			if (is_null($token) || strlen($token) === 0) throw new \InvalidArgumentException("Token is required");
			$this->token = $token;

			$this->test_mode = $test_mode;

			if ($this->test_mode) {
				$this->url = $this->sandbox_url;
			}

			if (!is_null($gateway_url)) {
				$this->url = $gateway_url;
			}
		}

		/**
		* Performs a purchase against the FatZebra gateway
		* @param PurchaseRequest $request the purchase request with the purchase details
		* @return \StdObject
		*/
		public function purchase($request) {
			$customer_ip = $this->get_customer_ip();

			$payload = array_merge($request->to_array(), array("customer_ip" => $customer_ip));
			return $this->do_request("POST", "/purchases", $payload);
		}

		/**
		* Performs a purchase against the FatZebra gateway with a tokenized credit card
		* @param string $token the card token
		* @param float $amount the purchase amount
		* @param string $reference the purchase reference
		* @param string $cvv the card verification value - optional but recommended
		* @param string $currency the currency code for the transaction. Defaults to AUD
		* @return \StdObject
		*/
		public function token_purchase($token, $amount, $reference, $cvv = null, $currency = "AUD") {
			$customer_ip = $this->get_customer_ip();

			if (function_exists('bcmul')) {
				$int_amount = intval(bcmul($amount, 100));
			} else {
				$multiplied = round($amount * 100);
				$int_amount = (int)$multiplied;
			}
			$payload = array(
				"customer_ip" => $customer_ip,
				"card_token" => $token,
				"cvv" => $cvv,
				"amount" => $int_amount,
				"reference" => $reference,
				"currency" => $currency
				);
			return $this->do_request("POST", "/purchases", $payload);
		}

		/**
		* Performs a refund against the FatZebra gateway
		* @param string $transaction_id the original transaction ID to be refunded
		* @param float $amount the amount to be refunded
		* @param string $reference the refund reference
		* @return \StdObject
		*/
		public function refund($transaction_id, $amount, $reference) {
			if(is_null($transaction_id) || strlen($transaction_id) === 0) throw new \InvalidArgumentException("Transaction ID is required");
			if(is_null($amount) || strlen($amount) === 0) throw new \InvalidArgumentException("Amount is required");
			if(intval($amount) < 1) throw new \InvalidArgumentException("Amount is invalid - must be a positive value");
			if(is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is required");

			if (function_exists('bcmul')) {
				$int_amount = intval(bcmul($amount, 100));
			} else {
				$multiplied = round($amount * 100);
				$int_amount = (int)$multiplied;
			}

			$payload = array(
				"transaction_id" => $transaction_id,
				"amount" => $int_amount,
				"reference" => $reference
				);

			return $this->do_request("POST", "/refunds", $payload);

		}

		/**
		* Retrieves a purchase from the FatZebra gateway
		* @param string $reference the purchase ID
		* @return \StdObject
		*/
		public function get_purchase($reference) {
			if (is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is required");
			return $this->do_request("GET", "/purchases/" . $reference);
		}

		/**
		* Retrieves a refund from the FatZebra gateway
		* @param string $reference the refund ID
		* @return \StdObject
		*/
		public function get_refund($reference) {
			if (is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is required");
			return $this->do_request("GET", "/refunds/" . $reference);
		}

		/**
		* Created a new tokenized credit card
		* @param string $card_holder the card holders name
		* @param string $card_number the card number
		* @param string $expiry_date the card expiry date (mm/yyyy format)
		* @param string $cvv the card verification value
		* @return \StdObject
		*/
		public function tokenize($card_holder, $card_number, $expiry_date, $cvv) {
			if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
			if(is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
			if(is_null($expiry_date)) throw new \InvalidArgumentException("Expiry is a required field.");
			if(is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");


			$customer_ip = $this->get_customer_ip();

			$payload = array(
				"customer_ip" => $customer_ip,
				"card_holder" => $card_holder,
				"card_number" => $card_number,
				"card_expiry" => $expiry_date,
				"cvv" => $cvv
				);
			return $this->do_request("POST", "/credit_cards", $payload);
		}

		/**
		* Create a new customer for recurring subscriptions
		* @param string $first_name the customers first name
		* @param string $last_name the customers last name
		* @param string $reference your system reference (i.e. record ID etc)
		* @param string $email the customers email address
		* @param string $card_holder the card holders name (likely to be the same as the customers name)
		* @param string $card_number the credit card number
		* @param string $card_expiry the card expiry date (mm/yyyy)
		* @param string $cvv the CVV for the credit card
		* @return \StdObject
		*/
		public function create_customer($first_name, $last_name, $reference, $email, $card_holder, $card_number, $card_expiry, $cvv) {
			if(is_null($first_name) || (strlen($first_name) === 0)) throw new \InvalidArgumentException("First name is a required field.");
			if(is_null($last_name) || (strlen($last_name) === 0)) throw new \InvalidArgumentException("Last name is a required field.");
			if(is_null($email) || (strlen($email) === 0)) throw new \InvalidArgumentException("Email is a required field.");
			if(is_null($reference) || (strlen($reference) === 0)) throw new \InvalidArgumentException("Reference is a required field.");

			if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
			if(is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
			if(is_null($card_expiry)) throw new \InvalidArgumentException("Expiry is a required field.");
			if(is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");

			$payload = array(
				"first_name" => $first_name,
				"last_name" => $last_name,
				"reference" => $reference,
				"email" => $email,
				"card" => array(
					"card_holder" => $card_holder,
					"card_number" => $card_number,
					"expiry_date" => $card_expiry,
					"cvv" => $cvv
					));

			return $this->do_request("POST", "/customers", $payload);
		}

		/**
		* Subscribe a customer to a plan
		*
		* @param string $customer_id the Fat Zebra Customer ID or your internal reference
		* @param string $plan_id the Fat Zebra Plan ID or the reference
		* @param string $frequency the billing frequency/interval. This can be: Daily, Weekly, Fortnightly, Monthly, Quarterly, Bi-Annually or Annually
		* @param string $start_date the start date of the subscription (the first billing date)
		* @param string $end_date the end date of the subscription
		* @param string $reference the reference for this subscription
		* @param bool $is_active indicates if the subscription is active or not
		* @return \StdObject
		*/
		public function create_subscription($customer_id, $plan_id, $frequency, $start_date, $reference, $is_active = true, $end_date = null) {
			if(is_null($customer_id) || (strlen($customer_id) === 0)) throw new \InvalidArgumentException("Customer ID or Reference is a required field.");
			if(is_null($plan_id) || (strlen($plan_id) === 0)) throw new \InvalidArgumentException("Plan ID or Reference is a required field.");

			if(is_null($frequency) || (strlen($frequency) === 0)) throw new \InvalidArgumentException("Email is a required field.");
			if(!in_array($frequency, array("Daily", "Weekly", "Fortnightly", "Monthly", "Quarterly", "Bi-Annually", "Annually"))) throw new \InvalidArgumentException("Invalid Frequency, Acceptable values are: Daily, Weekly, Fortnightly, Monthly, Quarterly, Bi-Annually or Annually");

			if(!Helpers::isTimestamp($start_date)){
				throw new \InvalidArgumentException("Invalid start date - must be a timestamp");
			}

			if(isset($end_date) && !Helpers::isTimestamp($end_date)){
				throw new \InvalidArgumentException("Invalid end date - must be a timestamp");
			}
			$payload = array(
				"customer" => (string) $customer_id,
				"plan" => $plan_id,
				"frequency" => $frequency,
				"start_date" => date("Y-m-d", $start_date),
				"end_date" => isset($end_date) ? date("Y-m-d", $end_date) : null,
				"reference" => $reference,
				"is_active" => $is_active
				);

			return $this->do_request("POST", "/subscriptions", $payload);
		}

		/**
		* Cancel an existing subscription
		* @param string $subscription_id the subscription ID
		*/
		public function cancel_subscription($subscription_id) {
			$payload = array("is_active" => false);
			return $this->do_request("PUT", "/subscriptions/" . $subscription_id, $payload);
		}

		/**
		* Resume a cancelled subscription
		* @param string $subscription_id the subscription ID
		*/
		public function resume_subscription($subscription_id) {
			$payload = array("is_active" => true);
			return $this->do_request("PUT", "/subscriptions/" . $subscription_id, $payload);
		}

		/**
		* Create a Plan for subscriptions
		* @param string $name the plan name
		* @param int $amount the amount for the plan
		* @param string $reference the plan reference
		* @param string $description the plan description
		* @return \StdObject
		*/
		public function create_plan($name, $amount, $reference, $description) {
			if(is_null($name) || (strlen($name) === 0)) throw new \InvalidArgumentException("Plan Name is a required field.");
			if(is_null($amount) || ((int)$amount < 1)) throw new \InvalidArgumentException("Amount is invalid.");
			if(is_null($reference) || (strlen($reference) === 0)) throw new \InvalidArgumentException("Reference is a required field.");
			if(is_null($description) || (strlen($description) === 0)) throw new \InvalidArgumentException("Description is a required field.");

			$payload = array(
				"name" => $name,
				"amount" => (int)$amount,
				"reference" => $reference,
				"description" => $description);

			return $this->do_request("POST", "/plans", $payload);
		}


		// TODO: auth/captures


		/************** Private functions ***************/

		/**
		* Performs the request against the Fat Zebra gateway
		* @param string $method the request method ("POST" or "GET")
		* @param string $uri the request URI (e.g. /purchases, /credit_cards etc)
		* @param Array $payload the request payload (if a POST request)
		* @return \StdObject
		*/
		private function do_request($method, $uri, $payload = null) {
			$curl = curl_init();
			if(is_null($this->api_version)) {
				$url = $this->url . $uri;
				curl_setopt($curl, CURLOPT_URL, $url);
			} else {
				$url = $this->url . "/v" . $this->api_version . $uri;
				curl_setopt($curl, CURLOPT_URL, $url);
			}

			$payload["test"] = $this->test_mode;

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $this->username .":". $this->token);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array("User-agent: FatZebra PHP Library " . $this->version));

			if ($method == "POST" || $method == "PUT") {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json", "User-agent: FatZebra PHP Library " . $this->version));
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
			}

			if ($method == "PUT") {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			}

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSLVERSION, 6); // CURLOPT_SSLVERSION_TLSv1_2
			curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ca-bundle.crt');
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

			$data = curl_exec($curl);

			if (curl_errno($curl) !== 0) {
				if (curl_errno($curl) == 28) throw new TimeoutException("cURL Timeout: " . curl_error($curl));
				throw new \Exception("cURL error " . curl_errno($curl) . ": " . curl_error($curl));
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

		/**
		* Fetches the customers 'real' IP address (i.e. pulls out the address from X-Forwarded-For if present)
		*
		* @return String the customers IP address
		*/
		private function get_customer_ip() {
			$customer_ip = $_SERVER['REMOTE_ADDR'];
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $customer_ip = $forwarded_ips[0];
      }

      return $customer_ip;
		}
	}


	/**
	* The Fat Zebra Purchase Request
	*/
	class PurchaseRequest {
		/**
		* The purchase amount
		*/
		private $amount = 0.00;

		/**
		* The purchase reference
		*/
		private $reference = "";

		/**
		* The card holders name
		*/
		private $card_holder = "";

		/**
		* The card number
		*/
		private $card_number = "";

		/**
		* The card expiry date
		*/
		private $expiry = "";

		/**
		* The Card Verification Value
		*/
		private $cvv = "";

		/**
		* Extra order data for Retail Decisions fraud detection
		*/
		private $fraud_data = null;

		/**
		* Currency code for transaction
		*/
		private $currency = "AUD";

		/**
		* Creates a new instance of the PurchaseRequest
		* @param float $amount the purchase amount
		* @param string $reference the reference for the purchase
		* @param string $card_holder the card holders name
		* @param string $card_number the card number
		* @param string $expiry the card expiry (mm/yyyy format)
		* @param string $cvv the card verification value
		* @return PurchaseRequest
		*/
		public function __construct($amount, $reference, $card_holder, $card_number, $expiry, $cvv, $fraud_data = null, $currency = "AUD") {
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

			$this->fraud_data = $fraud_data;
		}

		/**
		* Returns the request as a hash/assoc. array
		* @return \Array
		*/
		public function to_array() {
			$int_amount = Helpers::floatToInt($this->amount);

			$data = array(
				"card_holder" => $this->card_holder,
				"card_number" => $this->card_number,
				"card_expiry" => $this->expiry,
				"cvv" => $this->cvv,
				"reference" => $this->reference,
				"amount" => $int_amount,
				"currency" => $this->currency
			);
			if (!is_null($this->fraud_data)) {
				$data['fraud'] = $this->fraud_data;
			}
			return $data;
		}
	}

	class TimeoutException extends \Exception {}

?>
