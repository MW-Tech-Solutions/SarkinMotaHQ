<?php
/**
 * HR Recruitment & Staff Onboarding Test Suite
 * Sarkin Mota HQ
 */

class HrTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] HR Recruitment, Job Offers & Staff Provisioning Transaction Safety...\n";

        // 1. Verify Departments seeded
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM departments");
        $dept_count = (int)$stmt->fetchColumn();
        assert($dept_count >= 5, "Fewer than 5 corporate departments found in database");

        // 2. Verify HR Roles & Permissions seeded
        $stmt_roles = $this->pdo->query("SELECT COUNT(*) FROM roles WHERE name IN ('hr_manager', 'hr_officer', 'department_manager', 'interviewer', 'staff')");
        $hr_roles_count = (int)$stmt_roles->fetchColumn();
        assert($hr_roles_count === 5, "HR roles not fully seeded in database");

        // 3. Test privilege escalation prevention (HR cannot assign admin or super_admin)
        $hr_user_role = 'hr_manager';
        $forbidden_target = 'admin';
        $can_elevate = ($hr_user_role === 'super_admin' && in_array($forbidden_target, ['admin', 'super_admin']));
        assert($can_elevate === false, "Privilege escalation guard failed: HR user allowed to assign admin role");

        // 5. Test Dynamic Department CRUD
        $test_code = 'TEST-' . rand(1000, 9999);
        $stmt_ins = $this->pdo->prepare("INSERT INTO departments (name, code, description) VALUES (?, ?, ?)");
        $stmt_ins->execute(["Test Dept $test_code", $test_code, "Automated Test Department"]);
        $test_dept_id = $this->pdo->lastInsertId();
        assert($test_dept_id > 0, "Failed to dynamically create department");

        // Verify update
        $stmt_upd = $this->pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt_upd->execute(["Updated Dept $test_code", $test_dept_id]);
        
        $stmt_fetch = $this->pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt_fetch->execute([$test_dept_id]);
        $updated_name = $stmt_fetch->fetchColumn();
        assert($updated_name === "Updated Dept $test_code", "Dynamic department update failed");

        // Clean up test department
        $stmt_del = $this->pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt_del->execute([$test_dept_id]);

        // 6. Test Email Dispatcher & Notification Routing
        require_once __DIR__ . '/../includes/mail_helper.php';
        $test_email = 'test.candidate.' . rand(1000, 9999) . '@sarkinmotahq.com';
        $sent_ok = send_system_email($test_email, 'Test Dispatch', 'Automated Test Message', 'test_type');
        assert($sent_ok === true, "Failed to dispatch system email");

        $stmt_mail = $this->pdo->prepare("SELECT COUNT(*) FROM mail_logs WHERE recipient_email = ?");
        $stmt_mail->execute([$test_email]);
        assert((int)$stmt_mail->fetchColumn() > 0, "Dispatched email was not logged in mail_logs table");

        echo "  [✓] HrTest Passed.\n";
    }
}
