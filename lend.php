<?php

include_once('./config.php'); 
include_once('./functions.php');
include_once('./bitfinex.php');

$bfx = new Bitfinex($config['api_key'], $config['api_secret']);

$balances = $bfx->get_balances();
$available_balance = floatval($balances[3]['available']);

// Is there enough balance to lend?
if( $available_balance >= $config['minimum_balance'] )
{
	message("Lending availabe balance of $available_balance");
	
	$lendbook = $bfx->get_lendbook($config['currency']);
	$offers = $lendbook['asks'];
	
	$total_amount = 0;
	$next_rate = 0;
	$next_amount = 0;
	$check_next = FALSE;
	
	// Find the right rate
	foreach( $offers as $item )
	{	
		// Save next closest item
		if( $check_next )
		{
			$next_rate = $item['rate'];	
			$next_amount = $item['amount'];
			$check_next = FALSE;
		}
		
		$total_amount += floatval($item['amount']);
	
		// Possible the closest rate to what we want to lend
		if( $total_amount <= $config['max_total_swaps'] )
		{
			$rate = $item['rate'];
			$check_next = TRUE;
		}
	}
	
	// Current rate is too low, move closer to the next rate
	if( $next_amount <= $config['max_total_swaps'] )
	{
		$rate = $next_rate - 0.01;
	}
	
	$daily_rate = daily_rate($rate);
	
	$result = $bfx->new_offer($config['currency'], (string) $available_balance, (string) $rate, $config['period'], 'lend');
	
	// Successfully lent
	if( array_key_exists('id', $result) )
	{
		message("$available_balance {$config['currency']} lent for {$config['period']} days at daily rate of $daily_rate%. Offer id {$result['id']}.");
	}
	else
	{
		// Something went wrong
		message($result);
	}
}
else
{
	message("Balance of $available_balance {$config['currency']} is not enough to lend.");
}

?>