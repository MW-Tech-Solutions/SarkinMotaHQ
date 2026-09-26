<?php
/**
 * Admin Shared Workspace Layout Component
 * Enterprise Responsive Portal — Sarkin Mota HQ
 */
$is_portal_page = true;
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/rbac_helper.php';
require_once __DIR__ . '/settings_helper.php';

$current_user = get_logged_in_user();
$admin_page_title = $admin_page_title ?? 'Admin Module';

$root_dir = realpath(__DIR__ . '/..');
$script_file = $_SERVER['SCRIPT_FILENAME'] ?? '';
$portal_depth = '';
if (!empty($script_file)) {
    $script_dir = realpath(dirname($script_file));
    if ($root_dir && $script_dir && strpos($script_dir, $root_dir) === 0) {
        $rel = trim(substr($script_dir, strlen($root_dir)), '/\\');
        if ($rel !== '') {
            $parts = array_filter(explode('/', str_replace('\\', '/', $rel)));
            $portal_depth = str_repeat('../', count($parts));
        }
    }
}
if ($portal_depth === '') {
    $uri = $_SERVER['PHP_SELF'] ?? '';
    if (preg_match('#/(admin|auth|hr|sales|staff|divisions|error_pages)/#i', $uri)) {
        $portal_depth = '../';
    }
}

$company_name = setting('company_name', 'Sarkin Mota HQ');
$company_short = setting('company_short_name', 'Sarkin Mota HQ');
$company_logo = setting('company_logo', 'assets/images/logo.png');
$company_logo_dark = setting('company_logo_dark', 'assets/images/logo.png');
?>

