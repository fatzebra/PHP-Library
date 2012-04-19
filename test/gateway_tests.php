<?php
	include "FatZebra.class.php";
	// define("GW_URL", "https://gateway.sandbox.fatzebra.com.au");
	define("GW_URL", "http://fatapi.dev");
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
	}

?>