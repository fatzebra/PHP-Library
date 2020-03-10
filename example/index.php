<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Fat Zebra Example Application</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css"/>
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
    <div class="clear"></div>
    <div class="well">
        <a href="create_direct_debit.php" type="submit" class="btn-large btn-primary">
            Create Direct Debit and Fetch Direct Debit
        </a>
        <hr>
        <a href="tokenize_credit_card.php" type="submit" class="btn-large btn-primary">
            Tokenize Credit Card and Purchase
        </a>
    </div>
</div>
</body>
</html>
