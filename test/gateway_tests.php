<?php
	include "FatZebra.class.php";
	define("GW_URL", "https://gateway.sandbox.fatzebra.com.au");
	
	class GatewayTest extends PHPUnit_Framework_TestCase {
		public function testIsTrue() {
			$this->assertTrue(true);
		}

		public function test_valid_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}

		public function test_failing_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$req = new FatZebra\PurchaseRequest(100.99, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertTrue($result->successful);
			$this->assertFalse($result->response->successful);
			$this->assertEquals($result->response->message, "Declined, check with issuer");	
		}

		public function test_failing_transaction_invalid_card() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012345", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertFalse($result->successful);
			$this->assertFalse($result->response->successful);
			$this->assertEquals($result->errors[0], "Card number is invalid");	
		}

		public function test_fetch_valid_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($req);

			$purch = $gw->get_purchase($result->response->id);
			$this->assertTrue($purch->successful);
			$this->assertTrue($purch->response->successful);
			$this->assertEquals($purch->response->message, "Approved");
		}

		public function test_fetch_invalid_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$purch = $gw->get_purchase("12345");
			$this->assertFalse($purch->successful);
			$this->assertEquals($purch->errors[0], "Could not find Purchase");
		}

		public function test_refund() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$purch_request = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($purch_request);

			$refund_result = $gw->refund($result->response->id, 50.00, "UNITTEST" . rand());

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
		}

		public function test_invalid_refund() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
			$result = $gw->refund("12345", 100.00, "ERRORTEST");

			$this->assertFalse($result->successful);
			$this->assertEquals($result->errors[0], "Original transaction is required");
		}

		public function test_fetch_refund() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$purch_request = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($purch_request);

			$refund_result = $gw->refund($result->response->id, 50.00, "UNITTEST" . rand());
			$fetch_result = $gw->get_refund($refund_result->response->id);
			
			$this->assertTrue($fetch_result->successful);
			$this->assertTrue($fetch_result->response->successful);
		}

		public function test_tokenization() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
			$result = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2013", "123");

			$this->assertTrue($result->successful);
			$this->assertEquals($result->response->card_holder, "Billy Blanks");
			$this->assertEquals($result->response->card_number, "XXXXXXXXXXXX2346");
		}

		public function test_failing_tokenization() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
			$result = $gw->tokenize("Billy Blanks", "5123456789012345", "05/2013", "123");

			$this->assertFalse($result->successful);
			$this->assertEquals($result->errors[0], "Card number is invalid");
		}

		public function test_purchase_with_token() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
			$card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2013", "123");

			$result = $gw->token_purchase($card->response->token, 100.00, "UNITTEST" . rand(), 123);

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}

		public function test_purchase_with_token_no_cvv() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
			$card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2013", 123);

			$result = $gw->token_purchase($card->response->token, 100.00, "UNITTEST" . rand());

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}		

		public function test_purchase_with_invalid_token() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);

			$result = $gw->token_purchase("TOK123", 100.00, "UNITTEST" . rand());

			$this->assertFalse($result->successful);
			$this->assertEquals($result->errors[0], "Card TOK123 could not be found");
		}		
	}

?>