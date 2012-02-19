<?php
	include "FatZebra.class.php";
	class GatewayTest extends PHPUnit_Framework_TestCase {
		public function testIsTrue() {
			$this->assertTrue(true);
		}

		public function test_valid_transaction() {
			$gw = new FatZebra\Gateway("TESTbiztech", "16a31852daf418050eaf628f4fffecfccbf9571c", true);

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}

		public function test_failing_transaction() {
			// Pending
		}

		public function test_fetch_valid_transaction() {
			// Pending
		}
	}

?>