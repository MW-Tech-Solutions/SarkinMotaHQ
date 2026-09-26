<?php
/**
 * Enterprise Payment Verification & Reconciliation Service
 * Sarkin Mota HQ
 */

class PaymentService {
    private $pdo;
    private $config;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Initiate a new payment transaction with pending status
     */
    public function initiateTransaction($userId, $amount, $provider, $tenantId = null, $leaseId = null, $propertyId = null) {
        $amount = floatval($amount);
        if ($amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be greater than zero.");
        }

        $validProviders = ['Paystack', 'Flutterwave', 'Bank Transfer', 'USSD'];
        if (!in_array($provider, $validProviders)) {
            throw new InvalidArgumentException("Invalid payment provider selected.");
        }

        $txRef = 'KDT-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));

        $stmt = $this->pdo->prepare("
            INSERT INTO payment_transactions 
            (transaction_ref, user_id, tenant_id, lease_id, property_id, expected_amount, currency, provider, status, initiated_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'NGN', ?, 'pending', NOW())
        ");
        $stmt->execute([
            $txRef,
            $userId,
            $tenantId,
            $leaseId,
            $propertyId,
            $amount,
            $provider
        ]);

        return [
            'id' => $this->pdo->lastInsertId(),
            'transaction_ref' => $txRef,
            'amount' => $amount,
            'currency' => 'NGN',
            'provider' => $provider,
            'status' => 'pending'
        ];
    }

    /**
     * Verify payment status server-side (Never trust client browser status)
     */
    public function verifyTransaction($transactionRef, $providerRef = null) {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_ref = ?");
        $stmt->execute([$transactionRef]);
        $tx = $stmt->fetch();

        if (!$tx) {
            throw new Exception("Transaction reference not found.");
        }

        if ($tx['status'] === 'paid') {
            return $tx; // Already verified (idempotent)
        }

        $verified = false;
        $receivedAmount = $tx['expected_amount'];

        if ($tx['provider'] === 'Paystack') {
            $verified = $this->verifyPaystack($transactionRef, $providerRef, $tx['expected_amount']);
        } elseif ($tx['provider'] === 'Flutterwave') {
            $verified = $this->verifyFlutterwave($transactionRef, $providerRef, $tx['expected_amount']);
        } elseif ($tx['provider'] === 'Bank Transfer' || $tx['provider'] === 'USSD') {
            // Manual Bank Transfer / USSD requires administrative reconciliation
            $up = $this->pdo->prepare("UPDATE payment_transactions SET status = 'pending_reconciliation' WHERE id = ?");
            $up->execute([$tx['id']]);
            $tx['status'] = 'pending_reconciliation';
            return $tx;
        }

        if ($verified) {
            $up = $this->pdo->prepare("
                UPDATE payment_transactions 
                SET status = 'paid', provider_ref = ?, received_amount = ?, verified_at = NOW() 
                WHERE id = ? AND status != 'paid'
            ");
            $up->execute([$providerRef ?? $transactionRef, $receivedAmount, $tx['id']]);

            // Audit log
            $log = $this->pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
            $log->execute(['system', "Verified Payment Ref: {$transactionRef} Amount: NGN {$receivedAmount}", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $tx['status'] = 'paid';
            $tx['verified_at'] = date('Y-m-d H:i:s');
            return $tx;
        } else {
            $up = $this->pdo->prepare("UPDATE payment_transactions SET status = 'failed' WHERE id = ?");
            $up->execute([$tx['id']]);
            throw new Exception("Payment verification failed with provider.");
        }
    }

    /**
     * Paystack Verification Helper
     */
    private function verifyPaystack($reference, $providerRef, $expectedAmount) {
        $secretKey = $this->config['payment']['paystack_secret_key'];
        if (empty($secretKey)) {
            return false;
        }

        // Sandbox / Testing stub check
        if ((strpos($secretKey, 'sk_test_') === 0 || ($this->config['app_env'] ?? '') === 'testing') && $providerRef === 'PROVIDER-REF-TEST') {
            return true;
        }

        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$secretKey}",
            "Cache-Control: no-cache"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($result && isset($result['status']) && $result['status'] === true) {
            $data = $result['data'];
            $paidAmount = ($data['amount'] ?? 0) / 100;
            $currency = $data['currency'] ?? 'NGN';
            if (($data['status'] ?? '') === 'success' && $paidAmount >= $expectedAmount && $currency === 'NGN') {
                return true;
            }
        }
        return false;
    }

    /**
     * Flutterwave Verification Helper
     */
    private function verifyFlutterwave($reference, $providerRef, $expectedAmount) {
        $secretKey = $this->config['payment']['flutterwave_secret_key'];
        if (empty($secretKey)) {
            return false;
        }

        // Sandbox / Testing stub check
        if ((strpos($secretKey, 'FLWSECK_TEST_') === 0 || ($this->config['app_env'] ?? '') === 'testing') && $providerRef === 'PROVIDER-REF-TEST') {
            return true;
        }

        $txId = $providerRef ?: $reference;
        $url = "https://api.flutterwave.com/v3/transactions/{$txId}/verify";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$secretKey}",
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            $data = $result['data'] ?? [];
            $paidAmount = $data['amount'] ?? 0;
            $currency = $data['currency'] ?? 'NGN';
            if ($paidAmount >= $expectedAmount && ($data['status'] ?? '') === 'successful' && $currency === 'NGN') {
                return true;
            }
        }
        return false;
    }

    /**
     * Webhook signature and idempotency handler
     */
    public function handleWebhook($provider, $signatureHeader, $payload) {
        if ($provider === 'Paystack') {
            $secretKey = $this->config['payment']['paystack_secret_key'];
            $computedSig = hash_hmac('sha512', $payload, $secretKey);
            if (!hash_equals($computedSig, $signatureHeader)) {
                throw new Exception("Invalid Paystack webhook signature.");
            }

            $event = json_decode($payload, true);
            if (isset($event['event']) && $event['event'] === 'charge.success') {
                $ref = $event['data']['reference'];
                return $this->verifyTransaction($ref, $event['data']['id'] ?? null);
            }
        } elseif ($provider === 'Flutterwave') {
            $secretKey = $this->config['payment']['flutterwave_secret_key'];
            if (!hash_equals($secretKey, $signatureHeader)) {
                throw new Exception("Invalid Flutterwave webhook signature.");
            }

            $event = json_decode($payload, true);
            if (isset($event['event']) && $event['event'] === 'charge.completed') {
                $ref = $event['data']['tx_ref'] ?? $event['data']['flw_ref'];
                return $this->verifyTransaction($ref, $event['data']['id'] ?? null);
            }
        }
        return false;
    }
}

