<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("DELETE FROM corporate_divisions WHERE name LIKE 'Test Div%' OR name LIKE 'Test Division%'");
echo "Cleaned up test division records.\n";
