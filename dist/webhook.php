<?php
// /dist/webhook.php
// Verifies Stripe signature and handles events (no SDK).
declare(strict_types=1);

// Set your webhook signing secret from Stripe dashboard (for this endpoint)
$WEBHOOK_SECRET = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_REPLACE_ME';

// Read payload and signature header
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

function secure_compare($a, $b) {
  if (strlen($a) !== strlen($b)) return false;
  $res = 0;
  for ($i = 0; $i < strlen($a); $i++) $res |= ord($a[$i]) ^ ord($b[$i]);
  return $res === 0;
}

function verifyStripeSignature($payload, $sigHeader, $secret, $tTolerance = 300) {
  // Stripe-Signature: t=timestamp,v1=signature,v0=...
  $parts = [];
  foreach (explode(',', $sigHeader) as $kv) {
    [$k, $v] = array_map('trim', explode('=', $kv, 2) + [null,null]);
    if ($k && $v) $parts[$k] = $v;
  }
  if (empty($parts['t']) || empty($parts['v1'])) return false;

  $signedPayload = $parts['t'] . '.' . $payload;
  $expected = hash_hmac('sha256', $signedPayload, $secret);

  // Optional: timestamp tolerance (replay protection)
  if (abs(time() - (int)$parts['t']) > $tTolerance) return false;

  return secure_compare($expected, $parts['v1']);
}

if (!$WEBHOOK_SECRET || !verifyStripeSignature($payload, $sigHeader, $WEBHOOK_SECRET)) {
  http_response_code(400);
  echo 'Bad signature';
  exit;
}

$event = json_decode($payload, true);
$type  = $event['type'] ?? '';

if ($type === 'checkout.session.completed') {
  // The session object with metadata is in $event['data']['object']
  $session = $event['data']['object'];

  // Example: write a simple log (replace with DB save / fulfillment)
  @file_put_contents(__DIR__ . '/stripe.log',
    date('c') . " session.completed id={$session['id']} email={$session['customer_details']['email']}\n",
    FILE_APPEND
  );

  // You can also fetch line items via REST:
  // GET https://api.stripe.com/v1/checkout/sessions/{SESSION_ID}/line_items
  // using your secret key (server-to-server).
}

http_response_code(200);
echo 'ok';
