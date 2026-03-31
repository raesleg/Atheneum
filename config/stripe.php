<?php
require_once __DIR__ . '/../vendor/autoload.php';

$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
$stripeWebhookSecret = getenv('STRIPE_WEBHOOK_SECRET');

if (!$stripeSecretKey) {
    throw new Exception('Missing Stripe secret key.');
}

\Stripe\Stripe::setApiKey($stripeSecretKey);