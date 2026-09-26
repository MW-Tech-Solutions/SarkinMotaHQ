<?php
/**
 * Corporate Leadership & Team Manager - Sarkin Mota HQ
 * Enterprise Edition — Super Admin & Admin Portal
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
$user = get_logged_in_user();

// Authorization Guard: Super Admin or Admin
if (!is_super_admin() && !is_admin()) {
    set_flash_message('danger', 'Access Denied: Leadership team management is restricted to Administrators.');
    redirect('/SarkinMota/auth/dashboard.php');
}

$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Verification Failed. Please refresh and try again.";
    } else {
        $action = $_POST['post_action'] ?? '';

        // 1. ADD NEW TEAM MEMBER
        if ($action === 'add_member') {
            $name = trim(sanitize_input($_POST['name'] ?? ''));
            $role = trim(sanitize_input($_POST['role'] ?? ''));
            $bio = trim(sanitize_input($_POST['bio'] ?? ''));
            $sort_order = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
            if ($sort_order === false) $sort_order = 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $image_url = handle_image_upload('image_file', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=300&q=80');

            if (empty($name)) $errors[] = "Team member full name is required.";
            if (empty($role)) $errors[] = "Role / Title designation is required.";

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO team_members (name, role, bio, image_url, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $role, $bio, $image_url, $sort_order, $is_active]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Added Executive Team Member: " . $name . " ($role)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Leadership team member added successfully.');
                    redirect('manage-team.php');
                } catch (Exception $e) {
                    error_log("Add Team Member Error: " . $e->getMessage());
                    $errors[] = "Failed to save team member:  Please try again or contact support.";
                }
            }
        }

        // 2. UPDATE TEAM MEMBER
        elseif ($action === 'update_member') {
            $member_id = intval($_POST['member_id'] ?? 0);
            $name = trim(sanitize_input($_POST['name'] ?? ''));
            $role = trim(sanitize_input($_POST['role'] ?? ''));
            $bio = trim(sanitize_input($_POST['bio'] ?? ''));
            $sort_order = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
            if ($sort_order === false) $sort_order = 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $existing_image = sanitize_input($_POST['existing_image'] ?? '');

            $image_url = handle_image_upload('image_file', $existing_image);

            if ($member_id <= 0) $errors[] = "Invalid team member selection.";
            if (empty($name)) $errors[] = "Team member full name is required.";
            if (empty($role)) $errors[] = "Role / Title designation is required.";

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE team_members SET name = ?, role = ?, bio = ?, image_url = ?, sort_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $role, $bio, $image_url, $sort_order, $is_active, $member_id]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Updated Executive Team Member ID $member_id ($name)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Team member profile updated successfully.');
                    redirect('manage-team.php');
                } catch (Exception $e) {
                    error_log("Update Team Member Error: " . $e->getMessage());
                    $errors[] = "Failed to update team member.";
                }
            }
        }

        // 3. DELETE TEAM MEMBER
        elseif ($action === 'delete_member') {
            $member_id = intval($_POST['member_id'] ?? 0);
            if ($member_id > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
                    $stmt->execute([$member_id]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Deleted Team Member ID: " . $member_id, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Team member deleted successfully.');
                    redirect('manage-team.php');
                } catch (Exception $e) {
                    error_log("Delete Team Member Error: " . $e->getMessage());
                    $errors[] = "Failed to delete team member.";
                }
            }
        }
    }
}

// Fetch all team members
$team_members = [];
try {
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC, id ASC");
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Team Fetch Error: " . $e->getMessage());
}

$admin_page_title = 'Corporate Leadership & Team Management';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="space-y-6">

    <!-- Flash Messages -->
    <?php echo display_flash_message(); ?>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-exclamation-triangle-fill text-rose-500 text-base"></i>
                <div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700" aria-label="Dismiss notification" title="Dismiss">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Action Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
        <div>
            <div class="flex items-center space-x-2 mb-1">
                <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 rounded-full border border-amber-500/20">
                    Corporate Governance
                </span>
                <span class="text-xs text-slate-400 font-semibold">• About Us Directory</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Leadership & Executive Management Team</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage executive team members, names, designations, bios, and profile images rendered dynamically on the public About Us page.</p>
        </div>

        <button type="button" onclick="scrollToCreateForm()" class="inline-flex items-center justify-center space-x-2 px-5 py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition-all shadow-md hover:shadow-lg shrink-0">
            <i aria-hidden="true" class="bi bi-plus-circle-fill text-sm"></i>
            <span>Add Team Member</span>
        </button>
    </div>

    <!-- Main Workspace Grid: Roster List (7 Cols) + Add Form (5 Cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column: Team Members Roster (7 Cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i aria-hidden="true" class="bi bi-people-fill text-amber-500"></i>
                    <span>Configured Team Members (<?php echo count($team_members); ?>)</span>
                </h2>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sorted by Display Order</span>
            </div>

            <?php if (empty($team_members)): ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center text-slate-400 shadow-sm">
                    <i aria-hidden="true" class="bi bi-person-badge text-4xl block mb-2 text-slate-300"></i>
                    No leadership team members configured yet. Use the editor to add executives.
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($team_members as $member): ?>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm hover:shadow-md transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-start sm:items-center space-x-4">
                                <img src="<?php echo htmlspecialchars(resolve_image_url($member['image_url'] ?? 'assets/images/logo.png')); ?>" 
                                     alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                     class="h-16 w-16 rounded-2xl object-cover border border-slate-200 dark:border-slate-800 shrink-0 shadow-sm">
                                
                                <div>
                                    <div class="flex items-center space-x-2 flex-wrap">
                                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base">
                                            <?php echo htmlspecialchars($member['name']); ?>
                                        </h3>

                                        <?php if (!empty($member['is_active'])): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-wider">
                                                <i aria-hidden="true" class="bi bi-check-circle-fill mr-1"></i> Visible
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-wider">
                                                <i aria-hidden="true" class="bi bi-eye-slash-fill mr-1"></i> Hidden
                                            </span>
                                        <?php endif; ?>

                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-500">
                                            Order #<?php echo (int)$member['sort_order']; ?>
                                        </span>
                                    </div>

                                    <span class="text-xs font-bold text-amber-500 uppercase tracking-wider block mt-0.5">
                                        <?php echo htmlspecialchars($member['role']); ?>
                                    </span>

                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                        <?php echo htmlspecialchars($member['bio'] ?? 'No bio description set.'); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                                <button type="button" 
                                        onclick='openEditTeamModal(<?php echo json_encode($member, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); ?>)' 
                                        class="px-3.5 py-1.5 bg-slate-100 hover:bg-amber-500 hover:text-slate-950 dark:bg-slate-800 dark:hover:bg-amber-500 dark:hover:text-slate-950 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-all inline-flex items-center space-x-1">
                                    <i aria-hidden="true" class="bi bi-pencil-square"></i>
                                    <span>Edit</span>
                                </button>

                                <form action="manage-team.php" method="POST" onsubmit="return confirm('Delete this team member profile?');" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="post_action" value="delete_member">
                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                    <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-600 hover:text-white dark:bg-rose-950/30 dark:hover:bg-rose-600 text-rose-600 dark:text-rose-300 text-xs rounded-xl transition-all" title="Delete Member" aria-label="Delete Member">
                                        <i aria-hidden="true" class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Add New Team Member Form (5 Cols) -->
        <div class="lg:col-span-5" id="addTeamFormContainer">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 sticky top-24">
                
                <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Executive Directory Editor</span>
                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Add New Team Member</h3>
                </div>

                <form method="POST" action="manage-team.php" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="post_action" value="add_member">

                    <div>
                        <label for="name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Full Name & Honorific <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" required maxlength="120"
                               placeholder="e.g. Dr. Ken Davies" 
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label for="role" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Role / Designation <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="role" name="role" required maxlength="150"
                               placeholder="e.g. Managing Consultant & Agribusiness Director" 
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label for="sort_order" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Display Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" value="0" min="0" max="999"
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <p class="text-[10px] text-slate-400 mt-1">Lower numbers appear first on the About Us page (e.g. 1, 2, 3).</p>
                    </div>

                    <div>
                        <label for="bio" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Executive Bio / Summary
                        </label>
                        <textarea id="bio" name="bio" rows="3"
                                  placeholder="Brief professional background, qualifications, and domain expertise..." 
                                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                    </div>

                    <div>
                        <label for="image_file" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Profile Portrait Image
                        </label>
                        <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950">
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center space-x-3 cursor-pointer p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl">
                            <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 text-amber-500 focus:ring-amber-500 border-slate-300 rounded">
                            <div>
                                <span class="block text-xs font-extrabold text-slate-900 dark:text-white">Publish on About Us Page</span>
                                <span class="block text-[10px] text-slate-400">Make this profile visible to visitors on the public site.</span>
                            </div>
                        </label>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition-all shadow-md hover:shadow-lg">
                            <i aria-hidden="true" class="bi bi-check-lg mr-1.5 text-sm"></i> Save Team Member
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Edit Team Member Modal -->
<div id="editTeamModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-xl w-full shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Edit Team Member Profile</h3>
                <p class="text-xs text-slate-400">Update name, designation, bio, display order, or photo.</p>
            </div>
            <button type="button" onclick="closeEditTeamModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white" aria-label="Close modal" title="Close">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <form action="manage-team.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="post_action" value="update_member">
            <input type="hidden" id="edit_member_id" name="member_id" value="">
            <input type="hidden" id="edit_existing_image" name="existing_image" value="">

            <div>
                <label for="edit_name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Full Name & Honorific</label>
                <input type="text" id="edit_name" name="name" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="edit_role" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Role / Designation</label>
                <input type="text" id="edit_role" name="role" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="edit_sort_order" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Display Sort Order</label>
                <input type="number" id="edit_sort_order" name="sort_order" min="0" max="999" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="edit_bio" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Executive Bio / Summary</label>
                <textarea id="edit_bio" name="bio" rows="4" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Profile Photo Image</label>
                <input type="file" id="edit_image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950">
            </div>

            <div class="pt-2">
                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="h-4 w-4 text-amber-500 focus:ring-amber-500 border-slate-300 rounded">
                    <div>
                        <span class="block text-xs font-extrabold text-slate-900 dark:text-white">Publish on About Us Page</span>
                        <span class="block text-[10px] text-slate-400">Make this profile visible on the public site.</span>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeEditTeamModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs uppercase tracking-wider shadow-md">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function scrollToCreateForm() {
    const el = document.getElementById('addTeamFormContainer');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
}

function openEditTeamModal(m) {
    document.getElementById('edit_member_id').value = m.id;
    document.getElementById('edit_name').value = m.name || '';
    document.getElementById('edit_role').value = m.role || '';
    document.getElementById('edit_sort_order').value = m.sort_order || 0;
    document.getElementById('edit_bio').value = m.bio || '';
    document.getElementById('edit_existing_image').value = m.image_url || '';
    document.getElementById('edit_is_active').checked = (parseInt(m.is_active) === 1);

    document.getElementById('editTeamModal').classList.remove('hidden');
}

function closeEditTeamModal() {
    document.getElementById('editTeamModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
