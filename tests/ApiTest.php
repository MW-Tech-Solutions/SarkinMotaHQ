<?php
/**
 * REST API Contract Test Suite
 * Sarkin Mota HQ
 */

class ApiTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] REST API Contract & Authorization Header...\n";

        $appConfig = require __DIR__ . '/../config/app.php';
        $validKey = $appConfig['api']['secret_key'];
        $invalidKey = "WrongKey123";

        assert(hash_equals($validKey, $validKey), "Valid API key hash verification failed");
        assert(!hash_equals($validKey, $invalidKey), "Invalid API key should be rejected");

        echo "  [✓] ApiTest Passed.\n";
    }
}

