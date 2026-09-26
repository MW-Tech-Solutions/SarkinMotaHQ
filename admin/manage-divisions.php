<?php
/**
 * Dynamic Corporate Divisions Management Interface
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

require_super_admin();
$user = get_logged_in_user();

$errors = [];
$success_msg = '';

// Handle Division Creation & Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $action = sanitize_input($_POST['action'] ?? '');

        if ($action === 'save_division') {
            $div_id = intval($_POST['division_id'] ?? 0);
            $name = sanitize_input($_POST['name'] ?? '');
            $code = strtoupper(sanitize_input($_POST['code'] ?? ''));
            $description = sanitize_input($_POST['description'] ?? '');
            $icon = sanitize_input($_POST['icon'] ?? 'bi-building');
            $status = sanitize_input($_POST['status'] ?? 'active');

            if (empty($name)) $errors[] = "Division name is required.";
            if (empty($code)) $errors[] = "Division code is required (e.g., RE, AUTO, LOG).";

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

            if (empty($errors)) {
                try {
                    if ($div_id > 0) {
                        $stmt = $pdo->prepare("UPDATE corporate_divisions SET name = ?, slug = ?, code = ?, description = ?, icon = ?, status = ? WHERE id = ?");
                        $stmt->execute([$name, $slug, $code, $description, $icon, $status, $div_id]);
                        $success_msg = "Corporate Division '{$name}' updated successfully.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO corporate_divisions (name, slug, code, description, icon, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $slug, $code, $description, $icon, $status, $user['id']]);
                        $success_msg = "New Corporate Division '{$name}' created successfully.";
                    }

                    // Audit Log
                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Saved Corporate Division: {$name} [Code: {$code}]", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                } catch (Exception $e) {
                    $errors[] = "Database Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle_status') {
            $div_id = intval($_POST['division_id'] ?? 0);
            $new_status = sanitize_input($_POST['new_status'] ?? 'active');

            if ($div_id > 0) {
                $stmt = $pdo->prepare("UPDATE corporate_divisions SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $div_id]);
                $success_msg = "Division status updated to '{$new_status}'.";
            }
        } elseif ($action === 'delete_division') {
            $div_id = intval($_POST['division_id'] ?? 0);
            if ($div_id > 0) {
                try {
                    $chk = $pdo->prepare("SELECT name FROM corporate_divisions WHERE id = ?");
                    $chk->execute([$div_id]);
                    $div_name = $chk->fetchColumn();

                    if ($div_name) {
                        $del = $pdo->prepare("DELETE FROM corporate_divisions WHERE id = ?");
                        $del->execute([$div_id]);
                        $success_msg = "Corporate Division '{$div_name}' was deleted successfully.";

                        $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                        $log->execute([$user['email'], "Deleted Corporate Division: {$div_name} [ID: {$div_id}]", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                    }
                } catch (Exception $e) {
                    $errors[] = "Cannot delete division: existing projects or tasks are linked to it. Consider deactivating instead.";
                }
            }
        }
    }
}

// Fetch all divisions with staff & project counts
$divisions = [];
try {
    $stmt = $pdo->query("
        SELECT d.*, 
               (SELECT COUNT(DISTINCT user_id) FROM user_divisions WHERE division_id = d.id) as staff_count,
               (SELECT COUNT(*) FROM corporate_projects WHERE division_id = d.id) as project_count,
               (SELECT COUNT(*) FROM staff_tasks WHERE division_id = d.id) as task_count
        FROM corporate_divisions d
        ORDER BY d.name ASC
    ");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Failed to load corporate divisions: " . $e->getMessage();
}

$page_title = "Corporate Divisions — Sarkin Mota HQ";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-slate-100 dark:bg-slate-950">
    <!-- Admin Sidebar -->
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 p-6 sm:p-10 w-full space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-amber-500 mb-1 block">Enterprise Operations</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Corporate Divisions</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage dynamic business divisions, assign staff, and oversee operations across Real Estate, Automobile, and custom units.</p>
            </div>
            <button onclick="openDivisionModal()" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center space-x-2 shadow-md">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Create New Division</span>
            </button>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-xs font-semibold">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-xs font-semibold">
                <?php foreach ($errors as $err) echo "<div>" . htmlspecialchars($err) . "</div>"; ?>
            </div>
        <?php endif; ?>

        <!-- Division Grid Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php foreach ($divisions as $div): ?>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between hover:border-amber-500/50 transition-all">
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <span class="h-12 w-12 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl font-bold">
                                <i class="bi <?php echo htmlspecialchars($div['icon'] ?: 'bi-building'); ?>"></i>
                            </span>
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full <?php echo $div['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'; ?>">
                                <?php echo htmlspecialchars($div['status']); ?>
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span><?php echo htmlspecialchars($div['name']); ?></span>
                            <span class="text-xs font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">[<?php echo htmlspecialchars($div['code']); ?>]</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed line-clamp-3">
                            <?php echo htmlspecialchars($div['description'] ?: 'No description provided for this corporate division.'); ?>
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                        <div class="grid grid-cols-3 gap-2 text-center mb-4 text-xs">
                            <div class="bg-slate-50 dark:bg-slate-950 p-2 rounded-lg">
                                <span class="block font-bold text-slate-900 dark:text-white"><?php echo $div['staff_count']; ?></span>
                                <span class="text-[10px] text-slate-400 uppercase">Staff</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-950 p-2 rounded-lg">
                                <span class="block font-bold text-slate-900 dark:text-white"><?php echo $div['project_count']; ?></span>
                                <span class="text-[10px] text-slate-400 uppercase">Projects</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-950 p-2 rounded-lg">
                                <span class="block font-bold text-slate-900 dark:text-white"><?php echo $div['task_count']; ?></span>
                                <span class="text-[10px] text-slate-400 uppercase">Tasks</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button onclick='editDivision(<?php echo json_encode($div); ?>)' class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 font-bold rounded-lg text-xs transition-all text-center">
                                Edit
                            </button>
                            <a href="/SarkinMota/admin/manage-projects.php?division_id=<?php echo $div['id']; ?>" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-bold rounded-lg text-xs transition-all text-center">
                                Projects
                            </a>
                            <form method="POST" action="manage-divisions.php" onsubmit="return confirm('Are you sure you want to delete the division <?php echo htmlspecialchars(addslashes($div['name'])); ?>?');" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="delete_division">
                                <input type="hidden" name="division_id" value="<?php echo $div['id']; ?>">
                                <button type="submit" class="py-2 px-3 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 font-bold rounded-lg text-xs transition-all flex items-center justify-center space-x-1" title="Delete Division">
                                    <i class="bi bi-trash-fill"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Modal for Create/Edit Division -->
<div id="divisionModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-6 sm:p-8 shadow-2xl relative">
        <button onclick="closeDivisionModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 text-lg">
            <i class="bi bi-x-lg"></i>
        </button>
        <h2 id="modalTitle" class="text-xl font-bold text-slate-900 dark:text-white mb-6">Create Corporate Division</h2>

        <form action="manage-divisions.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="save_division">
            <input type="hidden" name="division_id" id="form_division_id" value="0">

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Division Name *</label>
                <input type="text" name="name" id="form_name" required placeholder="e.g. Logistics & Supply Chain" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Division Code *</label>
                    <input type="text" name="code" id="form_code" required placeholder="e.g. LOG" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Bootstrap Icon Class</label>
                    <input type="text" name="icon" id="form_icon" value="bi-building" placeholder="bi-truck" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status</label>
                <select name="status" id="form_status" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Description Brief</label>
                <textarea name="description" id="form_description" rows="3" placeholder="Describe the operational mandate of this division..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="closeDivisionModal()" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider shadow-md">Save Division</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDivisionModal() {
    document.getElementById('form_division_id').value = 0;
    document.getElementById('form_name').value = '';
    document.getElementById('form_code').value = '';
    document.getElementById('form_icon').value = 'bi-building';
    document.getElementById('form_status').value = 'active';
    document.getElementById('form_description').value = '';
    document.getElementById('modalTitle').innerText = 'Create Corporate Division';
    document.getElementById('divisionModal').classList.remove('hidden');
}

function editDivision(div) {
    document.getElementById('form_division_id').value = div.id;
    document.getElementById('form_name').value = div.name;
    document.getElementById('form_code').value = div.code;
    document.getElementById('form_icon').value = div.icon || 'bi-building';
    document.getElementById('form_status').value = div.status;
    document.getElementById('form_description').value = div.description || '';
    document.getElementById('modalTitle').innerText = 'Edit Division: ' + div.name;
    document.getElementById('divisionModal').classList.remove('hidden');
}

function closeDivisionModal() {
    document.getElementById('divisionModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
