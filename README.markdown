PHP API Library for Fat Zebra
=============================

Further (better) readme content coming soon - I promise!

General Usage:

 1. Include the FatZebra.class.php file into your sources.
 2. Create a new Gateway object, and pass it your username, token, and true or false for using test mode.
 3. Create a purchase request.
 4. Call the purchase() method on the gateway object.
 5. Examine the result.

More details to follow (structure of request/response objects etc).

**Note:** The cacert.pem file needs to be placed in the same location as the FatZebra.class.php file to ensure certificate verification.
The calls to the gateway will fail without this.

Documentation for the Fat Zebra API can be found at http://docs.fatzebra.com.au

As always, patches, pull requests, comments, issues etc welcome.

For support please visit https://www.fatzebra.com.au/help or email support@fatzebra.com.au
