<?php
/**
 * Property Inspection Workflow Test Suite
 * Sarkin Mota HQ
 */

class InspectionTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] Inspection Requests & Status Workflow...\n";

        // Insert test inspection
        $stmt = $this->pdo->prepare("
            INSERT INTO inspection_requests (property_id, name, email, phone, preferred_date, preferred_time, inspection_type, status) 
            VALUES (1, 'Test Applicant', 'applicant@example.com', '+23480000000', '2026-10-01', '10:00:00', 'in_person', 'requested')
        ");
        $stmt->execute();
        $id = $this->pdo->lastInsertId();

        // Query status
        $chk = $this->pdo->prepare("SELECT status FROM inspection_requests WHERE id = ?");
        $chk->execute([$id]);
        $status = $chk->fetchColumn();
        assert($status === 'requested', "Initial inspection status must be requested");

        // Clean up
        $del = $this->pdo->prepare("DELETE FROM inspection_requests WHERE id = ?");
        $del->execute([$id]);

        echo "  [✓] InspectionTest Passed.\n";
    }
}

