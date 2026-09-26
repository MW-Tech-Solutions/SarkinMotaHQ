<?php
/**
 * Enterprise Secure REST API — Properties Catalog Directory
 * Sarkin Mota HQ (F24 Resolution)
 */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => ["code" => 405, "message" => "Method Not Allowed. Only GET requests supported."]
    ]);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination_helper.php';

$appConfig = require __DIR__ . '/../config/app.php';
$expected_key = $appConfig['api']['secret_key'];

// Extract Authorization Bearer Token or X-API-Key Header (Never from URL query parameters)
$received_key = null;
if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    $received_key = $matches[1];
} elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
    $received_key = $_SERVER['HTTP_X_API_KEY'];
}

if (empty($expected_key) || empty($received_key) || !hash_equals($expected_key, $received_key)) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => [
            "code" => 401,
            "message" => "Unauthorized API Access. Invalid or missing Bearer token in Authorization header."
        ]
    ]);
    exit;
}

// Retrieve query parameters
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;

try {
    $sql = "SELECT id, title, description, price, location, latitude, longitude, beds, baths, area_sqft, land_size_sqft, type, category, listing_status, image_url, agent_name, is_short_let, created_at FROM properties WHERE listing_status = 'active'";
    $params = [];

    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    if (!empty($type)) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    if ($max_price > 0) {
        $sql .= " AND price <= ?";
        $params[] = $max_price;
    }

    $sql .= " ORDER BY id DESC";

    $paginated = paginate_query($pdo, $sql, $params, $page, $per_page);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => $paginated['data'],
        "meta" => [
            "page" => $paginated['page'],
            "per_page" => $paginated['per_page'],
            "total" => $paginated['total'],
            "last_page" => $paginated['last_page']
        ]
    ]);

} catch (Exception $e) {
    error_log("API Properties Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => [
            "code" => 500,
            "message" => "Internal API service error occurred."
        ]
    ]);
}
