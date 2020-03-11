PHP API Library for Fat Zebra
==============================

Release 2.2.0 for API version 1.0

A PHP library for the [Fat Zebra](https://www.fatzebra.com.au) Online Payment Gateway (for Australian Merchants)
Now supports recurring billing (subscriptions, plans, customers)

Dependencies
------------

 * PHP (Tested on versions; `5.4`, `5.6`, `7.0`, `7.1`, `7.2`, `7.3`)
 * cURL

 [![Build Status](https://travis-ci.org/JumboInteractiveLimited/fatzebra.svg?branch=master)](http://travis-ci.org/JumboInteractiveLimited/fatzebra)

Installing
----------

Copy the `src` dir to your project lib folder (or similar) or require this project with composer.

Usage
-----

*Token Purchase*
```php
<?php
  session_start();
  include_once("src/Gateway.php");
  define("USERNAME", "havanaco");
  define("TOKEN", "673bb3aaca9a1961bfa3c61917594dc7c4a00b71");
  define("TEST_MODE", true);

  $amount = 100;
  $reference = "your ref";

  try {
    $gateway = new \FatZebra\Gateway(USERNAME, TOKEN, TEST_MODE);
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
