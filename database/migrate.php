<?php
/**
 * CLI Migration & Seeding Execution Script
 * Sarkin Mota HQ Enterprise Edition
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Denied: Migration script must be executed via CLI only.\n");
}

require_once __DIR__ . '/../config/db.php';

echo "=== Sarkin Mota HQ Enterprise Migration & Seeding ===\n";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    if (!$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn()) {
        $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
        foreach (['01_schema_v2.sql','02_system_settings.sql','03_hr_onboarding.sql','04_team_members.sql'] as $version) {
            if ($version === '02_system_settings.sql') $pdo->exec(file_get_contents(__DIR__.'/migrations/'.$version));
            $pdo->prepare('INSERT IGNORE INTO schema_migrations (version) VALUES (?)')->execute([$version]);
        }
    }
    foreach (glob(__DIR__.'/migrations/*.sql') as $file) {
        $check=$pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = ?');
        $check->execute([basename($file)]);
        if ($check->fetchColumn()) continue;
        $pdo->exec(file_get_contents($file));
        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([basename($file)]);
        echo '[OK] '.basename($file)."\n";
    }
    $pdo->beginTransaction();

    // 2. Seed Roles
    $roles = [
        ['super_admin', 'Super Administrator', 'Full unrestricted system administration rights.'],
        ['admin', 'System Administrator', 'Corporate platform administration.'],
        ['staff', 'Corporate Staff', 'Operations, support, and site management.'],
        ['general_staff', 'General Staff', 'General corporate division staff member.'],
        ['sales_manager', 'Sales Manager', 'Manager for Real Estate and Automobile / Car Sales divisions.'],
        ['sales_executive', 'Sales Executive', 'Sales representative for Real Estate property sales & Automobile vehicle sales.'],
        ['hr_manager', 'HR Manager', 'Full recruitment, onboarding, hiring approval & HR management.'],
        ['hr_officer', 'HR Officer', 'Applicant screening, interview scheduling & onboarding operations.'],
        ['department_manager', 'Department Manager', 'Departmental vacancies, candidates & staff management.'],
        ['interviewer', 'Interviewer', 'Assigned candidate interviews and evaluations.'],
        ['landlord', 'Landlord / Property Owner', 'Property listing management and tenant tracking.'],
        ['tenant', 'Tenant Account', 'Lease management, rent payment, and maintenance.'],
        ['client', 'Client / Investor', 'Property inquiries, consultations, and inspection bookings.']
    ];

    $stmt_role = $pdo->prepare("INSERT INTO roles (name, label, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label=VALUES(label), description=VALUES(description)");
    foreach ($roles as $r) {
        $stmt_role->execute($r);
    }
    echo "[OK] Roles seeded successfully.\n";

    // 3. Seed Permissions
    $permissions = [
        ['users.view', 'View Users Catalog', 'users'],
        ['users.create', 'Create User Accounts', 'users'],
        ['users.edit', 'Edit User Profiles', 'users'],
        ['users.delete', 'Delete User Accounts', 'users'],
        ['users.assign_roles', 'Assign User Roles', 'users'],
        ['users.manage_admins', 'Manage Admin Accounts', 'users'],
        
        ['properties.view', 'View Properties', 'properties'],
        ['properties.create', 'Create Property Listings', 'properties'],
        ['properties.edit', 'Edit Property Listings', 'properties'],
        ['properties.archive', 'Archive Property Listings', 'properties'],
        ['properties.delete', 'Delete Property Listings', 'properties'],
        
        ['payments.view', 'View Payment Ledger', 'payments'],
        ['payments.verify', 'Verify Transactions', 'payments'],
        ['payments.refund', 'Refund Payments', 'payments'],
        ['payments.reconcile', 'Reconcile Financials', 'payments'],
        
        ['tenants.view', 'View Tenants Roster', 'tenants'],
        ['tenants.manage', 'Manage Tenants', 'tenants'],
        ['landlords.view', 'View Landlords Directory', 'landlords'],
        ['landlords.manage', 'Manage Landlords', 'landlords'],
        ['leases.manage', 'Manage Property Leases', 'leases'],
        
        ['careers.manage', 'Manage Job Openings', 'careers'],
        ['applications.view', 'View Job Applications', 'careers'],
        ['applications.manage', 'Process Applications', 'careers'],
        
        ['hr.view_dashboard', 'View HR Control Dashboard', 'hr'],
        ['hr.vacancies.manage', 'Manage Job Vacancies', 'hr'],
        ['hr.vacancies.approve', 'Approve Vacancies', 'hr'],
        ['hr.applicants.view', 'View Applicants & Candidates', 'hr'],
        ['hr.applicants.manage', 'Process Candidate Pipeline', 'hr'],
        ['hr.interviews.manage', 'Schedule & Manage Interviews', 'hr'],
        ['hr.interviews.evaluate', 'Submit Interview Evaluations', 'hr'],
        ['hr.offers.create', 'Prepare Job Offers', 'hr'],
        ['hr.offers.approve', 'Approve Job Offers', 'hr'],
        ['hr.offers.view_compensation', 'View Compensation Amounts', 'hr'],
        ['hr.onboarding.manage', 'Manage Onboarding Checklists', 'hr'],
        ['hr.staff.create_account', 'Provision Staff Accounts', 'hr'],
        ['hr.staff.view_directory', 'View Staff Directory', 'hr'],
        ['hr.staff.manage_profile', 'Manage Staff Profiles & Departments', 'hr'],
        ['departments.manage', 'Manage Corporate Departments', 'hr'],

        ['news.manage', 'Manage Articles & CMS', 'cms'],
        ['projects.manage', 'Manage Division Projects', 'projects'],
        ['inquiries.manage', 'Manage Client Inquiries', 'inquiries'],
        ['inspections.manage', 'Manage Inspection Requests', 'inspections'],
        ['consultations.manage', 'Manage Consultation Requests', 'consultations'],
        
        ['reports.view', 'View Analytics & Reports', 'analytics'],
        ['settings.manage', 'Manage Enterprise Settings', 'settings'],
        ['audit.view', 'View System Audit Logs', 'audit']
    ];

    $stmt_perm = $pdo->prepare("INSERT INTO permissions (name, label, category) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label=VALUES(label), category=VALUES(category)");
    foreach ($permissions as $p) {
        $stmt_perm->execute($p);
    }
    echo "[OK] Permissions seeded successfully.\n";

    // 4. Map Role Permissions
    $role_map = $pdo->query("SELECT name, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
    $perm_map = $pdo->query("SELECT name, id FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);

    $role_perms = [
        'super_admin' => array_keys($perm_map), // All permissions
        'admin' => [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.assign_roles',
            'properties.view', 'properties.create', 'properties.edit', 'properties.archive', 'properties.delete',
            'payments.view', 'payments.verify', 'payments.reconcile',
            'tenants.view', 'tenants.manage', 'landlords.view', 'landlords.manage', 'leases.manage',
            'careers.manage', 'applications.view', 'applications.manage',
            'hr.view_dashboard', 'hr.vacancies.manage', 'hr.vacancies.approve', 'hr.applicants.view',
            'hr.applicants.manage', 'hr.interviews.manage', 'hr.interviews.evaluate', 'hr.offers.create',
            'hr.offers.approve', 'hr.offers.view_compensation', 'hr.onboarding.manage',
            'hr.staff.create_account', 'hr.staff.view_directory', 'hr.staff.manage_profile', 'departments.manage',
            'news.manage', 'projects.manage', 'inquiries.manage', 'inspections.manage', 'consultations.manage',
            'reports.view', 'audit.view'
        ],
        'hr_manager' => [
            'careers.manage', 'applications.view', 'applications.manage',
            'hr.view_dashboard', 'hr.vacancies.manage', 'hr.vacancies.approve', 'hr.applicants.view',
            'hr.applicants.manage', 'hr.interviews.manage', 'hr.interviews.evaluate', 'hr.offers.create',
            'hr.offers.approve', 'hr.offers.view_compensation', 'hr.onboarding.manage',
            'hr.staff.create_account', 'hr.staff.view_directory', 'hr.staff.manage_profile', 'departments.manage'
        ],
        'hr_officer' => [
            'careers.manage', 'applications.view', 'applications.manage',
            'hr.view_dashboard', 'hr.vacancies.manage', 'hr.applicants.view', 'hr.applicants.manage',
            'hr.interviews.manage', 'hr.interviews.evaluate', 'hr.offers.create', 'hr.onboarding.manage',
            'hr.staff.view_directory'
        ],
        'department_manager' => [
            'hr.view_dashboard', 'hr.vacancies.manage', 'hr.applicants.view', 'hr.interviews.manage',
            'hr.interviews.evaluate', 'hr.offers.create', 'hr.staff.view_directory'
        ],
        'interviewer' => [
            'hr.applicants.view', 'hr.interviews.evaluate'
        ],
        'staff' => [
            'properties.view', 'properties.create', 'properties.edit',
            'payments.view', 'tenants.view', 'landlords.view', 'applications.view',
            'inquiries.manage', 'inspections.manage', 'consultations.manage',
            'hr.staff.view_directory'
        ],
        'landlord' => [
            'properties.view', 'properties.create', 'properties.edit', 'tenants.view', 'payments.view'
        ],
        'tenant' => [
            'properties.view', 'payments.view'
        ],
        'client' => [
            'properties.view'
        ]
    ];

    $stmt_rp = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    foreach ($role_perms as $rname => $perms) {
        if (!isset($role_map[$rname])) continue;
        $rid = $role_map[$rname];
        foreach ($perms as $pname) {
            if (isset($perm_map[$pname])) {
                $stmt_rp->execute([$rid, $perm_map[$pname]]);
            }
        }
    }
    echo "[OK] Role-Permission mappings initialized.\n";

    // 5. Seed Departments
    $departments = [
        ['Agriculture', 'AGR', 'Commercial farming, agronomy, and crop science division.'],
        ['Estate & Property Management', 'EPM', 'Property valuation, leasing, asset maintenance, and facilities.'],
        ['Environmental Consulting', 'ENV', 'Environmental Impact Assessment (EIA) and eco-audits.'],
        ['Development Consulting', 'DEV', 'Urban planning, infrastructure, and development consulting.'],
        ['Administration', 'ADM', 'Corporate administration, HR, finance, and operations.']
    ];

    $stmt_dept = $pdo->prepare("INSERT INTO departments (name, code, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE code=VALUES(code), description=VALUES(description)");
    foreach ($departments as $d) {
        $stmt_dept->execute($d);
    }
    echo "[OK] Default corporate departments seeded.\n";

    // 6. Backfill user_roles table from users table role column
    $users = $pdo->query("SELECT id, role FROM users")->fetchAll();
    $stmt_ur = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
    foreach ($users as $u) {
        if (!empty($u['role']) && isset($role_map[$u['role']])) {
            $stmt_ur->execute([$u['id'], $role_map[$u['role']]]);
        }
    }
    echo "[OK] User roles backfilled; no accounts or demo records created.\n";

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    echo "\n=== Migration & Seeding Completed Successfully! ===\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
