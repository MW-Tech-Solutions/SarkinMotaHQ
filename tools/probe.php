<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require __DIR__ . '/../config/app.php';
foreach (array_unique([$config['db']['port'], 3306, 3308]) as $port) {
    try {
        $db = new PDO('mysql:host='.$config['db']['host'].';port='.$port, $config['db']['username'], $config['db']['password'], [PDO::ATTR_TIMEOUT=>2]);
        echo "Database reachable on port $port\n";
        echo 'Available extensions: '.implode(', ', array_intersect(get_loaded_extensions(), ['pdo_mysql','gd','curl','fileinfo','mbstring']))."\n";
        break;
    } catch (Throwable $e) { echo "Database unavailable on port $port\n"; }
}