<div class="min-h-screen bg-slate-50 dark:bg-gray-950 flex flex-col md:flex-row">
    
    <!-- Mobile Off-Canvas Backdrop -->
    <div id="mobile-sidebar-backdrop" onclick="togglePortalSidebar(false)" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 hidden transition-opacity duration-300"></div>

    <!-- Sidebar Navigation Drawer -->
    <aside id="portal-sidebar" class="fixed top-0 left-0 bottom-0 h-screen w-64 md:w-72 bg-white dark:bg-slate-900 border-r border-slate-200/70 dark:border-slate-800 z-50 overflow-y-auto p-6 flex flex-col justify-between shadow-xl transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="space-y-6">
            
            <!-- Sidebar Header & Corporate Identity -->
            <div class="pb-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <img src="<?php echo $portal_depth . htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?> Logo" class="h-8 w-auto object-contain dark:hidden">
                        <img src="<?php echo $portal_depth . htmlspecialchars($company_logo_dark); ?>" alt="<?php echo htmlspecialchars($company_name); ?> Dark Logo" class="h-8 w-auto object-contain hidden dark:block">
                        <span class="text-base font-extrabold text-slate-900 dark:text-white uppercase tracking-tight"><?php echo htmlspecialchars($company_short); ?></span>
                    </div>
                    <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-500 block">Enterprise Control Center</span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate mt-1"><?php echo htmlspecialchars($current_user['name']); ?></span>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 border border-amber-500/20">
                        <i aria-hidden="true" class="bi bi-shield-check mr-1 text-amber-500"></i> <?php echo str_replace('_', ' ', htmlspecialchars($current_user['role'])); ?>
                    </span>
                </div>

                <!-- Mobile Close Button -->
                <button type="button" onclick="togglePortalSidebar(false)" aria-label="Close Portal Sidebar" class="md:hidden p-2 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg focus:outline-none">
                    <i aria-hidden="true" class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <!-- Sidebar Navigation Links (Unified System Color Scheme) -->
            <nav class="space-y-1 text-xs font-medium">
                <?php
                $current_role = $current_user['role'] ?? 'client';
                $current_tab = $_GET['tab'] ?? 'overview';
                $script_name = basename($_SERVER['PHP_SELF']);
                ?>

                <!-- TENANT SIDEBAR NAVIGATION MENU -->
                <?php if ($current_role === 'tenant'): ?>
                    <span class="block text-[9px] font-bold text-amber-500 uppercase tracking-widest px-3 mb-2">Tenant Services</span>
                    
                    <a id="sb-nav-quick" href="<?php echo $portal_depth; ?>auth/tenant-dashboard.php?tab=overview" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'tenant-dashboard.php' && ($current_tab === 'quick' || $current_tab === 'overview')) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-speedometer2 text-sm text-amber-500"></i>
                        <span>Tenant Dashboard</span>
                    </a>

                    <a id="sb-nav-pay_rent" href="<?php echo $portal_depth; ?>auth/tenant-dashboard.php?tab=pay_rent" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'tenant-dashboard.php' && $current_tab === 'pay_rent') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-wallet2 text-sm text-amber-500"></i>
                        <span>Pay Rent & Service Charge</span>
                    </a>

                    <a id="sb-nav-pay_electricity" href="<?php echo $portal_depth; ?>auth/tenant-dashboard.php?tab=pay_electricity" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'tenant-dashboard.php' && $current_tab === 'pay_electricity') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-lightning-charge-fill text-sm text-amber-500"></i>
                        <span>Electricity & Utilities</span>
                    </a>

                    <a id="sb-nav-receipts" href="<?php echo $portal_depth; ?>auth/tenant-dashboard.php?tab=receipts" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'tenant-dashboard.php' && $current_tab === 'receipts') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-receipt-cutoff text-sm text-amber-500"></i>
                        <span>Leases & Payment Receipts</span>
                    </a>

                    <a id="sb-nav-maintenance" href="<?php echo $portal_depth; ?>auth/tenant-dashboard.php?tab=maintenance" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'tenant-dashboard.php' && $current_tab === 'maintenance') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-tools text-sm text-amber-500"></i>
                        <span>Maintenance & Repairs</span>
                    </a>

                    <a id="sb-nav-helpdesk" href="<?php echo $portal_depth; ?>auth/tenant-dashboard.php?tab=helpdesk" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'tenant-dashboard.php' && $current_tab === 'helpdesk') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-headset text-sm text-amber-500"></i>
                        <span>Tenant Helpdesk</span>
                    </a>

                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 pt-4 mb-2">Real Estate Catalog</span>
                    <a href="<?php echo $portal_depth; ?>properties.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i aria-hidden="true" class="bi bi-building text-sm text-amber-500"></i>
                        <span>Browse Properties & Land</span>
                    </a>

                <!-- LANDLORD SIDEBAR NAVIGATION MENU -->
                <?php elseif ($current_role === 'landlord'): ?>
                    <span class="block text-[9px] font-bold text-amber-500 uppercase tracking-widest px-3 mb-2">Landlord Operations</span>
                    
                    <a id="sb-nav-quick" href="<?php echo $portal_depth; ?>auth/landlord-dashboard.php?tab=overview" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'landlord-dashboard.php' && ($current_tab === 'quick' || $current_tab === 'overview')) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-speedometer2 text-sm text-amber-500"></i>
                        <span>Landlord Overview</span>
                    </a>

                    <a id="sb-nav-roster" href="<?php echo $portal_depth; ?>auth/landlord-dashboard.php?tab=roster" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'landlord-dashboard.php' && ($current_tab === 'roster' || $current_tab === 'ledger')) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-people-fill text-sm text-amber-500"></i>
                        <span>Tenant Roster & Leases</span>
                    </a>

                    <a id="sb-nav-upload" href="<?php echo $portal_depth; ?>auth/landlord-dashboard.php?tab=upload" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'landlord-dashboard.php' && $current_tab === 'upload') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-plus-circle-fill text-sm text-amber-500"></i>
                        <span>Upload Property Listing</span>
                    </a>

                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 pt-4 mb-2">Corporate Modules</span>
                    <a href="<?php echo $portal_depth; ?>admin/manage-properties.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-properties.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-building text-sm text-amber-500"></i>
                        <span>My Listed Properties</span>
                    </a>

                <!-- CLIENT / NORMAL REGISTERED USER NAVIGATION MENU -->
                <?php elseif ($current_role === 'client'): ?>
                    <span class="block text-[9px] font-bold text-amber-500 uppercase tracking-widest px-3 mb-2">Client Portal</span>
                    
                    <a id="sb-nav-quick" href="<?php echo $portal_depth; ?>auth/client-dashboard.php?tab=overview" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'client-dashboard.php' && ($current_tab === 'quick' || $current_tab === 'overview')) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-person-badge-fill text-sm text-amber-500"></i>
                        <span>My Account Overview</span>
                    </a>

                    <a href="<?php echo $portal_depth; ?>properties.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i aria-hidden="true" class="bi bi-building text-sm text-amber-500"></i>
                        <span>Apply for Housing & Plots</span>
                    </a>

                    <a href="<?php echo $portal_depth; ?>projects.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i aria-hidden="true" class="bi bi-tree-fill text-sm text-amber-500"></i>
                        <span>Land Fields & Agribusiness</span>
                    </a>

                    <a id="sb-nav-inspections" href="<?php echo $portal_depth; ?>auth/client-dashboard.php?tab=inspections" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'client-dashboard.php' && $current_tab === 'inspections') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-calendar-check text-sm text-amber-500"></i>
                        <span>Property Tour Inspections</span>
                    </a>

                    <a id="sb-nav-consultations" href="<?php echo $portal_depth; ?>auth/client-dashboard.php?tab=consultations" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'client-dashboard.php' && $current_tab === 'consultations') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-headset text-sm text-amber-500"></i>
                        <span>Consultation Bookings</span>
                    </a>

                <!-- SUPER ADMIN / SYSTEM ADMIN / STAFF NAVIGATION MENU -->
                <?php else: ?>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 mb-2">Portal Navigation</span>
                    
                    <a id="sb-nav-quick" href="<?php echo $portal_depth; ?>auth/admin-dashboard.php?tab=quick" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'admin-dashboard.php' && ($current_tab === 'quick' || $current_tab === 'overview')) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-grid-1x2-fill text-sm text-amber-500"></i>
                        <span>Dashboard Home</span>
                    </a>

                    <?php if (has_permission('users.view')): ?>
                        <a id="sb-nav-users" href="<?php echo $portal_depth; ?>auth/admin-dashboard.php?tab=users" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'admin-dashboard.php' && $current_tab === 'users') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-people-fill text-sm text-amber-500"></i>
                            <span>User Accounts & Roles</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('payments.view')): ?>
                        <a id="sb-nav-payments" href="<?php echo $portal_depth; ?>auth/admin-dashboard.php?tab=payments" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'admin-dashboard.php' && $current_tab === 'payments') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-wallet2 text-sm text-amber-500"></i>
                            <span>Payment Ledger</span>
                        </a>
                    <?php endif; ?>

                    <?php if (is_super_admin()): ?>
