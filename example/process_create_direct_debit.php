<?php
require_once(__DIR__ . '/../vendor/autoload.php');
session_start();
use FatZebra\Gateway;

define('USERNAME', 'havanaco');
define('TOKEN', '673bb3aaca9a1961bfa3c61917594dc7c4a00b71');
define('TEST_MODE', true);

try {
	$gateway = new Gateway(USERNAME, TOKEN, TEST_MODE);

	$direct_debit_response = $gateway->create_direct_debit(
		$_POST['bsb'],
		$_POST['account_name'],
		$_POST['account_number'],
		$_POST['amount'],
		$_POST['description']
	);
	$_SESSION['response'] = $direct_debit_response;
	header('Location: index.php');
} catch(Exception $ex) {
	print 'Error n: ' . $ex->getMessage();
}
