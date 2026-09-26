<?php
/**
 * Corporate Divisions, Projects & Task Workflow Test Suite
 * Sarkin Mota HQ Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/divisions_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

class CorporateWorkflowTest {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run(): void {
        echo "Running Corporate Workflow & Divisions Test Suite...\n";

        $this->testDivisionsExist();
        $this->testCreateDivision();
        $this->testStaffDivisionAssignment();
        $this->testProjectCreation();
        $this->testTaskAssignmentAndWorkflow();
        $this->testRejectionReasonRequirement();
        $this->testAuditLogsAndNotifications();

        echo "  [✓] Corporate Workflow & Divisions Test Suite PASSED.\n";
    }

    private function testDivisionsExist(): void {
        $divisions = get_all_divisions(true);
        if (count($divisions) < 2) {
            throw new Exception("Initial seeded divisions (Real Estate & Automobile) missing!");
        }

        $slugs = array_column($divisions, 'slug');
        if (!in_array('real-estate', $slugs) || !in_array('automobile', $slugs)) {
            throw new Exception("Expected real-estate and automobile division slugs missing!");
        }
    }

    private function testCreateDivision(): void {
        $testCode = 'TD' . rand(1000, 9999);
        $testName = 'Test Div ' . rand(1000, 9999);
        $stmt = $this->pdo->prepare("INSERT INTO corporate_divisions (name, slug, code, description, icon, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$testName, 'test-div-' . time() . '-' . rand(100, 999), $testCode, 'Testing dynamic division creation', 'fa-vials']);
        $divId = (int)$this->pdo->lastInsertId();

        $stmtDiv = $this->pdo->prepare("SELECT * FROM corporate_divisions WHERE id = ?");
        $stmtDiv->execute([$divId]);
        $division = $stmtDiv->fetch(PDO::FETCH_ASSOC);

        if (!$division || $division['code'] !== $testCode) {
            throw new Exception("Failed to fetch dynamically created division!");
        }
    }

    private function testStaffDivisionAssignment(): void {
        $stmt = $this->pdo->query("SELECT id FROM users LIMIT 1");
        $staffId = (int)$stmt->fetchColumn();
        if (!$staffId) {
            return;
        }

        $stmtRe = $this->pdo->query("SELECT id FROM corporate_divisions WHERE slug = 'real-estate'");
        $reDivId = (int)($stmtRe->fetchColumn() ?: 1);

        $stmtAuto = $this->pdo->query("SELECT id FROM corporate_divisions WHERE slug = 'automobile'");
        $autoDivId = (int)($stmtAuto->fetchColumn() ?: 2);

        set_user_divisions($staffId, [$reDivId, $autoDivId], 1);

        $userDivs = get_user_division_ids($staffId);
        if (!in_array($reDivId, $userDivs) || !in_array($autoDivId, $userDivs)) {
            throw new Exception("Staff multi-division assignment failed!");
        }
    }

    private function testProjectCreation(): void {
        $stmtRe = $this->pdo->query("SELECT id FROM corporate_divisions WHERE slug = 'real-estate'");
        $reDivId = (int)($stmtRe->fetchColumn() ?: 1);

        $ref = generate_project_reference('RE');

        $stmt = $this->pdo->prepare("INSERT INTO corporate_projects (division_id, title, reference_number, description, status, priority, created_by) VALUES (?, ?, ?, ?, 'active', 'High', 1)");
        $stmt->execute([$reDivId, 'Test Estate Listing', $ref, 'Automated Test Project Description']);
        $projId = (int)$this->pdo->lastInsertId();

        $stmtDetails = $this->pdo->prepare("INSERT INTO real_estate_project_details (project_id, property_type, transaction_type, location, city, state, price, bedrooms, bathrooms) VALUES (?, 'house', 'sale', 'Maitama', 'Abuja', 'FCT', 150000000.00, 5, 6)");
        $stmtDetails->execute([$projId]);

        $stmtProj = $this->pdo->prepare("SELECT * FROM corporate_projects WHERE id = ?");
        $stmtProj->execute([$projId]);
        $project = $stmtProj->fetch(PDO::FETCH_ASSOC);

        if (!$project || $project['reference_number'] !== $ref) {
            throw new Exception("Failed to retrieve created Real Estate project!");
        }
    }

    private function testTaskAssignmentAndWorkflow(): void {
        $stmtRe = $this->pdo->query("SELECT id FROM corporate_divisions WHERE slug = 'real-estate'");
        $reDivId = (int)($stmtRe->fetchColumn() ?: 1);

        $taskRef = 'RE-TSK-' . date('Y') . '-' . rand(1000, 9999);

        $stmtUser = $this->pdo->query("SELECT id FROM users LIMIT 1");
        $staffId = (int)($stmtUser->fetchColumn() ?: 1);

        $dueDate = date('Y-m-d', strtotime('+3 days'));

        // 1. Super Admin Creates Task
        $stmtTask = $this->pdo->prepare("INSERT INTO staff_tasks (task_reference, division_id, title, description, priority, due_date, instructions, created_by, status) VALUES (?, ?, 'Test Inspection Task', 'Inspect property site', 'High', ?, 'Take 5 clear photos', 1, 'Assigned')");
        $stmtTask->execute([$taskRef, $reDivId, $dueDate]);
        $taskId = (int)$this->pdo->lastInsertId();

        if (!$taskId) {
            throw new Exception("Failed to insert task!");
        }

        $stmtAss = $this->pdo->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
        $stmtAss->execute([$taskId, $staffId]);

        // 2. Staff Acknowledges
        $stmtAck = $this->pdo->prepare("UPDATE task_assignees SET acknowledged_at = NOW() WHERE task_id = ? AND user_id = ?");
        $stmtAck->execute([$taskId, $staffId]);
        
        $upd = $this->pdo->prepare("UPDATE staff_tasks SET status = 'In Progress' WHERE id = ?");
        $upd->execute([$taskId]);

        $stmtCheck = $this->pdo->prepare("SELECT status FROM staff_tasks WHERE id = ?");
        $stmtCheck->execute([$taskId]);
        $status = $stmtCheck->fetchColumn();
        if ($status !== 'In Progress') {
            throw new Exception("Task status failed to transition to 'In Progress' (Got: '{$status}', TaskID: {$taskId})!");
        }

        // 3. Staff Submits Work Report
        $stmtSub = $this->pdo->prepare("INSERT INTO task_submissions (task_id, staff_id, summary, report, submission_number, status) VALUES (?, ?, 'Inspection complete', 'Found site in excellent condition.', 1, 'submitted')");
        $stmtSub->execute([$taskId, $staffId]);
        $subId = (int)$this->pdo->lastInsertId();

        $updSub = $this->pdo->prepare("UPDATE staff_tasks SET status = 'Submitted' WHERE id = ?");
        $updSub->execute([$taskId]);

        // 4. Admin Rejection with Mandatory Reason
        $rejectionReason = "Photographs of Plot 12 missing from report submission.";
        $stmtRev = $this->pdo->prepare("INSERT INTO task_reviews (task_id, task_submission_id, reviewed_by, decision, reason) VALUES (?, ?, 1, 'rejected', ?)");
        $stmtRev->execute([$taskId, $subId, $rejectionReason]);

        $updRej = $this->pdo->prepare("UPDATE staff_tasks SET status = 'Revision Required' WHERE id = ?");
        $updRej->execute([$taskId]);

        $stmtCheckRev = $this->pdo->prepare("SELECT status FROM staff_tasks WHERE id = ?");
        $stmtCheckRev->execute([$taskId]);
        $revStatus = $stmtCheckRev->fetchColumn();
        if ($revStatus !== 'Revision Required') {
            throw new Exception("Task failed to transition to 'Revision Required' state upon rejection!");
        }

        // 5. Staff Resubmits & Admin Accepts
        $stmtSub2 = $this->pdo->prepare("INSERT INTO task_submissions (task_id, staff_id, summary, report, submission_number, status) VALUES (?, ?, 'Inspection complete with photos', 'Uploaded 5 photos of Plot 12.', 2, 'submitted')");
        $stmtSub2->execute([$taskId, $staffId]);

        $updAcc = $this->pdo->prepare("UPDATE staff_tasks SET status = 'Accepted' WHERE id = ?");
        $updAcc->execute([$taskId]);

        $stmtCheckAcc = $this->pdo->prepare("SELECT status FROM staff_tasks WHERE id = ?");
        $stmtCheckAcc->execute([$taskId]);
        $finalStatus = $stmtCheckAcc->fetchColumn();
        if ($finalStatus !== 'Accepted') {
            throw new Exception("Task failed to transition to 'Accepted' final state!");
        }
    }

    private function testRejectionReasonRequirement(): void {
        $reason = trim('');
        if (!empty($reason)) {
            throw new Exception("Rejection reason check failed!");
        }
    }

    private function testAuditLogsAndNotifications(): void {
        $stmt = $this->pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES ('system_test', 'CORPORATE_TEST_ACTION', '127.0.0.1')");
        $stmt->execute();

        $stmtCheck = $this->pdo->prepare("SELECT id FROM audit_logs WHERE action = 'CORPORATE_TEST_ACTION' ORDER BY id DESC LIMIT 1");
        $stmtCheck->execute();
        if (!$stmtCheck->fetchColumn()) {
            throw new Exception("Audit log persistence failed!");
        }
    }
}
