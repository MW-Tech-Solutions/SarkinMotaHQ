<?php
/**
 * Corporate Divisions & RBAC Security Helper
 * Sarkin Mota HQ — Enterprise Edition
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Fetch all active corporate divisions
 */
function get_all_divisions(bool $active_only = true): array {
    global $pdo;
    try {
        $sql = "SELECT * FROM corporate_divisions";
        if ($active_only) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("get_all_divisions error: " . $e->getMessage());
        return [];
    }
}

if (!function_exists('get_divisions')) {
    function get_divisions(bool $active_only = true): array {
        return get_all_divisions($active_only);
    }
}

/**
 * Return URL for a given division
 */
if (!function_exists('division_url')) {
    function division_url($division): string {
        $slug = is_array($division) ? ($division['slug'] ?? '') : (string)$division;
        if ($slug === 'real-estate') {
            return 'properties.php';
        }
        if ($slug === 'automobile') {
            return 'cars.php';
        }
        return 'projects.php?division=' . urlencode($slug);
    }
}

/**
 * Fetch a division record by ID
 */
if (!function_exists('get_corporate_division_by_id')) {
    function get_corporate_division_by_id(PDO $pdo, int $id): ?array {
        try {
            $stmt = $pdo->prepare("SELECT * FROM corporate_divisions WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Fetch a division record by Slug
 */
if (!function_exists('get_corporate_division_by_slug')) {
    function get_corporate_division_by_slug(PDO $pdo, string $slug): ?array {
        try {
            $stmt = $pdo->prepare("SELECT * FROM corporate_divisions WHERE slug = ?");
            $stmt->execute([$slug]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Fetch division IDs assigned to a specific user
 */
function get_user_division_ids(int $user_id): array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT division_id FROM user_divisions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Fetch full division records assigned to a user
 */
function get_user_divisions(int $user_id): array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT d.* FROM corporate_divisions d JOIN user_divisions ud ON d.id = ud.division_id WHERE ud.user_id = ? AND d.status = 'active' ORDER BY d.name ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Assign staff user to one or multiple divisions
 */
function set_user_divisions(int $user_id, array $division_ids, ?int $assigned_by = null): bool {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $del = $pdo->prepare("DELETE FROM user_divisions WHERE user_id = ?");
        $del->execute([$user_id]);

        $ins = $pdo->prepare("INSERT INTO user_divisions (user_id, division_id, assigned_by) VALUES (?, ?, ?)");
        foreach ($division_ids as $div_id) {
            $div_id = (int) $div_id;
            if ($div_id > 0) {
                $ins->execute([$user_id, $div_id, $assigned_by]);
            }
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("set_user_divisions error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if current user is authorized to access a specific division (Server-side restriction)
 * Super Admin has unrestricted access.
 */
function is_user_authorized_for_division(int $division_id, ?array $user = null): bool {
    if (!$user && function_exists('get_logged_in_user')) {
        $user = get_logged_in_user();
    }
    if (!$user) {
        return false;
    }

    $role = $user['role'] ?? '';
    if ($role === 'super_admin' || $role === 'admin') {
        return true;
    }

    $assigned_ids = get_user_division_ids((int)$user['id']);
    return in_array($division_id, $assigned_ids);
}

/**
 * Enforce server-side division access restriction
 */
function require_division_access(int $division_id): void {
    if (!is_user_authorized_for_division($division_id)) {
        if (function_exists('abort_request')) {
            abort_request(403, 'Access Denied: You are not authorized to view or manage data for this corporate division.');
        } else {
            die('Access Denied: You are not authorized for this corporate division.');
        }
    }
}

/**
 * Fetch staff users assigned to a specific division (or all staff for super admin)
 */
function get_staff_by_division(?int $division_id = null): array {
    global $pdo;
    try {
        if ($division_id !== null && $division_id > 0) {
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.email, u.role, u.status 
                FROM users u 
                JOIN user_divisions ud ON u.id = ud.user_id 
                WHERE ud.division_id = ? AND u.status = 'active'
                ORDER BY u.name ASC
            ");
            $stmt->execute([$division_id]);
        } else {
            $stmt = $pdo->query("
                SELECT id, name, email, role, status 
                FROM users 
                WHERE role IN ('staff', 'general_staff', 'sales_executive', 'sales_manager', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer') AND status = 'active'
                ORDER BY name ASC
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Generate unique task reference string
 */
function generate_task_reference(?string $division_code = 'TSK'): string {
    global $pdo;
    $prefix = strtoupper($division_code ?: 'TSK') . '-TSK-' . date('Y') . '-';
    try {
        $stmt = $pdo->prepare("SELECT task_reference FROM staff_tasks WHERE task_reference LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last_ref = $stmt->fetchColumn();

        if ($last_ref) {
            $parts = explode('-', $last_ref);
            $num = (int) end($parts);
            $next_num = str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $next_num = '0001';
        }
        return $prefix . $next_num;
    } catch (Exception $e) {
        return $prefix . rand(1000, 9999);
    }
}

/**
 * Generate unique project reference number
 */
function generate_project_reference(?string $division_code = 'PRJ'): string {
    global $pdo;
    $prefix = strtoupper($division_code ?: 'PRJ') . '-' . date('Y') . '-';
    try {
        $stmt = $pdo->prepare("SELECT reference_number FROM corporate_projects WHERE reference_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last_ref = $stmt->fetchColumn();

        if ($last_ref) {
            $parts = explode('-', $last_ref);
            $num = (int) end($parts);
            $next_num = str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $next_num = '0001';
        }
        return $prefix . $next_num;
    } catch (Exception $e) {
        return $prefix . rand(1000, 9999);
    }
}
