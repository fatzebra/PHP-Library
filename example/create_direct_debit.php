<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Fat Zebra Example Application | Direct Debit</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css"/>
</head>
<body>
<div class="container">
    <h1>Fat Zebra Example Application (PHP) </h1>

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
							<?php foreach ($r->errors as $error): ?>
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
    <h2>Create Direct Debit</h2>
    <form class="well" action="process_create_direct_debit.php" method="post">
        <label for="bsb">bsb</label>
        <input type="text" name="bsb" id="bsb" value="123-123"/>

        <label for="account_name">Account Namer</label>
        <input type="text" name="account_name" id="account_name" autocomplete="off" value="Test"/>

        <label for="account_number">Account Number</label>
        <input type="text" name="account_number" id="account_number" class="span1" value="012345678"/>

        <label for="amount">Amount</label>
        <input type="text" name="amount" id="amount" value="42.00"/>

        <label for="reference">Description</label>
        <input type="text" name="description" id="description"/>

        <div class="clear"></div>
        <button type="submit" class="btn btn-primary">
            Submit
        </button>
    </form>
    <hr>
    <h2>Get Direct Debit</h2>
    <form class="well" action="process_get_direct_debit.php" method="post">
        <label for="id">id</label>
        <input type="text" name="id" id="id" />
        <div class="clear"></div>
        <button type="submit" class="btn btn-primary">
            Submit
        </button>
    </form>
</div>
</body>
</html>
