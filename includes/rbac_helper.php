<?php
/**
 * Granular RBAC System Helper
 * Sarkin Mota HQ Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Check if the currently logged-in user possesses a specific permission
 */
function has_permission($permission_name) {
    $user = get_logged_in_user();
    if (!$user) {
        return false;
    }

    // Super Admin retains full unrestricted access to all permissions
    if ($user['role'] === 'super_admin') {
        return true;
    }

    static $user_permissions_cache = [];
    $user_id = $user['id'];

    if (!isset($user_permissions_cache[$user_id])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.name 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN roles r ON rp.role_id = r.id
                JOIN users u ON r.name = u.role
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $user_permissions_cache[$user_id] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("RBAC Permission Lookup Error: " . $e->getMessage());
            return false;
        }
    }

    return in_array($permission_name, $user_permissions_cache[$user_id]);
}

/**
 * Enforce specific permission; redirect if user lacks permission
 */
function require_permission($permission_name, $redirect_to = 'auth/dashboard.php') {
    require_login();
    if (!has_permission($permission_name)) {
        set_flash_message('danger', 'Access Denied: You do not possess the required permission (' . htmlspecialchars($permission_name) . ').');
        abort_request(403);
    }
}

/**
 * Protect Super Admin Account Integrity
 * Ensures Admin users cannot modify, delete, or demote Super Admin accounts, or promote accounts to Super Admin.
 */
function can_manage_user($target_user_id, $target_user_role, $action = 'edit') {
    $current_user = get_logged_in_user();
    if (!$current_user) return false;

    // Super Admin can manage anyone
    if ($current_user['role'] === 'super_admin') {
        return true;
    }

    // Non-super-admins cannot manage Super Admin accounts
    if ($target_user_role === 'super_admin') {
        return false;
    }

    // Only Super Admins can assign super_admin role
    if ($action === 'assign_super_admin') {
        return false;
    }

    // System Admins can manage staff, landlords, tenants, and clients
    if ($current_user['role'] === 'admin') {
        return has_permission('users.edit');
    }

    return false;
}

