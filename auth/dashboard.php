<?php
/**
 * Role Router Dashboard Entry Point - Sarkin Mota HQ
 * Dispatches each role cleanly to its dedicated portal dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
$user = get_logged_in_user();

$role = $user['role'] ?? 'client';
$query_string = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

switch ($role) {
    case 'super_admin':
    case 'admin':
    case 'staff':
    case 'hr_manager':
    case 'hr_officer':
    case 'department_manager':
    case 'interviewer':
        redirect('admin-dashboard.php' . $query_string);
        break;

    case 'tenant':
        redirect('tenant-dashboard.php' . $query_string);
        break;

    case 'landlord':
        redirect('landlord-dashboard.php' . $query_string);
        break;

    case 'client':
    default:
        redirect('client-dashboard.php' . $query_string);
        break;
}
