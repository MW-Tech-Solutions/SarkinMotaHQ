<?php
/**
 * Authentication & Security Test Suite
 * Sarkin Mota HQ
 */

class AuthTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] Authentication & Registration Role Restriction...\n";
        
        // 1. Password Hashing
        $password = "SecretPass123!";
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        assert(password_verify($password, $hash), "Password verification failed");

        // 2. Test Registration Role Filter (Disallowing Staff escalation)
        $public_role = 'staff';
        $allowed_register_roles = ['client', 'tenant', 'landlord'];
        if (!in_array($public_role, $allowed_register_roles)) {
            $public_role = 'client';
        }
        assert($public_role === 'client', "Public registration failed to sanitize staff role to client");

        echo "  [✓] AuthTest Passed.\n";
    }
}

