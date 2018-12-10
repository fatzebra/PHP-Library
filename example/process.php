<?php
require_once(__DIR__ . '/../vendor/autoload.php');
session_start();
use FatZebra\Gateway;

define("USERNAME", "havanaco");
define("TOKEN", "673bb3aaca9a1961bfa3c61917594dc7c4a00b71");
define("TEST_MODE", true);

try {
	$gateway = new Gateway(USERNAME, TOKEN, TEST_MODE);

	$token_response = $gateway->tokenize(
		$_POST['name'],
		$_POST['card_number'],
		$_POST['card_expiry_month'] ."/". $_POST['card_expiry_year'],
		$_POST['card_cvv']
	);

	$token = $token_response->response->token;

	$purchase_response = $gateway->token_purchase(
		$token,
		$_POST['amount'],
		$_POST['reference']
	);

	$_SESSION['response'] = $purchase_response;
	header("Location: index.php");
} catch(Exception $ex) {
	print "Error: " . $ex->getMessage();
}
