<?php
/**
 * Payment Gateway Webhook Listener
 * Sarkin Mota HQ
 */
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Services/PaymentService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$provider = sanitize_input($_GET['provider'] ?? 'Paystack');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? $_SERVER['HTTP_VERIF_HASH'] ?? '';
$input = file_get_contents('php://input');

try {
    $paymentService = new PaymentService($pdo);
    $result = $paymentService->handleWebhook($provider, $signature, $input);

    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Webhook processed successfully", "data" => $result]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

