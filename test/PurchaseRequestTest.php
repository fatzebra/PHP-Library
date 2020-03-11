<?php
# vim: ts=4 sw=4 sts=4 noet

use function PHPUnit\Framework\assertEquals;

include "vendor/autoload.php";

class PurchaseRequestTest extends PHPUnit\Framework\TestCase
{
	public function test_error_checking_amount()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$cc_expiry_year  = (string) ((int) date("Y") + 2);
		$response = $gw->purchase(-100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/" . $cc_expiry_year, 123);
		assertEquals("Amount must be greater than or equal to 0", $response->errors[0]);
	}

	public function test_invalid_expiry()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$response = $gw->purchase(-100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", 123);
		assertEquals("Expiry date is invalid (expired)", $response->errors[0]);
	}

	public function test_error_checking_amount_missing()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(null, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", 123);
	}

	public function test_error_checking_reference()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(100.00, null, "Jim Smith", "5123456789012346", "05/2013", 123);
	}

	public function test_error_checking_reference_blank()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(100.00, "", "Jim Smith", "5123456789012346", "05/2013", 123);
	}

	public function test_error_checking_name()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(100.00, "UNITTEST", "", "5123456789012346", "05/2013", 123);
	}

	public function test_error_checking_card()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(100.00, "UNITTEST", "Jim Smith", "", "05/2013", 123);
	}

	public function test_error_checking_expiry()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(100.00, "UNITTEST", "Jim Smith", "5123456789012346", null, 123);
	}

	public function test_error_checking_cvv()
	{
		$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
		$gw->timeout = 30;
		$this->expectException(InvalidArgumentException::class);
		$response = $gw->purchase(100.00, "UNITTEST", "Jim Smith", "5123456789012346", "05/2013", null);
	}
}
