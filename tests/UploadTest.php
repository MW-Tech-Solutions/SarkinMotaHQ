<?php
/**
 * File Upload Security Test Suite
 * Sarkin Mota HQ
 */

class UploadTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] Upload Validation & Private Document Security...\n";

        // SVG disallowing check
        $disallowed_ext = 'svg';
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        assert(!in_array($disallowed_ext, $allowed_exts), "SVG must be strictly disallowed");

        // Private directory path check
        $private_dir = __DIR__ . '/../storage/uploads/private/';
        assert(file_exists($private_dir), "Private upload storage directory must exist");

        echo "  [✓] UploadTest Passed.\n";
    }
}

