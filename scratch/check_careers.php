<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, title, status, department FROM careers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
