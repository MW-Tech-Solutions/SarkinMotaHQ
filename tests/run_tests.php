<?php
/**
 * Master Automated CLI Test Suite Runner
 * Sarkin Mota HQ — Enterprise Edition (F22 Resolution)
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Denied: Test suite must be executed via CLI only.\n");
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthTest.php';
require_once __DIR__ . '/RbacTest.php';
require_once __DIR__ . '/PaymentTest.php';
require_once __DIR__ . '/UploadTest.php';
require_once __DIR__ . '/InspectionTest.php';
require_once __DIR__ . '/ApiTest.php';
require_once __DIR__ . '/BrandingTest.php';
require_once __DIR__ . '/HrTest.php';
require_once __DIR__ . '/CorporateWorkflowTest.php';

echo "========================================================\n";
echo "  Sarkin Mota HQ Enterprise Automated Test Suite Runner    \n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

$suites = [
    'AuthTest' => new AuthTest($pdo),
    'RbacTest' => new RbacTest($pdo),
    'PaymentTest' => new PaymentTest($pdo),
    'UploadTest' => new UploadTest($pdo),
    'InspectionTest' => new InspectionTest($pdo),
    'ApiTest' => new ApiTest($pdo),
    'BrandingTest' => new BrandingTest($pdo),
    'HrTest' => new HrTest($pdo),
    'CorporateWorkflowTest' => new CorporateWorkflowTest($pdo),
];

foreach ($suites as $name => $test) {
    try {
        $test->run();
        $passed++;
    } catch (Throwable $e) {
        $failed++;
        echo "  [X] {$name} FAILED: " . $e->getMessage() . "\n";
    }
}

echo "\n--------------------------------------------------------\n";
echo "Test Execution Summary: {$passed} Passed, {$failed} Failed.\n";
echo "--------------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
} else {
    echo "SUCCESS: All Enterprise System Tests Passed Cleanly!\n";
    exit(0);
}

