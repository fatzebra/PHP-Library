<?php

/**
 * Fat Zebra PHP Gateway Library
 * Version 1.2.1
 *
 * The original source for this library, including its tests can be found at
 * https://github.com/fatzebra/PHP-Library
 *
 * Please visit http://docs.fatzebra.com.au for details on the Fat Zebra API
 * or https://www.fatzebra.com.au/help for support.
 *
 * Patches, pull requests, issues, comments and suggestions always welcome.
 *
 * vim: ts=4 sw=4 sts=4 noet
 * @package FatZebra
 */

namespace FatZebra;

/**
 * The Fat Zebra Gateway class for interfacing with Fat Zebra
 */
class Gateway
{
    /**
     * The version of this library
     */
    public $version = "1.2.2";

    /**
     * The URL of the Fat Zebra gateway
     */
    public $url = "https://gateway.pmnts.io";

    /**
     * The sandbox URL of the Fat Zebra gateway
     */
    public $sandbox_url = "https://gateway.pmnts-sandbox.io";

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
     * The Certificate Authority bundle - Path to a bundle of certificates
     * authorities to trust or the system CA bundle if this is an empty string.
     */
    private $ca = "";

    /**
     * Creates a new instance of the Fat Zebra gateway object
     * @param string $username the username for the gateway
     * @param string $token the token for the gateway
     * @param boolean $test_mode indicates if the test mode should be used or not
     * @param string $gateway_url the URL for the Fat Zebra gateway
     * @return Gateway
     */
    public function __construct($username, $token, $test_mode = true, $gateway_url = null)
    {
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
        $this->ca = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ca-bundle.crt';
    }

