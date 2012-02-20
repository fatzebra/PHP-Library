<?php session_start(); ?>
<!DOCTYPE html>
<html>
  <head>
    <title>Fat Zebra Example Application</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />
  </head>
  <body>
    <div class="container">
      <h1>Fat Zebra Example Application (PHP)</h1>

      <?php if (isset($_SESSION['response'])):
        $r = $_SESSION['response'];
       ?>
        <div class="well">
          <h2>Response</h2>
          <ul>
            <li><strong>Successful: </strong><?php echo $r->successful ?></li>
            <li>
              <strong>Errors:</strong> 
              <?php if (empty($r->errors)): ?>
                None
              <?php else: ?>
                <ul>
                  <?php foreach($r->errors as $error): ?>
                    <li><?php echo $error; ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?></li>
              <li>
                <strong>Response:</strong>
                <ul>
                  <li><strong>Successful:</strong> <?php echo $r->response->authorization; ?></li>
                  <li><strong>Authorization:</strong><?php echo $r->response->authorization; ?></li>
                  <li><strong>Transaction ID:</strong><?php echo $r->response->transaction_id; ?></li>
                  <li><strong>Message:</strong><?php echo $r->response->message; ?></li>
                  <li><strong>Reference:</strong><?php echo $r->response->reference; ?></li>
                  <li><strong>Amount:</strong><?php echo $r->response->amount; ?></li>
                </ul>
              </li>
          </ul>

          <?php unset($_SESSION['response']); ?>
        </div>
      <?php endif; ?>
      <form class="well" action="process.php" method="post">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" />
        
        <label for="card_number">Card Number</label>
        <input type="text" name="card_number" id="card_number" autocomplete="off" />
        
        <label for="card_expiry_month">Expiry</label>
        <input type="text" name="card_expiry_month" id="card_expiry_month" class="span1" />
        /
        <input type="text" name="card_expiry_year" id="card_expiry_year" class="span1" />
        
        <label for="cvv">CVV</label>
        <input type="text" name="card_cvv" id="card_cvv" class="span1" />

        <label for="reference">Reference</label>
        <input type="text" name="reference" id="reference" />

        <label for="amount">Amount</label>
        <input type="text" name="amount" id="amount" value="100.00" />

        <div class="clear"></div>
        <button type="submit" class="btn btn-primary">
          Submit
        </button>
      </form>
    </div>
  </body>
</html>
