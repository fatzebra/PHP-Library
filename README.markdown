PHP API Library for Fat Zebra
==============================

Release 1.0.0 for API version 1.1

A PHP library for the [Fat Zebra](https://www.fatzebra.com.au) Online Payment Gateway (for Australian Merchants)
Now supports recurring billing (subscriptions, plans, customers)

Dependencies
------------

 * PHP (Tested on version 5.3)
 * cURL

 [![Build Status](https://secure.travis-ci.org/fatzebra/PHP-Library.png?branch=master)](http://travis-ci.org/fatzebra/PHP-Library)

Installing
----------

Copy the files FatZebra.class.php and cacert.pem to your project lib folder (or similar)

**Note:** The cacert.pem file needs to be placed in the same location as the FatZebra.class.php file to ensure certificate verification. The calls to the gateway will fail without this.

Usage
-----

*Regular Purchase*

```php
<?php
  session_start();
  include_once("../FatZebra.class.php");
  define("USERNAME", "havanaco");
  define("TOKEN", "673bb3aaca9a1961bfa3c61917594dc7c4a00b71");
  define("TEST_MODE", true);

  try {
  	$gateway = new FatZebra\Gateway(USERNAME, TOKEN, TEST_MODE);
  	$purchase_request = new FatZebra\PurchaseRequest($_POST['amount'], $_POST['reference'], $_POST['name'], $_POST['card_number'], $_POST['card_expiry_month'] ."/". $_POST['card_expiry_month'], $_POST['card_cvv']);

  	$response = $gateway->purchase($purchase_request);

  	$_SESSION['response'] = $response;
  	header("Location: index.php");
	} catch(Exception $ex) {
		print "Error: " . $ex->getMessage();
	}
?>
```

*Token Purchase*
```php
<?php
  session_start();
  include_once("../FatZebra.class.php");
  define("USERNAME", "havanaco");
  define("TOKEN", "673bb3aaca9a1961bfa3c61917594dc7c4a00b71");
  define("TEST_MODE", true);

  $amount = 100;
  $reference = "your ref";

  try {
    $gateway = new FatZebra\Gateway(USERNAME, TOKEN, TEST_MODE);
    $response = $gateway->token_purchase($_POST['token'], $amount, $reference);

    $_SESSION['response'] = $response;
    header("Location: index.php");
  } catch(Exception $ex) {
    print "Error: " . $ex->getMessage();
  }
?>
```

See the example folder for this example tied into a website.

Documentation
-------------

Full API reference can be found at http://docs.fatzebra.com.au

Support
-------
If you have any issue with the Fat Zebra PHP Client please contact us at support@fatzebra.com.au and we will be more then happy to help out. Alternatively you may raise an issue in github.

Pull Requests
-------------
If you would like to contribute to the plugin please fork the project, make your changes within a feature branch and then submit a pull request. All pull requests will be reviewed as soon as possible and integrated into the main branch if deemed suitable.
