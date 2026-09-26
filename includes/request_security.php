<?php
/** Shared request controls. No mutation is permitted before CSRF validation. */
function enforce_request_security(PDO $pdo): void {
    if (PHP_SAPI === 'cli') return;
    start_secure_session();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    header("Content-Security-Policy: base-uri 'self'; object-src 'none'; frame-ancestors 'self'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
    $path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('~/(auth|admin|hr)/|/(download|activate-account|offer-response)~', $path)) header('Cache-Control: no-store, private');
    foreach ([$_GET, $_POST] as $input) {
        foreach ($input as $value) if (is_array($value)) abort_request(400, 'Repeated or nested form fields are not supported.');
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET','HEAD','POST','OPTIONS'], true)) { header('Allow: GET, HEAD, POST'); abort_request(405); }
    if ($method === 'POST' && !str_ends_with($path, '/api/payment-webhook.php')) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) abort_request(403, 'Your session could not be verified. Refresh the page and try again.');
    }
    if ($method === 'POST' || str_contains($path, '/api/')) {
        $limit = str_ends_with($path, '/auth/login.php') ? 20 : (str_contains($path, '/api/') ? 180 : 60);
        enforce_rate_limit($pdo, $path, $limit, 900);
    }
}

function enforce_rate_limit(PDO $pdo, string $scope, int $limit, int $window): void {
    $key = hash('sha256', $scope.'|'.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $bucket = intdiv(time(), $window);
    try {
        $stmt = $pdo->prepare('INSERT INTO request_limits (bucket_key, bucket, attempts) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE attempts = IF(bucket = VALUES(bucket), attempts + 1, 1), bucket = VALUES(bucket)');
        $stmt->execute([$key,$bucket]);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02' || str_contains($e->getMessage(), "1146")) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS request_limits (bucket_key CHAR(64) NOT NULL PRIMARY KEY, bucket BIGINT NOT NULL, attempts INT UNSIGNED NOT NULL DEFAULT 0, INDEX idx_request_limit_expiry (bucket)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $pdo->prepare('INSERT INTO request_limits (bucket_key, bucket, attempts) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE attempts = IF(bucket = VALUES(bucket), attempts + 1, 1), bucket = VALUES(bucket)');
            $stmt->execute([$key,$bucket]);
        } else {
            throw $e;
        }
    }
    $read = $pdo->prepare('SELECT attempts FROM request_limits WHERE bucket_key = ?');
    $read->execute([$key]);
    if ((int)$read->fetchColumn() > $limit) {
        header('Retry-After: '.($window - time() % $window));
        abort_request(429, 'Please wait before trying again.');
    }
    if (random_int(1,100) === 1) $pdo->prepare('DELETE FROM request_limits WHERE bucket < ?')->execute([$bucket-2]);
}
