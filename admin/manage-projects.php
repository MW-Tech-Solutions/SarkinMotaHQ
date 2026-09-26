<?php
/**
 * Corporate Projects & Listings Management Interface
 * Dynamic Architecture for Real Estate, Automobile, and custom corporate divisions.
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

require_login();
$user = get_logged_in_user();

// RBAC: Require permission to view or manage projects
if (!has_permission('manage_all_projects') && !has_permission('view_division_projects')) {
    set_flash_message('danger', 'Access Denied: You do not have permission to view corporate projects.');
    redirect('/SarkinMota/auth/dashboard.php');
}

$errors = [];
$success_msg = '';

$divisions = get_all_divisions(true);
$user_div_ids = get_user_division_ids($user['id']);

$selected_division_id = intval($_GET['division_id'] ?? 0);

// Filter by server-side assigned division if staff
if ($user['role'] !== 'super_admin' && $user['role'] !== 'admin') {
    if ($selected_division_id > 0 && !in_array($selected_division_id, $user_div_ids)) {
        require_division_access($selected_division_id);
    }
}

// Handle Form Submission: Create/Edit Corporate Project
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $action = sanitize_input($_POST['action'] ?? '');

        if ($action === 'save_project') {
            $project_id = intval($_POST['project_id'] ?? 0);
            $division_id = intval($_POST['division_id'] ?? 0);
            $title = sanitize_input($_POST['title'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $priority = sanitize_input($_POST['priority'] ?? 'Normal');
            $status = sanitize_input($_POST['status'] ?? 'active');

            if ($division_id <= 0) $errors[] = "Please select a Corporate Division.";
            if (empty($title)) $errors[] = "Project title is required.";

            // Server-side division authorization for staff
            if ($user['role'] !== 'super_admin' && $user['role'] !== 'admin') {
                require_division_access($division_id);
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    // Get division code for reference
                    $stmt_c = $pdo->prepare("SELECT code, slug, name FROM corporate_divisions WHERE id = ?");
                    $stmt_c->execute([$division_id]);
                    $div_info = $stmt_c->fetch(PDO::FETCH_ASSOC);
                    $div_code = $div_info['code'] ?? 'PRJ';
                    $div_slug = $div_info['slug'] ?? '';

                    if ($project_id > 0) {
                        $stmt = $pdo->prepare("UPDATE corporate_projects SET division_id = ?, title = ?, description = ?, priority = ?, status = ? WHERE id = ?");
                        $stmt->execute([$division_id, $title, $description, $priority, $status, $project_id]);
                    } else {
                        $ref_number = generate_project_reference($div_code);
                        $stmt = $pdo->prepare("INSERT INTO corporate_projects (division_id, title, reference_number, description, priority, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$division_id, $title, $ref_number, $description, $priority, $status, $user['id']]);
                        $project_id = $pdo->lastInsertId();
                    }

                    // Handle Division-Specific Sub-Details
                    if ($div_slug === 'real-estate' || str_contains(strtolower($div_info['name'] ?? ''), 'real estate')) {
                        $prop_type = sanitize_input($_POST['property_type'] ?? 'residential');
                        $trans_type = sanitize_input($_POST['transaction_type'] ?? 'sale');
                        $location = sanitize_input($_POST['location'] ?? '');
                        $price = floatval($_POST['price'] ?? 0);
                        $bedrooms = intval($_POST['bedrooms'] ?? 0);
                        $bathrooms = intval($_POST['bathrooms'] ?? 0);
                        $size = intval($_POST['property_size'] ?? 0);

                        $re_stmt = $pdo->prepare("
                            INSERT INTO real_estate_project_details (project_id, property_type, transaction_type, location, price, bedrooms, bathrooms, property_size) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE property_type=VALUES(property_type), transaction_type=VALUES(transaction_type), location=VALUES(location), price=VALUES(price), bedrooms=VALUES(bedrooms), bathrooms=VALUES(bathrooms), property_size=VALUES(property_size)
                        ");
                        $re_stmt->execute([$project_id, $prop_type, $trans_type, $location, $price, $bedrooms, $bathrooms, $size]);

                    } elseif ($div_slug === 'automobile' || str_contains(strtolower($div_info['name'] ?? ''), 'automobile')) {
                        $make = sanitize_input($_POST['make'] ?? 'Toyota');
                        $model = sanitize_input($_POST['model'] ?? 'Camry');
                        $year = intval($_POST['year'] ?? date('Y'));
                        $transmission = sanitize_input($_POST['transmission'] ?? 'Automatic');
                        $fuel_type = sanitize_input($_POST['fuel_type'] ?? 'Petrol');
                        $mileage = sanitize_input($_POST['mileage'] ?? '0 km');
                        $vehicle_condition = sanitize_input($_POST['vehicle_condition'] ?? 'brand_new');
                        $price = floatval($_POST['price'] ?? 0);
                        $location = sanitize_input($_POST['location'] ?? 'Lagos, Nigeria');

                        // File Upload Handler for Car Photo
                        $cover_image_path = null;
                        if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['cover_image_file'];
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                            if (in_array($ext, $allowed)) {
                                $target_dir = __DIR__ . '/../assets/images/vehicles/';
                                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                                $filename = 'car_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                                if (move_uploaded_file($file['tmp_name'], $target_dir . $filename)) {
                                    $cover_image_path = 'assets/images/vehicles/' . $filename;
                                }
                            }
                        }

                        if ($cover_image_path) {
                            $auto_stmt = $pdo->prepare("
                                INSERT INTO automobile_project_details (project_id, make, model, year, transmission, fuel_type, mileage, vehicle_condition, price, location, cover_image) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE make=VALUES(make), model=VALUES(model), year=VALUES(year), transmission=VALUES(transmission), fuel_type=VALUES(fuel_type), mileage=VALUES(mileage), vehicle_condition=VALUES(vehicle_condition), price=VALUES(price), location=VALUES(location), cover_image=VALUES(cover_image)
                            ");
                            $auto_stmt->execute([$project_id, $make, $model, $year, $transmission, $fuel_type, $mileage, $vehicle_condition, $price, $location, $cover_image_path]);
                        } else {
                            $auto_stmt = $pdo->prepare("
                                INSERT INTO automobile_project_details (project_id, make, model, year, transmission, fuel_type, mileage, vehicle_condition, price, location) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE make=VALUES(make), model=VALUES(model), year=VALUES(year), transmission=VALUES(transmission), fuel_type=VALUES(fuel_type), mileage=VALUES(mileage), vehicle_condition=VALUES(vehicle_condition), price=VALUES(price), location=VALUES(location)
                            ");
                            $auto_stmt->execute([$project_id, $make, $model, $year, $transmission, $fuel_type, $mileage, $vehicle_condition, $price, $location]);
                        }
                    }

                    $pdo->commit();
                    $success_msg = "Corporate Project '{$title}' saved successfully.";
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $errors[] = "Failed to save project: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_project') {
            $project_id = intval($_POST['project_id'] ?? 0);
            if ($project_id > 0) {
                try {
                    $del = $pdo->prepare("DELETE FROM corporate_projects WHERE id = ?");
                    $del->execute([$project_id]);
                    $success_msg = "Corporate Project deleted successfully.";
                } catch (Exception $e) {
                    $errors[] = "Failed to delete project: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Projects List
$projects = [];
try {
    $sql = "
        SELECT p.*, d.name as division_name, d.code as division_code, d.slug as division_slug,
               re.price as re_price, re.location as re_location,
               auto.make as auto_make, auto.model as auto_model, auto.year as auto_year, auto.price as auto_price, auto.cover_image as auto_cover
        FROM corporate_projects p
        JOIN corporate_divisions d ON p.division_id = d.id
        LEFT JOIN real_estate_project_details re ON p.id = re.project_id
        LEFT JOIN automobile_project_details auto ON p.id = auto.project_id
    ";

    $where = [];
    $params = [];

    // Filter by selected division dropdown
    if ($selected_division_id > 0) {
        $where[] = "p.division_id = ?";
        $params[] = $selected_division_id;
    }

    // Server-side restriction for non-admin staff
    if ($user['role'] !== 'super_admin' && $user['role'] !== 'admin') {
        if (!empty($user_div_ids)) {
            $in_clause = implode(',', array_map('intval', $user_div_ids));
            $where[] = "p.division_id IN ({$in_clause})";
        } else {
            $where[] = "1=0";
        }
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Failed to load projects: " . $e->getMessage();
}

$page_title = "Corporate Projects — Sarkin Mota HQ";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-slate-100 dark:bg-slate-950">
    <!-- Admin Sidebar -->
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 p-6 sm:p-10 w-full space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-amber-500 mb-1 block">Enterprise Asset Catalog</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Corporate Projects & Listings</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage Real Estate estates, Automobile car inventories, and multi-division projects.</p>
            </div>
            <button onclick="openProjectModal()" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center space-x-2 shadow-md">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Add New Project / Listing</span>
            </button>
        </div>

        <!-- Division Filter Dropdown -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center space-x-3 w-full sm:w-auto">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter Division:</span>
                <select onchange="window.location.href='manage-projects.php?division_id='+this.value" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="0">All Authorized Divisions</option>
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?php echo $div['id']; ?>" <?php echo $selected_division_id === (int)$div['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($div['name']); ?> [<?php echo htmlspecialchars($div['code']); ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <span class="text-xs text-slate-400 font-semibold">Total Projects: <?php echo count($projects); ?></span>
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

        <!-- Projects Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="p-4 font-bold">Ref Code</th>
                            <th class="p-4 font-bold">Project / Listing Title</th>
                            <th class="p-4 font-bold">Division</th>
                            <th class="p-4 font-bold">Details / Valuation</th>
                            <th class="p-4 font-bold">Priority</th>
                            <th class="p-4 font-bold">Status</th>
                            <th class="p-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                                    No corporate projects found for the selected division criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $prj): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/50 transition-colors">
                                    <td class="p-4 font-mono font-bold text-slate-400"><?php echo htmlspecialchars($prj['reference_number']); ?></td>
                                    <td class="p-4 font-bold text-slate-900 dark:text-white">
                                        <div class="flex items-center space-x-3">
                                            <?php if (!empty($prj['auto_cover'])): ?>
                                                <img src="../<?php echo htmlspecialchars($prj['auto_cover']); ?>" alt="Car" class="w-10 h-8 object-cover rounded-lg border border-slate-200">
                                            <?php endif; ?>
                                            <div>
                                                <span class="block"><?php echo htmlspecialchars($prj['title']); ?></span>
                                                <span class="block text-[10px] text-slate-400 truncate max-w-xs font-normal"><?php echo htmlspecialchars($prj['description'] ?: 'No description'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                            <?php echo htmlspecialchars($prj['division_name']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($prj['division_slug'] === 'real-estate'): ?>
                                            <span class="block font-bold text-emerald-600 dark:text-emerald-400">₦<?php echo number_format($prj['re_price'] ?? 0, 2); ?></span>
                                            <span class="block text-[10px] text-slate-400"><?php echo htmlspecialchars($prj['re_location'] ?: 'Lagos / Abuja'); ?></span>
                                        <?php elseif ($prj['division_slug'] === 'automobile'): ?>
                                            <span class="block font-bold text-emerald-600 dark:text-emerald-400">₦<?php echo number_format($prj['auto_price'] ?? 0, 2); ?></span>
                                            <span class="block text-[10px] text-slate-400"><?php echo htmlspecialchars(($prj['auto_make'] ?? '') . ' ' . ($prj['auto_model'] ?? '') . ' (' . ($prj['auto_year'] ?? '') . ')'); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            <?php echo htmlspecialchars($prj['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $prj['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-500/10 text-slate-400'; ?>">
                                            <?php echo htmlspecialchars($prj['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <form method="POST" action="manage-projects.php" onsubmit="return confirm('Delete project <?php echo htmlspecialchars(addslashes($prj['title'])); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="action" value="delete_project">
                                                <input type="hidden" name="project_id" value="<?php echo $prj['id']; ?>">
                                                <button type="submit" class="px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white dark:bg-rose-950/40 text-xs font-bold rounded-lg transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Create/Edit Project -->
<div id="projectModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeProjectModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 text-lg">
            <i class="bi bi-x-lg"></i>
        </button>
        <h2 id="projModalTitle" class="text-xl font-bold text-slate-900 dark:text-white mb-6">Create Corporate Project</h2>

        <form action="manage-projects.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="save_project">
            <input type="hidden" name="project_id" id="proj_id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Corporate Division *</label>
                    <select name="division_id" id="proj_division_id" required onchange="toggleDivisionFields(this.value)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="">-- Choose Division --</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?php echo $div['id']; ?>" data-slug="<?php echo $div['slug']; ?>">
                                <?php echo htmlspecialchars($div['name']); ?> [<?php echo htmlspecialchars($div['code']); ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Priority</label>
                    <select name="priority" id="proj_priority" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none">
                        <option value="Normal">Normal</option>
                        <option value="Low">Low</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Project / Listing Title *</label>
                <input type="text" name="title" id="proj_title" required placeholder="e.g. Maitama Executive Sky Villas / 2026 Mercedes-Benz G63 AMG" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <!-- Dynamic Real Estate Sub-Fields -->
            <div id="real_estate_fields" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                <h4 class="text-xs font-bold text-amber-500 uppercase tracking-wider">Real Estate Listing Parameters</h4>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="location" placeholder="Location Address (e.g. Maitama, Abuja)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="number" name="price" placeholder="Valuation Price (₦)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <input type="number" name="bedrooms" placeholder="Bedrooms" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="number" name="bathrooms" placeholder="Bathrooms" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="number" name="property_size" placeholder="Size (Sq Ft)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                </div>
            </div>

            <!-- Dynamic Automobile Sub-Fields -->
            <div id="automobile_fields" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3 hidden">
                <h4 class="text-xs font-bold text-amber-500 uppercase tracking-wider">Automobile Specification Parameters</h4>
                <div class="grid grid-cols-3 gap-3">
                    <input type="text" name="make" placeholder="Make (e.g. Toyota, Mercedes)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="text" name="model" placeholder="Model (e.g. Camry, G63)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="number" name="year" placeholder="Year (e.g. 2026)" value="2026" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <select name="vehicle_condition" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                        <option value="brand_new">Brand New</option>
                        <option value="foreign_used">Foreign Used (Tokunbo)</option>
                        <option value="local_used">Local Used</option>
                    </select>
                    <input type="number" name="price" placeholder="Price (₦)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="text" name="location" placeholder="Showroom Location" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <input type="text" name="transmission" placeholder="Transmission" value="Automatic" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="text" name="fuel_type" placeholder="Fuel Type" value="Petrol" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                    <input type="text" name="mileage" placeholder="Mileage (e.g. 0 km)" value="0 km" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Car Cover Photograph (File Upload)</label>
                    <input type="file" name="cover_image_file" accept="image/*" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-1.5 text-xs text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Project Description</label>
                <textarea name="description" id="proj_description" rows="3" placeholder="Enter detailed corporate project brief..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="closeProjectModal()" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider shadow-md">Save Project</button>
            </div>
        </form>
    </div>
</div>

<script>
function openProjectModal() {
    document.getElementById('projectModal').classList.remove('hidden');
}
function closeProjectModal() {
    document.getElementById('projectModal').classList.add('hidden');
}
function toggleDivisionFields(divId) {
    const select = document.getElementById('proj_division_id');
    const selectedOpt = select.options[select.selectedIndex];
    const slug = selectedOpt ? selectedOpt.getAttribute('data-slug') : '';

    const reFields = document.getElementById('real_estate_fields');
    const autoFields = document.getElementById('automobile_fields');

    if (slug === 'real-estate') {
        reFields.classList.remove('hidden');
        autoFields.classList.add('hidden');
    } else if (slug === 'automobile') {
        autoFields.classList.remove('hidden');
        reFields.classList.add('hidden');
    } else {
        reFields.classList.add('hidden');
        autoFields.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
