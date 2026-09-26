<?php
require_once __DIR__ . '/../config/db.php';

echo "--- corporate_projects ---\n";
print_r($pdo->query("DESCRIBE corporate_projects")->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- automobile_project_details ---\n";
print_r($pdo->query("DESCRIBE automobile_project_details")->fetchAll(PDO::FETCH_ASSOC));
