<?php

use Allpaypayz\Exception\AllpaypayzException;
use Allpaypayz\Allpaypayz as AllpaypayzClient;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// CS-Cart invokes this file with $processor_data + $order_info in scope.
// Refer to fn_run_payment_processor_data() in fn.payment.php.

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (defined('PAYMENT_NOTIFICATION')) {
    // Webhook callback path. CS-Cart invokes this file with $_REQUEST set;
    // we read the raw HTTP body for the signed payload.
    $signKey = (string) ($processor_data['processor_params']['sign_key'] ?? '');
    if ($signKey === '') {
        http_response_code(500);
        exit('sign_key_unconfigured');
    }
    $rawBody = file_get_contents('php://input') ?: '';
    $sigHeader = $_SERVER['HTTP_CALLBACK_SIGNATURE'] ?? '';
    try {
        $event = \Allpaypayz\Webhooks::verify(
            rawBody: $rawBody, signatureHeader: $sigHeader, signKey: $signKey,
        );
    } catch (\Allpaypayz\Exception\WebhookException $e) {
        http_response_code(400);
        exit($e->errorCode);
    }
    $resource = $event['resource'] ?? null;
    $reference = is_array($resource) ? ($resource['merchant_reference'] ?? null) : null;
    if (is_string($reference) && preg_match('/^CS-(\d+)$/', $reference, $m)) {
        $orderId = (int) $m[1];
        $type = (string) ($event['type'] ?? '');
        if (in_array($type, ['payment.succeeded', 'order.completed'], true)) {
            fn_change_order_status($orderId, 'P');
        } elseif (in_array($type, ['payment.failed', 'payment.cancelled', 'order.cancelled', 'order.expired'], true)) {
            fn_change_order_status($orderId, 'F');
        }
    }
    header('Content-Type: application/json');
    echo '{}';
    exit;
}

// Otherwise — this is the checkout-time invocation. CS-Cart wants us to
// either render a hidden form or initiate a redirect.

$apiKey = (string) ($processor_data['processor_params']['api_key'] ?? '');
$baseUrl = (string) ($processor_data['processor_params']['base_url'] ?? 'https://api4.allpaypayz.com');
$paymentMethod = (string) ($processor_data['processor_params']['payment_method'] ?? 'card');

$client = new AllpaypayzClient(apiKey: $apiKey, baseUrl: $baseUrl);
try {
    $payment = $client->payments->createRedirect([
        'merchant_reference' => 'CS-' . $order_info['order_id'],
        'amount' => [
            'amount_minor' => (int) round((float) $order_info['total'] * 100),
            'currency'     => $order_info['secondary_currency'] ?? CART_PRIMARY_CURRENCY,
        ],
        'description'    => 'CS-Cart order #' . $order_info['order_id'],
        'payment_method' => $paymentMethod,
        'customer' => [
            'name'  => trim(($order_info['firstname'] ?? '') . ' ' . ($order_info['lastname'] ?? '')),
            'email' => (string) ($order_info['email'] ?? ''),
            'phone' => (string) ($order_info['phone'] ?? ''),
        ],
        'urls' => [
            'success'  => fn_url('checkout.complete?order_id=' . $order_info['order_id'], AREA, 'http'),
            'error'    => fn_url('checkout.cart', AREA, 'http'),
            'callback' => fn_url('payment_notification.notify?payment=allpaypayz', AREA, 'http'),
        ],
        'extra_data' => ['cscart_order_id' => (string) $order_info['order_id']],
    ]);
} catch (AllpaypayzException $e) {
    fn_set_notification('E', __('error'), 'Allpaypayz: ' . $e->errorCode);
    fn_redirect(fn_url('checkout.cart'));
    return;
}

$checkoutUrl = $payment['checkout_url'] ?? null;
if (!is_string($checkoutUrl)) {
    fn_set_notification('E', __('error'), 'Allpaypayz: missing checkout_url');
    fn_redirect(fn_url('checkout.cart'));
    return;
}
fn_redirect($checkoutUrl);
