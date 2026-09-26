<?php
/**
 * REST API Endpoint - Save User Theme Preference
 * Enterprise Edition — Sarkin Mota HQ
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !is_string($input['theme'] ?? null)) abort_request(422, 'Choose a valid theme.');
$theme = sanitize_input($input['theme'] ?? 'system');

if (!in_array($theme, ['light', 'dark', 'system'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid theme mode']);
    exit;
}

start_secure_session();
$_SESSION['user_theme'] = $theme;

if (is_logged_in()) {
    $user = get_logged_in_user();
    try {
        $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        $stmt->execute([$theme, $user['id']]);
    } catch (Exception $e) {
        // Table column optional
    }
}

echo json_encode(['success' => true, 'theme' => $theme]);

