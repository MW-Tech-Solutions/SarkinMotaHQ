<?php
/**
 * Official Job Offer Letter PDF Generator
 * Sarkin Mota HQ — Enterprise HR Module
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$offer_id = (int)($_GET['id'] ?? 0);
$token = trim($_GET['token'] ?? '');

if ($offer_id <= 0 && empty($token)) {
    die("Invalid offer letter request.");
}

$offer = null;
try {
    if (!empty($token)) {
        $stmt = $pdo->prepare("
            SELECT jo.*, cp.applicant_name, cp.applicant_email, cp.applicant_phone,
                   d.name AS department_name, d.code AS department_code,
                   mgr.name AS reporting_manager_name
            FROM job_offers jo
            JOIN candidate_profiles cp ON jo.candidate_profile_id = cp.id
            LEFT JOIN departments d ON jo.department_id = d.id
            LEFT JOIN users mgr ON jo.reporting_manager_id = mgr.id
            WHERE jo.token = ?
        ");
        $stmt->execute([$token]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        require_login();
        $user = get_logged_in_user();
        if (!has_permission('hr.offers.create') && !has_permission('hr.view_dashboard') && !is_admin()) {
            die("Access Denied: You do not possess permission to view official offer letters.");
        }

        $stmt = $pdo->prepare("
            SELECT jo.*, cp.applicant_name, cp.applicant_email, cp.applicant_phone,
                   d.name AS department_name, d.code AS department_code,
                   mgr.name AS reporting_manager_name
            FROM job_offers jo
            JOIN candidate_profiles cp ON jo.candidate_profile_id = cp.id
            LEFT JOIN departments d ON jo.department_id = d.id
            LEFT JOIN users mgr ON jo.reporting_manager_id = mgr.id
            WHERE jo.id = ?
        ");
        $stmt->execute([$offer_id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Offer PDF Fetch Error: " . $e->getMessage());
    die("Error retrieving job offer document.");
}

if (!$offer) {
    die("Job offer record not found or link has expired.");
}

// Compensation visibility check
$can_view_comp = true;
if (is_logged_in() && empty($token)) {
    $can_view_comp = has_permission('hr.offers.view_compensation') || is_super_admin();
}

$company_name = setting('company_name', 'Sarkin Mota HQ');
$company_short = setting('company_short_name', 'Sarkin Mota HQ');
$company_tagline = setting('company_tagline', 'Enterprise Real Estate & Strategic Advisory');
$company_address = setting('company_address', 'Plot 1024, Diplomatic Zone, Maitama, Abuja - FCT, Nigeria');
$company_email = setting('company_email', 'careers@sarkinmotahq.com');
$company_phone = setting('company_phone', '+234 803 000 7788');
$company_logo = setting('company_logo', 'assets/images/logo.png');
$primary_color = validate_hex_color(setting('primary_color'), '#f59e0b');

$ref_no = "KH-OFF-" . date('Y', strtotime($offer['created_at'])) . "-" . str_pad($offer['id'], 4, '0', STR_PAD_LEFT);
$auto_print = isset($_GET['print']) || isset($_GET['pdf']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Employment Offer Letter - <?= htmlspecialchars($offer['applicant_name']) ?> (Ref: <?= $ref_no ?>)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 40px 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .letter-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 50px 60px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            position: relative;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-b: 3px solid <?= $primary_color ?>;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }
        .company-subtitle {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }
        .ref-badge {
            text-align: right;
            font-size: 11px;
            color: #64748b;
        }
        .ref-badge .ref-no {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            display: block;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .meta-table td {
            padding: 10px 16px;
            font-size: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-table td.label {
            font-weight: 700;
            color: #475569;
            width: 25%;
            background: #f1f5f9;
        }
        .meta-table td.val {
            font-weight: 700;
            color: #0f172a;
        }
        .letter-body p {
            margin-bottom: 16px;
            text-align: justify;
        }
        .comp-box {
            background: #ecfdf5;
            border: 1.5px dashed #059669;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .comp-title {
            font-size: 11px;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .comp-amount {
            font-size: 26px;
            font-weight: 800;
            color: #065f46;
            margin-top: 4px;
        }
        .signature-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #f1f5f9;
        }
        .sig-block {
            width: 45%;
        }
        .sig-line {
            border-bottom: 1.5px solid #0f172a;
            height: 40px;
            margin-bottom: 8px;
        }
        .sig-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 12px;
        }
        .sig-role {
            font-size: 11px;
            color: #64748b;
        }
        .no-print-toolbar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: justify-between;
            align-items: center;
        }
        .btn-print {
            background: <?= $primary_color ?>;
            color: #0f172a;
            font-weight: 800;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(245,158,11,0.3);
            text-decoration: none;
        }
        .btn-print:hover {
            opacity: 0.9;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .letter-card {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-toolbar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-toolbar">
        <div style="font-size: 12px; font-weight: 700; color: #64748b;">
            <i class="bi bi-file-earmark-pdf-fill text-amber-500"></i> Formal Offer Document (PDF Ready)
        </div>
        <button onclick="window.print()" class="btn-print">
            <i class="bi bi-printer-fill"></i> Print / Save as PDF
        </button>
    </div>

    <div class="letter-card">
        <!-- Letterhead Header -->
        <div class="header-bar">
            <div>
                <img src="../<?= htmlspecialchars($company_logo) ?>" alt="Logo" style="max-height: 45px; margin-bottom: 6px;" onerror="this.style.display='none'">
                <div class="company-title"><?= htmlspecialchars($company_short) ?></div>
                <div class="company-subtitle"><?= htmlspecialchars($company_name) ?> • <?= htmlspecialchars($company_tagline) ?></div>
            </div>
            <div class="ref-badge">
                <span class="ref-no"><?= $ref_no ?></span>
                <span>Date: <?= date('F d, Y', strtotime($offer['created_at'])) ?></span>
            </div>
        </div>

        <!-- Candidate Address Block -->
        <div style="margin-bottom: 25px;">
            <strong>To:</strong><br>
            <span style="font-size: 15px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($offer['applicant_name']) ?></span><br>
            <span>Email: <?= htmlspecialchars($offer['applicant_email']) ?></span><br>
            <?php if (!empty($offer['applicant_phone'])): ?>
                <span>Phone: <?= htmlspecialchars($offer['applicant_phone']) ?></span><br>
            <?php endif; ?>
        </div>

        <!-- Offer Title -->
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin: 0; letter-spacing: 0.5px;">
                OFFICIAL OFFER OF EMPLOYMENT
            </h2>
            <div style="font-size: 12px; color: <?= $primary_color ?>; font-weight: 700; text-transform: uppercase; margin-top: 4px;">
                Position: <?= htmlspecialchars($offer['position_title']) ?>
            </div>
        </div>

        <!-- Position & Terms Table -->
        <table class="meta-table">
            <tr>
                <td class="label">Job Designation:</td>
                <td class="val"><?= htmlspecialchars($offer['position_title']) ?></td>
                <td class="label">Department:</td>
                <td class="val"><?= htmlspecialchars($offer['department_name'] ?? 'Administration') ?> (<?= htmlspecialchars($offer['department_code'] ?? 'ADM') ?>)</td>
            </tr>
            <tr>
                <td class="label">Employment Type:</td>
                <td class="val"><?= ucfirst(str_replace('_', ' ', $offer['employment_type'])) ?></td>
                <td class="label">Proposed Start Date:</td>
                <td class="val"><?= date('F d, Y', strtotime($offer['start_date'])) ?></td>
            </tr>
            <tr>
                <td class="label">Reporting Line:</td>
                <td class="val"><?= htmlspecialchars($offer['reporting_manager_name'] ?? 'Head of Department') ?></td>
                <td class="label">Offer Expiry Date:</td>
                <td class="val"><?= date('F d, Y', strtotime($offer['expiry_date'])) ?></td>
            </tr>
        </table>

        <!-- Letter Body -->
        <div class="letter-body">
            <p>Dear <strong><?= htmlspecialchars($offer['applicant_name']) ?></strong>,</p>
            
            <p>On behalf of <strong><?= htmlspecialchars($company_name) ?></strong>, we are pleased to formally offer you the position of <strong><?= htmlspecialchars($offer['position_title']) ?></strong> within our <strong><?= htmlspecialchars($offer['department_name'] ?? 'Corporate') ?></strong> division.</p>
            
            <p>We were highly impressed by your professional background, technical expertise, and performance throughout our evaluation process. We believe your contributions will be key to advancing our strategic initiatives and maintaining enterprise excellence.</p>

            <!-- Compensation Box -->
            <?php if ($can_view_comp): ?>
                <div class="comp-box">
                    <div class="comp-title">Approved Annual Total Remuneration Package</div>
                    <div class="comp-amount">₦<?= number_format($offer['compensation_amount'], 2) ?> per annum</div>
                    <div style="font-size: 11px; color: #047857; margin-top: 4px;">Subject to statutory taxes, pension contributions, and performance reviews as detailed in the employment agreement.</div>
                </div>
            <?php else: ?>
                <div class="comp-box" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="comp-title" style="color: #64748b;">Remuneration Package Details</div>
                    <div class="comp-amount" style="color: #475569; font-size: 18px;">CONFIDENTIAL COMPENSATION PACKAGE</div>
                </div>
            <?php endif; ?>

            <p><strong>Terms and Conditions:</strong></p>
            <ul style="padding-left: 20px; margin-bottom: 20px; font-size: 12px;">
                <li style="margin-bottom: 6px;">Your employment will be governed by the official policies, code of conduct, and employment regulations of <?= htmlspecialchars($company_name) ?>.</li>
                <li style="margin-bottom: 6px;">You will undergo an initial orientation and onboarding process starting on your effective start date of <strong><?= date('F d, Y', strtotime($offer['start_date'])) ?></strong>.</li>
                <li style="margin-bottom: 6px;">This offer is conditional upon the successful completion of account provisioning, document verification, and policy acknowledgments.</li>
            </ul>

            <p>Please indicate your acceptance of this offer by signing below and returning a copy to HR on or before <strong><?= date('F d, Y', strtotime($offer['expiry_date'])) ?></strong>.</p>

            <p>We look forward to welcoming you to the <strong><?= htmlspecialchars($company_short) ?></strong> family and working together toward shared growth.</p>

            <p>Yours Sincerely,</p>
        </div>

        <!-- Signature Grid -->
        <div class="signature-grid">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-name">Authorized Signatory</div>
                <div class="sig-role">Human Resources & Talent Management<br><?= htmlspecialchars($company_name) ?></div>
            </div>

            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-name"><?= htmlspecialchars($offer['applicant_name']) ?></div>
                <div class="sig-role">Candidate Acceptance Signature & Date<br>Status: <?= strtoupper($offer['status']) ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 15px;">
            <?= htmlspecialchars($company_name) ?> • <?= htmlspecialchars($company_address) ?><br>
            Official HR Email: <?= htmlspecialchars($company_email) ?> | Tel: <?= htmlspecialchars($company_phone) ?>
        </div>
    </div>

    <?php if ($auto_print): ?>
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>
</html>
