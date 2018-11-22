<?php
# vim: ts=4 sw=4 sts=4 noet
include "FatZebra.class.php";

class PurchaseRequestTest extends PHPUnit_Framework_TestCase {
	public function testConstructor() {
		$obj = new FatZebra\PurchaseRequest(100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", 123);
	}

	public function testTo_array() {
		$obj = new FatZebra\PurchaseRequest(100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", 123);

		$data = $obj->to_array();
		$this->assertEquals("UNITTEST", $data['reference']);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_amount() {
		$obj = new FatZebra\PurchaseRequest(-100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", 123);

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_amount_missing() {
		$obj = new FatZebra\PurchaseRequest(null, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", 123);

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_reference() {
		$obj = new FatZebra\PurchaseRequest(100.00, null, "Jim Smith", "5123456789012346", "05/2013", 123);

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_reference_blank() {
		$obj = new FatZebra\PurchaseRequest(100.00, "", "Jim Smith", "5123456789012346", "05/2013", 123);

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_name() {
		$obj = new FatZebra\PurchaseRequest(100.00, "UNITTEST", "", "5123456789012346", "05/2013", 123);

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_card() {
		$obj = new FatZebra\PurchaseRequest(100.00, "UNITTEST", "Jim Smith", "", "05/2013", 123);

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_expiry() {
		$obj = new FatZebra\PurchaseRequest(100.00, "UNITTEST", "Jim Smith", "5123456789012346", null, 123);

	}



	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_error_checking_cvv() {
		$obj = new FatZebra\PurchaseRequest(100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", null);

	}
}
?>
