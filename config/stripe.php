<?php
require_once __DIR__ . '/../vendor/autoload.php';

$configPath = __DIR__ . '/stripe.local.php';

if (file_exists($configPath)) {
    $config = require $configPath;
    $stripeSecretKey = $config['stripe_secret_key'] ?? null;
    $stripeWebhookSecret = $config['stripe_webhook_secret'] ?? null;
} else {
    $stripeSecretKey = getenv('STRIPE_SECRET_KEY');
    // $stripeWebhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
}

if (!$stripeSecretKey) {
    throw new Exception('Missing Stripe secret key.');
}

\Stripe\Stripe::setApiKey($stripeSecretKey);