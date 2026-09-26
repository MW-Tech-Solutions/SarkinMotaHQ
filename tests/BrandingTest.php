<?php
/**
 * Global Branding & Theme Management Test Suite
 * Sarkin Mota HQ — Enterprise Edition
 */

require_once __DIR__ . '/../includes/settings_helper.php';

class BrandingTest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function run() {
        echo "  [Test] Global Branding & Dynamic Theme Engine...\n";

        // 1. Verify Default Setting Retrieval
        $company_name = setting('company_name', 'Fallback Co.');
        assert(!empty($company_name), "setting('company_name') should not be empty");
        assert($company_name === 'Sarkin Mota HQ' || strpos($company_name, 'Sarkin Mota HQ') !== false, "company_name mismatch");

        $fallback_val = setting('non_existent_key_xyz_123', 'Default Fallback');
        assert($fallback_val === 'Default Fallback', "Fallback value for missing setting failed");

        // 2. Test Setting Update & Audit Logging
        $super_admin_stmt = $this->pdo->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
        $super_admin_id = $super_admin_stmt->fetchColumn() ?: 1;

        $orig_phone = setting('contact_phone', '+234 (0) 803 000 0000');
        $test_phone = '+234 (0) 800 TEST 9999';

        $updated = update_setting('contact_phone', $test_phone, $super_admin_id);
        assert($updated === true, "update_setting returned false");

        $fetched_phone = setting('contact_phone');
        assert($fetched_phone === $test_phone, "setting('contact_phone') did not reflect updated value");

        // Check audit log entry
        $audit_stmt = $this->pdo->prepare("SELECT action FROM audit_logs WHERE action LIKE '%contact_phone%' ORDER BY id DESC LIMIT 1");
        $audit_stmt->execute();
        $log_details = $audit_stmt->fetchColumn();
        assert($log_details !== false, "Audit log record for update_setting was not found");
        assert(strpos($log_details, 'contact_phone') !== false, "Audit log does not mention setting key");

        // Restore original setting
        update_setting('contact_phone', $orig_phone, $super_admin_id);

        // 3. Test Dynamic CSS Engine Generation
        $css_output = generate_brand_css();
        assert(strpos($css_output, ':root') !== false, "Generated CSS missing :root declaration");
        assert(strpos($css_output, '--color-primary:') !== false, "Generated CSS missing --color-primary variable");
        assert(strpos($css_output, '[data-theme="dark"]') !== false, "Generated CSS missing dark theme declaration");

        // 4. Test Branding Asset Upload Security (Reject SVG)
        $_FILES['test_logo_svg'] = [
            'name' => 'malicious_logo.svg',
            'type' => 'image/svg+xml',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        try {
            handle_brand_asset_upload('test_logo_svg', 'assets/images/logo.png');
            assert(false, "SVG upload should have thrown an exception");
        } catch (Exception $e) {
            assert(strpos($e->getMessage(), 'SVG') !== false, "Expected SVG rejection exception message");
        }
        unset($_FILES['test_logo_svg']);

        // 5. Test Super Admin Permission Check for settings.branding.manage
        $perm_stmt = $this->pdo->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id JOIN roles r ON rp.role_id = r.id WHERE r.name = 'super_admin' AND p.name = 'settings.branding.manage'");
        $perm_stmt->execute();
        $perm_exists = $perm_stmt->fetchColumn();
        assert($perm_exists === 'settings.branding.manage', "settings.branding.manage permission not granted to super_admin role");

        echo "  [✓] BrandingTest Passed.\n";
    }
}
