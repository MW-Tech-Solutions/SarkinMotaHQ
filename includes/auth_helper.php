<?php
/**
 * Authentication & Session Security Helpers - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

// Inactivity Session Timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

/**
 * Check if user is logged in with active, non-expired session and valid DB session token (F09 resolution)
 */
function is_logged_in() {
    start_secure_session();
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    // Enforce Inactivity Timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        revoke_current_session();
        return false;
    }
    
    // Validate session status and session_token against Database in real-time
    static $validated_user = null;
    if ($validated_user === null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, role, status, session_token FROM users WHERE id = ? AND session_token = ?");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token'] ?? '']);
            $db_user = $stmt->fetch();

            if (!$db_user || $db_user['status'] !== 'active' || $db_user['session_token'] !== $_SESSION['session_token']) {
                revoke_current_session();
                return false;
            }

            // Sync session variables if role or name changed in DB
            $_SESSION['user_role'] = $db_user['role'];
            $_SESSION['user_name'] = $db_user['name'];
            $_SESSION['user_email'] = $db_user['email'];
            
            $validated_user = $db_user;
        } catch (Exception $e) {
            error_log("Session Verification Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Retrieve currently logged-in user context
 */
function get_logged_in_user() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'client'
        ];
    }
    return null;
}

/**
 * Start a new authenticated user session with a random session_token stored in DB
 */
function create_authenticated_session($user) {
    start_secure_session();
    session_regenerate_id(true);
    
    $session_token = bin2hex(random_bytes(32));
    
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET session_token = ? WHERE id = ?");
    $stmt->execute([$session_token, $user['id']]);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['session_token'] = $session_token;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['created_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Safely revoke current session
 */
function revoke_current_session() {
    start_secure_session();
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {}
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Enforce login; redirect to login if unauthenticated
 */
function require_login($redirect_to = '/SarkinMota/auth/login.php') {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Session expired or invalid. Please sign in to continue.');
        redirect($redirect_to);
    }
}

/**
 * Enforce role access (Super Admin bypasses all role restrictions)
 */
function require_role($allowed_roles, $redirect_to = '/SarkinMota/auth/dashboard.php') {
    require_login();
    $user = get_logged_in_user();
    
    // Super Admin has full unrestricted access
    if ($user['role'] === 'super_admin') {
        return;
    }
    
    $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    
    if (!in_array($user['role'], $allowed)) {
        set_flash_message('danger', 'Access denied. Your current account role (' . ucfirst($user['role']) . ') lacks required permissions.');
        abort_request(403);
    }
}

/**
 * Role specific enforcement shortcuts
 */
function require_super_admin($redirect_to = '/SarkinMota/auth/dashboard.php') {
    require_role('super_admin', $redirect_to);
}

function require_admin($redirect_to = '/SarkinMota/auth/dashboard.php') {
    require_role(['super_admin', 'admin'], $redirect_to);
}

function require_landlord($redirect_to = '/SarkinMota/auth/dashboard.php') {
    require_role(['super_admin', 'admin', 'landlord'], $redirect_to);
}

function require_tenant($redirect_to = '/SarkinMota/auth/dashboard.php') {
    require_role(['super_admin', 'admin', 'tenant'], $redirect_to);
}

function require_staff($redirect_to = '/SarkinMota/auth/dashboard.php') {
    require_role(['super_admin', 'admin', 'staff', 'general_staff', 'sales_executive', 'sales_manager', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer'], $redirect_to);
}

/**
 * Boolean helpers for templates
 */
function is_super_admin() {
    $u = get_logged_in_user();
    return $u && $u['role'] === 'super_admin';
}

function is_admin() {
    $u = get_logged_in_user();
    return $u && in_array($u['role'], ['super_admin', 'admin']);
}

function is_landlord() {
    $u = get_logged_in_user();
    return $u && in_array($u['role'], ['super_admin', 'admin', 'landlord']);
}

function is_tenant() {
    $u = get_logged_in_user();
    return $u && in_array($u['role'], ['super_admin', 'admin', 'tenant']);
}

function is_staff() {
    $u = get_logged_in_user();
    return $u && in_array($u['role'], ['super_admin', 'admin', 'staff', 'general_staff', 'sales_executive', 'sales_manager', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer']);
}
