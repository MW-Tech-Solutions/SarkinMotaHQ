<?php
/**
 * Payment Verification Architecture Test Suite
 * Sarkin Mota HQ
 */

require_once __DIR__ . '/../app/Services/PaymentService.php';

class PaymentTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] Payment Verification & Transaction Lifecycle...\n";

        $paymentService = new PaymentService($this->pdo);

        // 1. Initiate Transaction
        $txn = $paymentService->initiateTransaction(1, 50000.00, 'Paystack');
        assert($txn['status'] === 'pending', "Initiated transaction must start as pending");
        assert(strpos($txn['transaction_ref'], 'KDT-') === 0, "Invalid transaction ref prefix");

        // 2. Verify Transaction
        $verifiedTx = $paymentService->verifyTransaction($txn['transaction_ref'], 'PROVIDER-REF-TEST');
        assert($verifiedTx['status'] === 'paid', "Verified transaction must be marked paid");

        echo "  [✓] PaymentTest Passed.\n";
    }
}