<a href="<?php echo $portal_depth; ?>admin/manage-divisions.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo $script_name === 'manage-divisions.php' ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
<i aria-hidden="true" class="bi bi-diagram-3 text-amber-500"></i><span>Manage Divisions</span></a>
                        <a id="sb-nav-audits" href="<?php echo $portal_depth; ?>auth/admin-dashboard.php?tab=audits" class="sidebar-subtab-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'admin-dashboard.php' && $current_tab === 'audits') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-shield-shaded text-sm text-amber-500"></i>
                            <span>Security Audit Logs</span>
                        </a>
                    <?php endif; ?>

                    <?php if (is_super_admin() || has_permission('settings.branding.manage')): ?>
                        <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 pt-4 mb-2">System Settings</span>
                        <a href="<?php echo $portal_depth; ?>admin/manage-branding.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-branding.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-palette-fill text-sm text-amber-500"></i>
                            <span>Branding & Theme</span>
                        </a>
                        <a href="<?php echo $portal_depth; ?>admin/send-mail.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'send-mail.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-send-fill text-sm text-amber-500"></i>
                            <span>Email Dispatcher</span>
                        </a>
                    <?php endif; ?>

                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 pt-4 mb-2">Staff & Sales Portals</span>
                    
                    <a href="<?php echo $portal_depth; ?>staff/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'index.php' && strpos($_SERVER['PHP_SELF'], '/staff/') !== false) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-person-badge-fill text-sm text-amber-500"></i>
                        <span>General Staff Portal</span>
                    </a>

                    <a href="<?php echo $portal_depth; ?>sales/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'index.php' && strpos($_SERVER['PHP_SELF'], '/sales/') !== false) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-shop text-sm text-amber-500"></i>
                        <span>Sales Portal (Real Estate & Car Sales)</span>
                    </a>

                    <a href="<?php echo $portal_depth; ?>hr/my-tasks.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'my-tasks.php' || $script_name === 'task-view.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                        <i aria-hidden="true" class="bi bi-check2-square text-sm text-amber-500"></i>
                        <span>My Assigned Tasks</span>
                    </a>

                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 pt-4 mb-2">Corporate Modules</span>
                    
                    <?php if (has_permission('properties.view')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-properties.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-properties.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-building text-sm text-amber-500"></i>
                            <span>Property Catalog</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('projects.manage')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-projects.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-projects.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-folder-check text-sm text-amber-500"></i>
                            <span>Division Projects</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('assign_tasks') || is_super_admin()): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-tasks.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-tasks.php' || $script_name === 'review-tasks.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-list-task text-sm text-amber-500"></i>
                            <span>Task Control Center</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('news.manage')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-news.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-news.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-journal-text text-sm text-amber-500"></i>
                            <span>Blog & Articles</span>
                        </a>
                    <?php endif; ?>

                    <?php if (is_super_admin() || is_admin()): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-team.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-team.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-person-badge-fill text-sm text-amber-500"></i>
                            <span>Leadership & Team</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('careers.manage')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-careers.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-careers.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-person-badge text-sm text-amber-500"></i>
                            <span>Career Vacancies</span>
                        </a>
                    <?php endif; ?>

                    <?php if (is_super_admin() || is_admin() || has_permission('hr.view_dashboard') || in_array($current_role, ['hr_manager', 'hr_officer', 'department_manager', 'interviewer'])): ?>
                        <span class="block text-[9px] font-bold text-amber-500 uppercase tracking-widest px-3 pt-4 mb-2">HR & Recruitment</span>
                        
                        <a href="<?php echo $portal_depth; ?>hr/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'index.php' && strpos($_SERVER['PHP_SELF'], '/hr/') !== false) ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-speedometer2 text-sm text-amber-500"></i>
                            <span>HR Dashboard</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/vacancies.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'vacancies.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-briefcase-fill text-sm text-amber-500"></i>
                            <span>Vacancies</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/applicants.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'applicants.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-person-lines-fill text-sm text-amber-500"></i>
                            <span>Applicants & Candidates</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/interviews.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'interviews.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-calendar-event-fill text-sm text-amber-500"></i>
                            <span>Interviews</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/offers.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'offers.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-file-earmark-check-fill text-sm text-amber-500"></i>
                            <span>Job Offers</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/onboarding.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'onboarding.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-person-plus-fill text-sm text-amber-500"></i>
                            <span>Staff Onboarding</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/directory.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'directory.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-people-fill text-sm text-amber-500"></i>
                            <span>Staff Directory</span>
                        </a>

                        <a href="<?php echo $portal_depth; ?>hr/departments.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'departments.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-diagram-3-fill text-sm text-amber-500"></i>
                            <span>Departments</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('inquiries.manage')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/view-messages.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'view-messages.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-chat-left-dots-fill text-sm text-amber-500"></i>
                            <span>Client Inquiries</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('inspections.manage')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-inspections.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-inspections.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-calendar-check text-sm text-amber-500"></i>
                            <span>Inspection Tours</span>
                        </a>
                    <?php endif; ?>

                    <?php if (has_permission('consultations.manage')): ?>
                        <a href="<?php echo $portal_depth; ?>admin/manage-consultations.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($script_name === 'manage-consultations.php') ? 'text-amber-500 font-bold bg-amber-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                            <i aria-hidden="true" class="bi bi-headset text-sm text-amber-500"></i>
                            <span>Consultation Bookings</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Sidebar Sign Out Action -->
        <div class="pt-6 border-t border-slate-100 dark:border-slate-800/80">
            <a href="<?php echo $portal_depth; ?>auth/logout.php" class="w-full py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-950/60 text-xs font-bold uppercase tracking-wider rounded-xl transition-colors flex items-center justify-center space-x-2">
                <i aria-hidden="true" class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Workspace Content Canvas -->
    <main class="md:ml-72 flex-grow flex flex-col min-h-screen">
        
        <!-- Top Navigation Header Bar -->
        <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-4 md:px-8 py-3 flex items-center justify-between gap-3 z-30 shadow-sm sticky top-0">
            <div class="flex items-center space-x-3">
                <button type="button" onclick="togglePortalSidebar(true)" aria-label="Open Navigation Drawer" class="md:hidden p-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-lg hover:text-amber-500 focus:outline-none">
                    <i aria-hidden="true" class="bi bi-list text-xl"></i>
                </button>

                <div>
                    <div class="flex items-center space-x-2 text-[10px] sm:text-xs font-semibold text-slate-400">
                        <a href="<?php echo $portal_depth; ?>auth/dashboard.php" class="hover:text-amber-500">Dashboard</a>
                        <span>/</span>
                        <span class="text-amber-500 uppercase font-bold tracking-wider truncate max-w-[120px] sm:max-w-none"><?php echo htmlspecialchars($admin_page_title); ?></span>
                    </div>
                    <h1 class="text-sm sm:text-lg font-extrabold text-slate-900 dark:text-white truncate">
                        <?php echo htmlspecialchars($admin_page_title); ?>
                    </h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Single Theme Button with Dropdown (Admin Workspace) -->
                <?php if (setting('allow_theme_switching', '1') === '1'): ?>
                    <div class="relative inline-block text-left">
                        <button type="button" onclick="toggleThemeDropdown('admin-theme-dropdown')" aria-label="Toggle Theme Menu" class="theme-dropdown-btn p-2 text-slate-700 dark:text-slate-200 hover:text-amber-500 focus:outline-none rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors flex items-center justify-center">
                            <i class="bi bi-circle-half single-theme-toggle-icon text-lg"></i>
                        </button>
                        <div id="admin-theme-dropdown" class="theme-dropdown-menu hidden absolute right-0 mt-2 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 p-1 space-y-0.5 text-xs font-semibold">
                            <button type="button" onclick="setThemeMode('light'); toggleThemeDropdown('admin-theme-dropdown');" class="theme-switcher-btn w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5" data-theme-mode="light">
                                <i class="bi bi-sun text-amber-500 text-sm"></i>
                                <span>Light</span>
                            </button>
                            <button type="button" onclick="setThemeMode('dark'); toggleThemeDropdown('admin-theme-dropdown');" class="theme-switcher-btn w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5" data-theme-mode="dark">
                                <i class="bi bi-moon-stars text-indigo-400 text-sm"></i>
                                <span>Dark</span>
                            </button>
                            <button type="button" onclick="setThemeMode('system'); toggleThemeDropdown('admin-theme-dropdown');" class="theme-switcher-btn w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5" data-theme-mode="system">
                                <i class="bi bi-circle-half text-slate-400 text-sm"></i>
                                <span>Auto</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="<?php echo $portal_depth; ?>index.php" class="hidden sm:flex px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-xs font-bold rounded-xl transition-colors items-center gap-1.5" title="Main Website">
                    <i aria-hidden="true" class="bi bi-globe"></i> <span class="hidden md:inline">Main Website</span>
                </a>
                
                <div class="flex items-center space-x-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-1.5 shadow-sm">
                    <div class="h-8 w-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-xs">
                        <?php echo strtoupper(substr($current_user['name'], 0, 2)); ?>
                    </div>
                    <div class="hidden sm:block text-left pr-2">
                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 leading-tight truncate max-w-[120px]"><?php echo htmlspecialchars($current_user['name']); ?></span>
                        <span class="block text-[9px] text-slate-400 capitalize"><?php echo htmlspecialchars($current_user['role']); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Workspace Body Canvas -->
        <div class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">

