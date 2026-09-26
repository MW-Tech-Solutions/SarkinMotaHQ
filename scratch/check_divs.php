<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, name, slug, code FROM corporate_divisions");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT id, division_id, title, reference_number FROM corporate_projects");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
