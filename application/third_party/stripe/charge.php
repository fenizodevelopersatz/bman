<?php
require_once('vendor/autoload.php'); 

\Stripe\Stripe::setApiKey(''); // Replace with your Stripe secret key

$token = $_POST['stripeToken'];

try {

  $charge = \Stripe\Charge::create([
    'amount' => 1000, 
    'currency' => 'usd',
    'description' => 'Example charge',
    'source' => $token,
  ]);

  echo 'Payment successful';
} catch (\Stripe\Exception\CardException $e) {
  echo 'Card error: ' . $e->getError()->message;
} catch (\Stripe\Exception\RateLimitException $e) {
  echo 'Rate limit exceeded';
} catch (\Stripe\Exception\InvalidRequestException $e) {
  echo 'Invalid request';
} catch (\Stripe\Exception\AuthenticationException $e) {
  echo 'Authentication failed';
} catch (\Stripe\Exception\ApiConnectionException $e) {
  echo 'Network communication failed';
} catch (\Stripe\Exception\ApiErrorException $e) {
  echo 'An error occurred';
} catch (Exception $e) {
  echo 'An error occurred';
}
?>
