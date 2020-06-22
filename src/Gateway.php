<?php
/* @package FatZebra
 * vim: ts=4 sw=4 sts=4 noet
 */
namespace FatZebra;

use \InvalidArgumentException;

/**
 * The Fat Zebra Gateway class for interfacing with Fat Zebra
 */
class Gateway {
    /**
     * The version of this library
     */
    private $version = '2.2.0';

    /**
     * The URL of the Fat Zebra gateway
     */
    private $url = 'https://gateway.pmnts.io';

    /**
     * The sandbox URL of the Fat Zebra gateway
     */
    private $sandbox_url = 'https://gateway.pmnts-sandbox.io';

    /**
     * The API version for the requests
     */
    private $api_version = '1.0';

    /**
     * The gateway username
     */
    private $username;

    /**
     * The gateway token
     */
    private $token;

    /**
     * Indicates if test mode should be used or not
     */
    private $test_mode = true; // This needs to be set to false for production use.

    /**
     * The connection timeout - the maximum processing time for Fat Zebra is 30 seconds,
     * however in the event of a timeout the transaction will be re-queried which could increase the
     * processing time up to 50 seconds. Currently this is, on average, below 10 seconds.
     */
    private $timeout = 50;

    /**
     * Customer real IP to send.
     */
    private $customer_ip;

