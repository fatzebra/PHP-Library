<?php
/**
 * @package Gateway Tests
 * vim: ts=4 sw=4 sts=4 noet
 */

use FatZebra\Gateway;

class GatewayTests extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}

	/**
	 * Test a valid purchase
	 */
	public function test_valid_transaction() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);
		$result = $gw->purchase(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2023", 123);
		$this->assertTrue($result->successful);
		$this->assertTrue($result->response->successful);
		$this->assertEquals($result->response->message, "Approved");
	}

	/**
	 * Test a declining purchase
	 */
	public function test_failing_transaction() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->purchase(100.05, "UNITTEST" . rand(), "Jim Smith", "5555555555554444", "05/2023", 123);

		$this->assertTrue($result->successful);
		$this->assertFalse($result->response->successful);
		$this->assertEquals($result->response->message, "Refer to Card Issuer");
	}

	/**
	 * Test an auth.
	 */
	public function test_valid_auth() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->authorization(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2023", 123);

		$this->assertTrue($result->successful);
		$this->assertTrue($result->response->successful);
		$this->assertFalse($result->response->captured);
		$this->assertEquals($result->response->message, "Approved");
	}

	/**
	 * Test aa void.
	 */
	public function test_void() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->authorization(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2023", 123);
		$voidResult = $gw->void($result->response->id);
		$this->assertTrue($voidResult->successful);
		$this->assertEquals($voidResult->response->message, "Voided");
	}

	/**
	 * Test a purchase with an invalid card number
	 */
	public function test_failing_transaction_invalid_card() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->purchase(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012345", "05/2023", 123);

		$this->assertFalse($result->successful);
		$this->assertFalse($result->response->successful);
		$this->assertEquals($result->errors[0], "Card number is invalid");
	}

	/**
	 * Test fetching a purchase
	 */
	public function test_fetch_valid_transaction() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->purchase(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2023", 123);

		$purch = $gw->get_purchase($result->response->id);
		$this->assertTrue($purch->successful);
		$this->assertTrue($purch->response->successful);
		$this->assertEquals($purch->response->message, "Approved");
	}

	/**
	 * Test fetching an invalid purchase
	 */
	public function test_fetch_invalid_transaction() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$purch = $gw->get_purchase("12345");
		$this->assertFalse($purch->successful);
		$this->assertEquals($purch->errors[0], "Could not find Purchase");
	}

	/**
	 * Test a refund
	 */
	public function test_refund() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->purchase(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2023", 123);

		$refund_result = $gw->refund($result->response->id, 50.00, "UNITTEST" . rand());

		$this->assertTrue($result->successful);
		$this->assertTrue($result->response->successful);
	}

	/**
	 * Test refunding with an invalid transaction ID
	 */
	public function test_invalid_refund() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->refund("12345", 100.00, "ERRORTEST");

		$this->assertFalse($result->successful);
		$this->assertEquals($result->errors[0], "Original transaction could not be found");
	}

	/**
	 * Test fetching a refund
	 */
	public function test_fetch_refund() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->purchase(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2023", 123);

		$refund_result = $gw->refund($result->response->id, 50.00, "UNITTEST" . rand());
		$fetch_result = $gw->get_refund($refund_result->response->id);

		$this->assertTrue($fetch_result->successful);
		$this->assertTrue($fetch_result->response->successful);
	}

	/**
	 * Test create a direct debit
	 */
	public function test_direct_debit() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->direct_debit("123-123","Billy Blanks", "012345678", "7", "test deposit");

		$this->assertTrue($result->successful);
		$this->assertEquals($result->response->bsb, "123-123");
		$this->assertEquals($result->response->account_name, "Billy Blanks");
		$this->assertEquals($result->response->account_number, "012345678");
		$this->assertEquals($result->response->amount, "7.0");
		$this->assertEquals($result->response->description, "test deposit");
	}

	/**
	 * Test tokenizing a credit card
	 */
	public function test_tokenization() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2023", "123");

		$this->assertTrue($result->successful);
		$this->assertEquals($result->response->card_holder, "Billy Blanks");
		$this->assertEquals($result->response->card_number, "512345XXXXXX2346");
	}

	/**
	 * Test tokenizing an invalid card
	 */
	public function test_failing_tokenization() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->tokenize("Billy Blanks", "5123456789012345", "05/2023", "123");

		$this->assertFalse($result->successful);
		$this->assertEquals($result->errors[0], "Card number is invalid");
	}

	/**
	 * Testing fetching a previously tokenized card
	 */
	public function test_get_tokenized_card() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$card_holder = "Joe Bloggs";
		$card_number = "5123456789012346";
		$expected_card_number = "512345XXXXXX2346";

		$expiry_date = "05/2023";
		$expected_expiry_date = "2023-05-31";

		$result = $gw->tokenize($card_holder, $card_number, $expiry_date, "123");

		$this->assertTrue($result->successful);
		$this->assertNotNull($result->response->token);

		$token = $result->response->token;
		$result = $gw->get_tokenized_card($token);

		$this->assertTrue($result->successful);
		$this->assertEquals($result->response->card_holder, $card_holder);
		$this->assertEquals($result->response->card_number, $expected_card_number);
		$this->assertEquals($result->response->card_expiry, $expected_expiry_date);
	}

	/**
	 * Testing fail fetching a previously tokenized card using invalid token
	 */
	public function test_failed_get_tokenized_card() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->get_tokenized_card("invalid token");

		$this->assertFalse($result->successful);
		$this->assertEquals($result->errors[0], "Record not found");
	}

	/**
	 * Testing updating a previously tokenized card
	 */
	public function test_update_tokenized_card() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$card_holder = "Joanne Bloggs";
		$card_number = "5123456789012346";
		$expiry_date = "05/2024";
		$expected_expiry_date = "2024-05-31";

		$result = $gw->tokenize($card_holder, $card_number, $expiry_date, "123");

		$this->assertTrue($result->successful);
		$this->assertNotNull($result->response->token);

		$token = $result->response->token;
		$result = $gw->get_tokenized_card($token);

		$this->assertTrue($result->successful);
		$this->assertEquals($result->response->card_expiry, $expected_expiry_date);

		$alias = "fz_test";
		$new_expiry_date = "11/2025";
		$expected_new_expiry_date = "2025-11-30";

		$result = $gw->update_tokenized_card($token, $new_expiry_date, $alias);

		$this->assertTrue($result->successful);

		$token = $result->response->token;
		$result = $gw->get_tokenized_card($token);

		$this->assertTrue($result->successful);
		$this->assertEquals($result->response->card_expiry, $expected_new_expiry_date);
		$this->assertEquals($result->response->alias, $alias);
	}

	/**
	 * Testing a token purchase
	 */
	public function test_purchase_with_token() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2023", "123");

		$result = $gw->token_purchase($card->response->token, 100.00, "UNITTEST" . rand(), 123);

		$this->assertTrue($result->successful);
		$this->assertTrue($result->response->successful);
		$this->assertEquals($result->response->message, "Approved");
	}

	/**
	 * Test a token purchase without a CVV
	 */
	public function test_purchase_with_token_no_cvv() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2023", 123);

		$result = $gw->token_purchase($card->response->token, 100.00, "UNITTEST" . rand());

		$this->assertTrue($result->successful);
		$this->assertTrue($result->response->successful);
		$this->assertEquals($result->response->message, "Approved");
	}

	/**
	 * Test a token purchase with an invalid token
	 */
	public function test_purchase_with_invalid_token() {
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->token_purchase("TOK123", 100.00, "UNITTEST" . rand());

		$this->assertFalse($result->successful);
		$this->assertEquals($result->errors[0], "Card TOK123 could not be found");
	}

	/**
	 * Test creating a customer
	 */
	public function test_create_customer() {
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
		$gw = new FatZebra\Gateway("TEST", "TEST", true);
		$gw->set_timeout(30);

		$result = $gw->create_customer("Mark", "Smith", "mark" . rand() . "@smith.com", "UNITTEST" . rand(), "Mark Smith", "5123456789012346", "05/2023", 123);

		$this->assertTrue($result->successful);
		$this->assertNotNull($result->response->id);
	}

	/**
	 * Testing a token authorization
	 */
	public function test_token_authorization() {
	 $gw = new FatZebra\Gateway("TEST", "TEST", true);
	 $gw->set_timeout(30);

	 $card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2023", "123");

	 $result = $gw->token_authorization(100.00, "UNITTEST" . rand(), $card->response->token);

	 $this->assertTrue($result->successful);
	 $this->assertTrue($result->response->successful);
	 $this->assertEquals($result->response->message, "Approved");
	}
}

?>
