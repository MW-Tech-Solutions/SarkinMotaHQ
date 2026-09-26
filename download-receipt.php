<?php
/**
 * Verified Official Receipt Controller
 * Enterprise Edition — Sarkin Mota HQ
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';
require_once __DIR__ . '/includes/rbac_helper.php';
require_once __DIR__ . '/includes/settings_helper.php';

require_login();
$user = get_logged_in_user();

$tx_id = intval($_GET['id'] ?? 0);
if ($tx_id <= 0) {
    die("Invalid transaction request.");
}

try {
    $stmt = $pdo->prepare("
        SELECT pt.*, u.name AS user_name, u.email AS user_email, p.title AS property_title 
        FROM payment_transactions pt
        JOIN users u ON pt.user_id = u.id
        LEFT JOIN properties p ON pt.property_id = p.id
        WHERE pt.id = ?
    ");
    $stmt->execute([$tx_id]);
    $tx = $stmt->fetch();

    if (!$tx) {
        set_flash_message('danger', 'Transaction record not found.');
        redirect('/SarkinMota/auth/dashboard.php');
    }

    // Object-Level Authorization: Only transaction owner or Admin/Staff operations can access receipt
    $is_owner = ($tx['user_id'] == $user['id']) || (strcasecmp($tx['user_email'], $user['email']) === 0);
    $is_admin_staff = is_admin() || is_super_admin() || in_array($user['role'], ['admin', 'super_admin', 'staff', 'hr_manager']);

    if (!$is_owner && !$is_admin_staff) {
        set_flash_message('danger', 'Access Denied: You are not authorized to view or download this payment receipt.');
        redirect('/SarkinMota/auth/dashboard.php');
    }

    // Receipt available ONLY for genuinely paid transactions
    if ($tx['status'] !== 'paid') {
        set_flash_message('warning', 'Receipts are only generated for verified paid transactions. Current status: ' . strtoupper($tx['status']));
        redirect('/SarkinMota/auth/dashboard.php');
    }

} catch (Exception $e) {
    error_log("Receipt Generation Error: " . $e->getMessage());
    die("Error retrieving receipt details.");
}

$company_name = setting('company_name', 'Sarkin Mota HQ');
$company_short = setting('company_short_name', 'Sarkin Mota HQ');
$company_logo = setting('company_logo', 'assets/images/logo.png');
$company_tagline = setting('company_tagline');
$company_address = setting('company_address');
$company_email = setting('company_email');
$company_phone = setting('company_phone');
$primary_color = validate_hex_color(setting('primary_color'), '#f59e0b');

// Render Branded Official Receipt HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt #<?php echo htmlspecialchars($tx['transaction_ref']); ?> — <?php echo htmlspecialchars($company_short); ?></title>
    <?php require_once __DIR__ . '/includes/icon_assets.php'; ?>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 40px 20px; }
        .receipt-card { max-width: 650px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 32px; }
        .logo-img { max-height: 50px; width: auto; margin-bottom: 10px; }
        .brand { font-size: 24px; font-weight: 800; color: #0f172a; text-transform: uppercase; }
        .brand span { color: <?php echo $primary_color; ?>; }
        .badge-paid { display: inline-block; background: #ecfdf5; color: #047857; font-weight: 700; font-size: 11px; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; margin-top: 12px; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }
        .row .label { color: #64748b; font-weight: 600; }
        .row .value { font-weight: 700; color: #0f172a; }
        .total-box { background: #fffbebf5; border: 1px dashed <?php echo $primary_color; ?>; padding: 16px; border-radius: 12px; margin-top: 24px; text-align: center; }
        .total-box .amount { font-size: 28px; font-weight: 800; color: #92400e; }
        .footer { text-align: center; margin-top: 32px; font-size: 11px; color: #94a3b8; line-height: 1.6; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-card { border: none; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" style="background: <?php echo $primary_color; ?>; color: #000; border: none; padding: 8px 16px; font-weight: bold; border-radius: 8px; cursor: pointer;">
                <i class="bi bi-printer ui-icon" aria-hidden="true"></i> Print / Download PDF
            </button>
        </div>

        <div class="header">
            <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo" class="logo-img">
            <div class="brand"><?php echo htmlspecialchars($company_short); ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;"><?php echo htmlspecialchars($company_name); ?> — <?php echo htmlspecialchars($company_tagline); ?></div>
            <span class="badge-paid">VERIFIED OFFICIAL PAYMENT RECEIPT</span>
        </div>

        <div class="row"><span class="label">Transaction Reference:</span><span class="value"><?php echo htmlspecialchars($tx['transaction_ref']); ?></span></div>
        <div class="row"><span class="label">Provider Reference:</span><span class="value"><?php echo htmlspecialchars($tx['provider_ref'] ?: 'N/A'); ?></span></div>
        <div class="row"><span class="label">Payment Gateway:</span><span class="value"><?php echo htmlspecialchars($tx['provider']); ?></span></div>
        <div class="row"><span class="label">Payer Name:</span><span class="value"><?php echo htmlspecialchars($tx['user_name']); ?></span></div>
        <div class="row"><span class="label">Payer Email:</span><span class="value"><?php echo htmlspecialchars($tx['user_email']); ?></span></div>
        <?php if (!empty($tx['property_title'])): ?>
            <div class="row"><span class="label">Property Associated:</span><span class="value"><?php echo htmlspecialchars($tx['property_title']); ?></span></div>
        <?php endif; ?>
        <div class="row"><span class="label">Payment Date:</span><span class="value"><?php echo date('d M Y, H:i A', strtotime($tx['verified_at'] ?: $tx['created_at'])); ?></span></div>

        <div class="total-box">
            <div style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase;">Total Amount Verified</div>
            <div class="amount">₦<?php echo number_format($tx['received_amount'], 2); ?></div>
        </div>

        <div class="footer">
            This is an electronically generated official receipt issued by <?php echo htmlspecialchars($company_name); ?>.<br>
            <?php echo htmlspecialchars($company_address); ?>.<br>
            Support Email: <?php echo htmlspecialchars($company_email); ?> | Phone: <?php echo htmlspecialchars($company_phone); ?>
        </div>
    </div>
</body>
</html>
