<?php
	/**
	* Fat Zebra PHP Gateway Library
	* Version 1.1.5
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
		* Creates a new instance of the PurchaseRequest
		* @param float $amount the purchase amount
		* @param string $reference the reference for the purchase
		* @param string $card_holder the card holders name
		* @param string $card_number the card number
		* @param string $expiry the card expiry (mm/yyyy format)
		* @param string $cvv the card verification value
		* @return PurchaseRequest
		*/
		public function __construct($amount, $reference, $card_holder, $card_number, $expiry, $cvv, $fraud_data = null) {
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
			if (function_exists('bcmul')) {
				$int_amount = intval(bcmul($this->amount, 100));
			} else {
				$multiplied = round($amount * 100);
				$int_amount = (int)$multiplied;
			}
			
			$data = array(
				"card_holder" => $this->card_holder,
				"card_number" => $this->card_number,
				"card_expiry" => $this->expiry,
				"cvv" => $this->cvv,
				"reference" => $this->reference,
				"amount" => $int_amount
			);
			if (!is_null($this->fraud_data)) {
				$data['fraud'] = $this->fraud_data;
			}
			return $data;
		}
	}