    /**
     * Creates a new instance of the Fat Zebra gateway object
     * @param string $username the username for the gateway
     * @param string $token the token for the gateway
     * @param boolean $test_mode indicates if the test mode should be used or not
     * @param string $gateway_url the URL for the Fat Zebra gateway
     * @return Gateway
     */
    public function __construct($username, $token, $test_mode = true, $gateway_url = null) {
        if (is_null($username) || strlen($username) === 0) throw new InvalidArgumentException('Username is required');
        $this->username = $username;

        if (is_null($token) || strlen($token) === 0) throw new InvalidArgumentException('Token is required');
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
     * Allows customization the request timeout threshold, measured in seconds.
     * @param int $timeout seconds to wait before connection timeout
     */
    public function set_timeout($timeout) {
        $this->timeout = $timeout;
    }

    /**
     * Performs a purchase against the FatZebra gateway
     * @param float $amount the purchase amount
     * @param string $reference the reference for the purchase
     * @param string $card_holder the card holders name
     * @param string $card_number the card number
     * @param string $expiry the card expiry (mm/yyyy format)
     * @param string $cvv the card verification value
     * @return \StdObject
     */
    public function purchase($amount, $reference, $card_holder, $card_number, $expiry, $cvv, $fraud_data = null, $currency = 'AUD', $extra = null) {
        $customer_ip = $this->get_customer_ip();

        if(is_null($amount)) throw new InvalidArgumentException('Amount is a required field.');
        if(is_null($reference)) throw new InvalidArgumentException('Reference is a required field.');
        if(strlen($reference) === 0) throw new InvalidArgumentException('Reference is a required field.');
        if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new InvalidArgumentException('Card Holder is a required field.');
        if(is_null($card_number) || (strlen($card_number) === 0)) throw new InvalidArgumentException('Card Number is a required field.');
        if(is_null($expiry)) throw new InvalidArgumentException('Expiry is a required field.');
        if(is_null($cvv)) throw new InvalidArgumentException('CVV is a required field.');

        $int_amount = self::floatToInt($amount);

        $payload = [
            'card_holder' => $card_holder,
            'card_number' => $card_number,
            'card_expiry' => $expiry,
            'cvv' => $cvv,
            'reference' => $reference,
            'amount' => $int_amount,
            'currency' => $currency,
            'customer_ip' => $customer_ip
        ];

        if (!is_null($fraud_data)) {
            $payload['fraud'] = $fraud_data;
        }

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request('POST', '/purchases', $payload);
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
    public function token_purchase($token, $amount, $reference, $cvv = null, $currency = 'AUD') {
        if(is_null($amount)) throw new InvalidArgumentException('Amount is a required field.');
        if(is_null($reference)) throw new InvalidArgumentException('Reference is a required field.');
        if(strlen($reference) === 0) throw new InvalidArgumentException('Reference is a required field.');
        if(is_null($token) || (strlen($token) === 0)) throw new InvalidArgumentException('Card token is a required field.');

        $customer_ip = $this->get_customer_ip();

        $int_amount = self::floatToInt($amount);
        $payload = [
            'customer_ip' => $customer_ip,
            'card_token' => $token,
            'cvv' => $cvv,
            'amount' => $int_amount,
            'reference' => $reference,
            'currency' => $currency
        ];
        return $this->do_request('POST', '/purchases', $payload);
    }

    /**
     * Performs an authorization against the FatZebra gateway with credit card details
     * @param float $amount the purchase amount
     * @param string $reference the purchase reference
     * @param string $card_holder the card holders name
     * @param string $card_number the credit card number for the transaction
     * @param string $expiry the card expiry date (mm/yyyy format)
     * @param string $cvv the card security code (also called CVV, CVC, CVN etc)
     * @param string $currency the currency code for the transaction. Defaults to AUD
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function authorization($amount, $reference, $card_holder, $card_number, $expiry, $cvv, $currency = 'AUD', $extra = null) {
        if(is_null($amount)) throw new InvalidArgumentException('Amount is a required field.');
        if(is_null($reference)) throw new InvalidArgumentException('Reference is a required field.');
        if(strlen($reference) === 0) throw new InvalidArgumentException('Reference is a required field.');
        if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new InvalidArgumentException('Card Holder is a required field.');
        if(is_null($card_number) || (strlen($card_number) === 0)) throw new InvalidArgumentException('Card Number is a required field.');
        if(is_null($expiry)) throw new InvalidArgumentException('Expiry is a required field.');
        if(is_null($cvv)) throw new InvalidArgumentException('CVV is a required field.');

        $customer_ip = $this->get_customer_ip();

        $int_amount = self::floatToInt($amount);

        $payload = [
            'customer_ip' => $customer_ip,
            'card_number' => $card_number,
            'card_holder' => $card_holder,
            'card_expiry' => $expiry,
            'cvv' => $cvv,
            'reference' => $reference,
            'amount' => $int_amount,
            'currency' => $currency,
            'capture' => false
        ];

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request('POST', '/purchases', $payload);
    }

    /**
     * Performs an authorization against the FatZebra gateway with a tokenized credit card
     * @param float $amount the purchase amount
     * @param string $reference the purchase reference
     * @param string $token the card token or alias for the authorization
     * @param string $currency the currency code for the transaction. Defaults to AUD
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function token_authorization($amount, $reference, $token, $currency = 'AUD', $extra = null) {
        if(is_null($amount)) throw new InvalidArgumentException('Amount is a required field.');
        if(is_null($reference)) throw new InvalidArgumentException('Reference is a required field.');
        if(strlen($reference) === 0) throw new InvalidArgumentException('Reference is a required field.');
        if(is_null($token) || (strlen($token) === 0)) throw new InvalidArgumentException('Card token is a required field.');

        $customer_ip = $this->get_customer_ip();

        $int_amount = self::floatToInt($amount);

        $payload = [
            'customer_ip' => $customer_ip,
            'card_token' => $token,
            'reference' => $reference,
            'amount' => $int_amount,
            'currency' => $currency,
            'capture' => false
        ];

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request('POST', '/purchases', $payload);
    }


    /**
     * Performs an capture for an existing authorization
     * @param string $transaction_id the pre-auth transaction id (e.g. xxxx-P-yyyyyyyy)
     * @param float $amount the amount ot capture
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function capture($transaction_id, $amount, $extra = null) {
        if(is_null($amount)) throw new InvalidArgumentException('Amount is a required field.');
        if(is_null($transaction_id)) throw new InvalidArgumentException('Transaction ID is a required field.');

        $int_amount = self::floatToInt($amount);

        $payload = ['amount' => $int_amount];

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request('POST', '/purchases/' . $transaction_id . '/capture', $payload);
    }

    /**
     * Voids a purchase or authorization which was processed against the bank
     * @param string $transaction_id the transaction to void's id (e.g. xxxx-P-yyyyyyyy)
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function void($transaction_id, $extra = null) {
        $payload = [];

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request('POST', '/purchases/void?id=' . $transaction_id, $payload);
    }

    /**
     * Performs a refund against the FatZebra gateway
     * @param string $transaction_id the original transaction ID to be refunded
     * @param float $amount the amount to be refunded
     * @param string $reference the refund reference
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function refund($transaction_id, $amount, $reference, $extra = null) {
        if(is_null($transaction_id) || strlen($transaction_id) === 0) throw new InvalidArgumentException('Transaction ID is required');
        if(is_null($amount) || strlen($amount) === 0) throw new InvalidArgumentException('Amount is required');
        if(intval($amount) < 1) throw new InvalidArgumentException('Amount is invalid - must be a positive value');
        if(is_null($reference) || strlen($reference) === 0) throw new InvalidArgumentException('Reference is required');

        $int_amount = self::floatToInt($amount);

        $payload = [
            'transaction_id' => $transaction_id,
            'amount' => $int_amount,
            'reference' => $reference
        ];

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request('POST', '/refunds', $payload);

    }

    /**
     * Retrieves a purchase from the FatZebra gateway
     * @param string $reference the purchase ID
     * @return \StdObject
     */
    public function get_purchase($id) {
        if (is_null($id) || strlen($id) === 0) throw new InvalidArgumentException('id is required');
        return $this->do_request('GET', '/purchases/' . $id);
    }

    /**
     * Retrieves a refund from the FatZebra gateway
     * @param string $reference the refund ID
     * @return \StdObject
     */
    public function get_refund($id) {
        if (is_null($id) || strlen($id) === 0) throw new InvalidArgumentException('id is required');
        return $this->do_request('GET', '/refunds/' . $id);
    }

	/**
	 * Create a new direct debit
	 * @param string $bsb
	 * @param string $account_name
	 * @param string $account_number
	 * @param float $amount
	 * @param string $description
	 * @param string|null $reference
	 * @return \StdObject
	 * @throws TimeoutException
	 */
	public function create_direct_debit($bsb, $account_name, $account_number, $amount, $description, $reference) {
		if(is_null($bsb) || (strlen($bsb) === 0)) throw new InvalidArgumentException('BSB is a required field.');
		if(is_null($account_name) || (strlen($account_name) === 0)) throw new InvalidArgumentException('Account Name is a required field.');
		if(is_null($account_number) || (strlen($account_name) === 0)) throw new InvalidArgumentException('Account Number is a required field.');
		if(is_null($amount) || ($amount === 0)) throw new InvalidArgumentException('Amount is a required field.');
		if(is_null($description) || (strlen($description) === 0)) throw new InvalidArgumentException('Description is a required field.');

		$customer_ip = $this->get_customer_ip();

		$payload = [
			'customer_ip' => $customer_ip,
			'description'=> $description,
			'amount'=> $amount,
			'bsb'=> $bsb,
			'account_name'=> $account_name,
			'account_number'=>$account_number,
			'reference'=> $reference
		];
		return $this->do_request('POST', '/direct_debits', $payload);
	}

	/**
	 * Get a direct debit
	 * @param $id
	 * @return \StdObject
	 * @throws TimeoutException
	 */
	public function get_direct_debit($id) {
		if(is_null($id) || (strlen($id) === 0)) throw new InvalidArgumentException('ID is a required field.');
		return $this->do_request('GET', '/direct_debits/'.$id);
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
        if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new InvalidArgumentException('Card Holder is a required field.');
        if(is_null($card_number) || (strlen($card_number) === 0)) throw new InvalidArgumentException('Card Number is a required field.');
        if(is_null($expiry_date)) throw new InvalidArgumentException('Expiry is a required field.');
        if(is_null($cvv)) throw new InvalidArgumentException('CVV is a required field.');

        $customer_ip = $this->get_customer_ip();

        $payload = [
            'customer_ip' => $customer_ip,
            'card_holder' => $card_holder,
            'card_number' => $card_number,
            'card_expiry' => $expiry_date,
            'cvv' => $cvv
        ];
        return $this->do_request('POST', '/credit_cards', $payload);
    }

    /**
     * Fetch the details of a previously tokenized credit card
     * @param string $token the card token
     * @return \StdObject
     */
    public function get_tokenized_card($token) {
        if(is_null($token) || (strlen($token) === 0)) throw new InvalidArgumentException('Token is a required field.');
        return $this->do_request('GET', '/credit_cards/'.$token);
    }

	/**
	 * Update a tokenized card
	 * @param string $token the card token
	 * @param string $expiry_date the card expiry date (mm/yyyy format)
	 * @param string $alias alias to associate with the card
	 * @return \StdObject
	 */
    public function update_tokenized_card($token, $expiry_date=null, $alias=null) {
		if(is_null($token) || (strlen($token) === 0)) throw new InvalidArgumentException('Token is a required field.');

		$payload = [];

		if (!is_null($expiry_date)) {
			$payload['card_expiry'] = $expiry_date;
		}

		if (!is_null($alias)) {
			$payload['alias'] = $alias;
		}

		return $this->do_request('PUT', '/credit_cards/'.$token, $payload);
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
        if(is_null($first_name) || (strlen($first_name) === 0)) throw new InvalidArgumentException('First name is a required field.');
        if(is_null($last_name) || (strlen($last_name) === 0)) throw new InvalidArgumentException('Last name is a required field.');
        if(is_null($email) || (strlen($email) === 0)) throw new InvalidArgumentException('Email is a required field.');
        if(is_null($reference) || (strlen($reference) === 0)) throw new InvalidArgumentException('Reference is a required field.');

        if(is_null($card_holder) || (strlen($card_holder) === 0)) throw new InvalidArgumentException('Card Holder is a required field.');
        if(is_null($card_number) || (strlen($card_number) === 0)) throw new InvalidArgumentException('Card Number is a required field.');
        if(is_null($card_expiry)) throw new InvalidArgumentException('Expiry is a required field.');
        if(is_null($cvv)) throw new InvalidArgumentException('CVV is a required field.');

        $payload = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'reference' => $reference,
            'email' => $email,
            'card' => [
                'card_holder' => $card_holder,
                'card_number' => $card_number,
                'expiry_date' => $card_expiry,
                'cvv' => $cvv
            ]
        ];

        return $this->do_request('POST', '/customers', $payload);
    }

    /************** Private functions ***************/

    /**
     * Performs the request against the Fat Zebra gateway
     * @param string $method the request method ('POST' or 'GET')
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
            $url = $this->url . '/v' . $this->api_version . $uri;
            curl_setopt($curl, CURLOPT_URL, $url);
        }

        $payload['test'] = $this->test_mode;

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->username .':'. $this->token);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['User-agent: FatZebra PHP Library ' . $this->version]);

        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json', 'User-agent: FatZebra PHP Library ' . $this->version]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        if ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6); // CURLOPT_SSLVERSION_TLSv1_2
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $data = curl_exec($curl);

        if (curl_errno($curl) !== 0) {
            if (curl_errno($curl) == 28) throw new TimeoutException('cURL Timeout: ' . curl_error($curl));
            throw new \Exception('cURL error ' . curl_errno($curl) . ': ' . curl_error($curl));
        }
        curl_close($curl);

        $response =  json_decode($data);
        if (is_null($response)) {
            $err = json_last_error();
            if ($err == JSON_ERROR_SYNTAX) {
                throw new \Exception('JSON Syntax error. JSON attempted to parse: ' . $data);
            } elseif ($err == JSON_ERROR_UTF8) {
                throw new \Exception('JSON Data invalid - Malformed UTF-8 characters. Data: ' . $data);
            } else {
                throw new \Exception('JSON parse failed. Unknown error. Data: ' . $data);
            }
        }

        return $response;
    }

    /**
     * Get the currently set customer ip or fetches the customers 'real' IP
     * address (i.e. pulls out the address from X-Forwarded-For if present)
     *
     * @return String the customers IP address
     */
    private function get_customer_ip() {
        if (!$this->customer_ip) {
            $this->customer_ip = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwarded_ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $this->customer_ip = $forwarded_ips[0];
            }
        }
        return $this->customer_ip;
    }

    /**
     * Allows explicitly setting the customer's IP address to be sent along with some requests.
     *
     * @return String the customers IP address
     */
    public function set_customer_ip($customer_ip) {
        $this->customer_ip = $customer_ip;
    }

    /**
     * Convert a float to the integer value, using BCMul if available.
     * If BCMul is not available use the two-line cast method to avoid floating point precision issues
     *
     * @param float $input the input value
     * @return int the integer value of the conversion
     */
    static private function floatToInt($input) {
        if (function_exists('bcmul')) {
            return intval(bcmul($input, 100));
        } else {
            $multiplied = round($input * 100);
            return (int)$multiplied;
        }

    }
}
