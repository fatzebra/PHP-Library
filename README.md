PHP API Library for Fat Zebra
==============================

Release 2.0.0 for API version 1.0

A PHP library for the [Fat Zebra](https://www.fatzebra.com.au) Online Payment Gateway (for Australian Merchants)
Now supports recurring billing (subscriptions, plans, customers)

Dependencies
------------

 * PHP (Tested on versions; `5.4`, `5.6`, `7.0`, `7.1`, `7.2`)
 * cURL

 [![Build Status](https://secure.travis-ci.org/fatzebra/PHP-Library.png?branch=master)](http://travis-ci.org/fatzebra/PHP-Library)

Installing
----------

Copy the files FatZebra.class.php and ca-bundle.pem to your project lib folder (or similar)

**Note:** The ca-bundle.pem file needs to be placed in the same location as the FatZebra.class.php file to ensure certificate verification. The calls to the gateway will likely fail without this, however if you have taken steps to ensure this will work (such as including the bundle in your OS root trust store) you may use the `set_ca_bundle` method to define an empty string for the OS root trust store or the path to another bundle.

If you wish to use a minimal CA bundle you can copy the `ca-bundle-minimal.pem` file to the same directory, but rename it to `ca-bundle.pem`.

Usage
-----

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
    $response = $gateway->token_purchase($_POST['token'], $amount, $reference, null, 'AUD');

    $_SESSION['response'] = $response;
    header("Location: index.php");
  } catch(Exception $ex) {
    print "Error: " . $ex->getMessage();
  }
?>
```

See the example folder for this example tied into a website.

Example
-------

From within git repo you may run `docker-compose up -d example` and `docker-compose down` to start and stop (respectively) an apache web server running the php example.

Once up, you can access the example website at http://localhost:8080 URL to give it a go.

Testing
-------

All tests can be run in a container by executing `docker-compose run --rm tests` from withing the git repo. After making file changes you will need to run `docker-compose build tests` so they are taken into account next test run.

Documentation
-------------

Full API reference can be found at http://docs.fatzebra.com.au

Support
-------
If you have any issue with the Fat Zebra PHP Client please contact us at support@fatzebra.com.au and we will be more then happy to help out. Alternatively you may raise an issue in github.

Pull Requests
-------------
If you would like to contribute to the plugin please fork the project, make your changes within a feature branch and then submit a pull request. All pull requests will be reviewed as soon as possible and integrated into the main branch if deemed suitable.