    /**
     * Allows customization of the CA bundle used when connecting to the API,
     * if given an empty string the OS trust store is used instead.
     * @param string $ca_bundle path to the desired certificate authority bundle.
     */
    public function set_ca_bundle($ca_bundle)
    {
        $this->ca = $ca_bundle;
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
    public function purchase($amount, $reference, $card_holder, $card_number, $expiry, $cvv, $fraud_data = null, $currency = "AUD", $extra = null)
    {
        $customer_ip = $this->get_customer_ip();

        if (is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
        if (is_null($reference)) throw new \InvalidArgumentException("Reference is a required field.");
        if (strlen($reference) === 0) throw new \InvalidArgumentException("Reference is a required field.");
        if (is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
        if (is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
        if (is_null($expiry)) throw new \InvalidArgumentException("Expiry is a required field.");
        if (is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");

        $int_amount = self::floatToInt($amount);

        $payload = array(
            "card_holder" => $card_holder,
            "card_number" => $card_number,
            "card_expiry" => $expiry,
            "cvv" => $cvv,
            "reference" => $reference,
            "amount" => $int_amount,
            "currency" => $currency,
            "customer_ip" => $customer_ip
        );

        if (!is_null($fraud_data)) {
            $payload['fraud'] = $fraud_data;
        }

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

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
    public function token_purchase($token, $amount, $reference, $cvv = null, $currency = "AUD")
    {
        if (is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
        if (is_null($reference)) throw new \InvalidArgumentException("Reference is a required field.");
        if (strlen($reference) === 0) throw new \InvalidArgumentException("Reference is a required field.");
        if (is_null($token) || (strlen($token) === 0)) throw new \InvalidArgumentException("Card token is a required field.");

        $customer_ip = $this->get_customer_ip();

        $int_amount = self::floatToInt($amount);
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
     * Performs a purchase against the FatZebra gateway with a wallet
     * @param float $amount the purchase amount
     * @param string $reference the purchase reference
     * @param string $currency the currency code for the transaction. Defaults to AUD
     * @param array<string,string> $wallet an assoc. array of wallet params to merge into the request
     * @return \StdObject
     */
    public function wallet_purchase($amount, $reference, $wallet, $currency = "AUD")
    {
        if (is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
        if (is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is a required field.");
        if (is_null($wallet) || !is_array($wallet) || empty($wallet)) throw new \InvalidArgumentException("Wallet is a required field.");

        $customer_ip = $this->get_customer_ip();
        $int_amount = self::floatToInt($amount);
        $payload = array(
            "amount" => $int_amount,
            "reference" => $reference,
            "customer_ip" => $customer_ip,
            "currency" => $currency,
            "wallet" => $wallet,
        );
        return $this->do_request("POST", "/purchases", $payload);
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
    public function authorization($amount, $reference, $card_holder, $card_number, $expiry, $cvv, $currency = "AUD", $extra = null)
    {
        if (is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
        if (is_null($reference)) throw new \InvalidArgumentException("Reference is a required field.");
        if (strlen($reference) === 0) throw new \InvalidArgumentException("Reference is a required field.");
        if (is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
        if (is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
        if (is_null($expiry)) throw new \InvalidArgumentException("Expiry is a required field.");
        if (is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");

        $customer_ip = $this->get_customer_ip();

        $int_amount = self::floatToInt($amount);

        $payload = array(
            'customer_ip' => $customer_ip,
            'card_number' => $card_number,
            'card_holder' => $card_holder,
            'card_expiry' => $expiry,
            'cvv' => $cvv,
            'reference' => $reference,
            'amount' => $int_amount,
            'currency' => $currency,
            'capture' => false
        );

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request("POST", '/purchases', $payload);
    }

    /**
     * Performs an authorization against the FatZebra gateway with a tokenized credit card
     * @param float $amount the purchase amount
     * @param string $reference the purchase reference
     * @param string $card_token the card token or alias for the authorization
     * @param string $currency the currency code for the transaction. Defaults to AUD
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function token_authorization($amount, $reference, $card_token, $currency = "AUD", $extra = null)
    {
        if (is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
        if (is_null($reference)) throw new \InvalidArgumentException("Reference is a required field.");
        if (strlen($reference) === 0) throw new \InvalidArgumentException("Reference is a required field.");
        if (is_null($card_token) || (strlen($card_token) === 0)) throw new \InvalidArgumentException("Card token is a required field.");

        $customer_ip = $this->get_customer_ip();

        $int_amount = self::floatToInt($amount);

        $payload = array(
            'customer_ip' => $customer_ip,
            'card_token' => $card_token,
            'reference' => $this->reference,
            'amount' => $int_amount,
            'currency' => $this->currency,
            'capture' => false
        );

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request("POST", '/purchases', $payload);
    }


    /**
     * Performs an capture for an existing authorization
     * @param string $transaction_id the pre-auth transaction id (e.g. xxxx-P-yyyyyyyy)
     * @param float $amount the amount ot capture
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function capture($transaction_id, $amount, $extra = null)
    {
        if (is_null($amount)) throw new \InvalidArgumentException("Amount is a required field.");
        if (is_null($transaction_id)) throw new \InvalidArgumentException("Transaction ID is a required field.");

        $int_amount = self::floatToInt($amount);

        $payload = array('amount' => $int_amount);

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request("POST", '/purchases/' . $transaction_id . '/capture', $payload);
    }

    /**
     * Voids a purchase or authorization which was processed against the bank
     * @param string $transaction_id the transaction to void's id (e.g. xxxx-P-yyyyyyyy)
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function void($transaction_id, $extra = null)
    {
        $payload = array();

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request("POST", '/purchases/void?id=' . $transaction_id, $payload);
    }

    /**
     * Performs a refund against the FatZebra gateway
     * @param string $transaction_id the original transaction ID to be refunded
     * @param float $amount the amount to be refunded
     * @param string $reference the refund reference
     * @param array<string,string> $extra an assoc. array of extra params to merge into the request (e.g. metadata, fraud etc)
     * @return \StdObject
     */
    public function refund($transaction_id, $amount, $reference, $extra = null)
    {
        if (is_null($transaction_id) || strlen($transaction_id) === 0) throw new \InvalidArgumentException("Transaction ID is required");
        if (is_null($amount) || strlen($amount) === 0) throw new \InvalidArgumentException("Amount is required");
        if (intval($amount) < 1) throw new \InvalidArgumentException("Amount is invalid - must be a positive value");
        if (is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is required");

        $int_amount = self::floatToInt($amount);

        $payload = array(
            "transaction_id" => $transaction_id,
            "amount" => $int_amount,
            "reference" => $reference
        );

        if (is_array($extra)) {
            $payload = array_merge_recursive($payload, $extra);
        }

        return $this->do_request("POST", "/refunds", $payload);
    }

    /**
     * Retrieves a purchase from the FatZebra gateway
     * @param string $reference the purchase ID
     * @return \StdObject
     */
    public function get_purchase($reference)
    {
        if (is_null($reference) || strlen($reference) === 0) throw new \InvalidArgumentException("Reference is required");
        return $this->do_request("GET", "/purchases/" . $reference);
    }

    /**
     * Retrieves a refund from the FatZebra gateway
     * @param string $reference the refund ID
     * @return \StdObject
     */
    public function get_refund($reference)
    {
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
    public function tokenize($card_holder, $card_number, $expiry_date, $cvv)
    {
        if (is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
        if (is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
        if (is_null($expiry_date)) throw new \InvalidArgumentException("Expiry is a required field.");
        if (is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");


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
    public function create_customer($first_name, $last_name, $reference, $email, $card_holder, $card_number, $card_expiry, $cvv)
    {
        if (is_null($first_name) || (strlen($first_name) === 0)) throw new \InvalidArgumentException("First name is a required field.");
        if (is_null($last_name) || (strlen($last_name) === 0)) throw new \InvalidArgumentException("Last name is a required field.");
        if (is_null($email) || (strlen($email) === 0)) throw new \InvalidArgumentException("Email is a required field.");
        if (is_null($reference) || (strlen($reference) === 0)) throw new \InvalidArgumentException("Reference is a required field.");

        if (is_null($card_holder) || (strlen($card_holder) === 0)) throw new \InvalidArgumentException("Card Holder is a required field.");
        if (is_null($card_number) || (strlen($card_number) === 0)) throw new \InvalidArgumentException("Card Number is a required field.");
        if (is_null($card_expiry)) throw new \InvalidArgumentException("Expiry is a required field.");
        if (is_null($cvv)) throw new \InvalidArgumentException("CVV is a required field.");

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
            )
        );

        return $this->do_request("POST", "/customers", $payload);
    }

    /************** Private functions ***************/

    /**
     * Performs the request against the Fat Zebra gateway
     * @param string $method the request method ("POST" or "GET")
     * @param string $uri the request URI (e.g. /purchases, /credit_cards etc)
     * @param Array $payload the request payload (if a POST request)
     * @return \StdObject
     */
    protected function do_request($method, $uri, $payload = null)
    {
        $curl = curl_init();
        if (is_null($this->api_version)) {
            $url = $this->url . $uri;
            curl_setopt($curl, CURLOPT_URL, $url);
        } else {
            $url = $this->url . "/v" . $this->api_version . $uri;
            curl_setopt($curl, CURLOPT_URL, $url);
        }

        $payload["test"] = $this->test_mode;

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->username . ":" . $this->token);
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
        if ($this->ca != "") {
            curl_setopt($curl, CURLOPT_CAINFO, $this->ca);
        }
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
    private function get_customer_ip()
    {

        $customer_ip = "UNKNOWN";
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $customer_ip = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded_ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $customer_ip = $forwarded_ips[0];
        }
        return $customer_ip;
    }

    /**
     * Convert a float to the integer value, using BCMul if available.
     * If BCMul is not available use the two-line cast method to avoid floating point precision issues
     *
     * @param float $input the input value
     * @return int the integer value of the conversion
     */
    static private function floatToInt($input)
    {
        if (function_exists('bcmul')) {
            return intval(bcmul($input, 100));
        } else {
            $multiplied = round($input * 100);
            return (int) $multiplied;
        }
    }
}
