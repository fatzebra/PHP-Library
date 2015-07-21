<?php
  session_start();
  include_once("../FatZebra.class.php");
  define("USERNAME", "havanaco");
  define("TOKEN", "673bb3aaca9a1961bfa3c61917594dc7c4a00b71");
  define("TEST_MODE", true);

  try {
  	$gateway = new FatZebra\Gateway(USERNAME, TOKEN, TEST_MODE);
  	$purchase_request = new FatZebra\PurchaseRequest($_POST['amount'], $_POST['reference'], $_POST['name'], $_POST['card_number'], $_POST['card_expiry_month'] ."/". $_POST['card_expiry_year'], $_POST['card_cvv']);

  	$response = $gateway->purchase($purchase_request);

  	$_SESSION['response'] = $response;
  	header("Location: index.php");
	} catch(Exception $ex) {
		print "Error: " . $ex->getMessage();
	}
?>