<script>
function togglePortalSidebar(open) {
    const sidebar = document.getElementById('portal-sidebar');
    const backdrop = document.getElementById('mobile-sidebar-backdrop');
    if (sidebar && backdrop) {
        if (open) {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('portal-sidebar');
    if (!sidebar) return;

    // Restore saved sidebar scroll position
    const savedPos = localStorage.getItem('sarkinmota_sidebar_scroll');
    if (savedPos !== null) {
        sidebar.scrollTop = parseInt(savedPos, 10);
    }

    // Save scroll position on scroll
    sidebar.addEventListener('scroll', function() {
        localStorage.setItem('sarkinmota_sidebar_scroll', sidebar.scrollTop);
    });

    // Save scroll position when clicking any link inside sidebar
    const links = sidebar.querySelectorAll('a');
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            localStorage.setItem('sarkinmota_sidebar_scroll', sidebar.scrollTop);
        });
    });

    // Ensure active link is scrolled into view smoothly if not visible
    const activeLink = sidebar.querySelector('.bg-amber-500\\/10, .text-amber-500.font-bold');
    if (activeLink) {
        const linkRect = activeLink.getBoundingClientRect();
        const sidebarRect = sidebar.getBoundingClientRect();
        if (linkRect.top < sidebarRect.top || linkRect.bottom > sidebarRect.bottom) {
            activeLink.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }
});
</script>
