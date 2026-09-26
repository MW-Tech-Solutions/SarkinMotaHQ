<?php
/**
 * Admin property catalog manager - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_role(['admin', 'staff']);
require_permission('properties.view');
$user = get_logged_in_user();

$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        // ADD listing
        if (isset($_POST['add_property'])) {
            require_permission('properties.create');
            
            $title = sanitize_input($_POST['title'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $location = sanitize_input($_POST['location'] ?? '');
            $beds = intval($_POST['beds'] ?? 0);
            $baths = intval($_POST['baths'] ?? 0);
            $area_sqft = intval($_POST['area_sqft'] ?? 0);
            $type = sanitize_input($_POST['type'] ?? 'sale');
            $category = sanitize_input($_POST['category'] ?? 'residential');
            $latitude = floatval($_POST['latitude'] ?? 9.0765);
            $longitude = floatval($_POST['longitude'] ?? 7.3986);
            $image_url = handle_image_upload('image_file', 'assets/images/logo.png');
            
            if (empty($title)) $errors[] = "Title is required.";
            if ($price <= 0) $errors[] = "Invalid price amount.";
            if (empty($location)) $errors[] = "Location is required.";
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO properties 
                        (landlord_id, title, description, price, location, latitude, longitude, beds, baths, area_sqft, type, category, listing_status, image_url, agent_name) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
                    ");
                    $stmt->execute([$user['id'], $title, $description, $price, $location, $latitude, $longitude, $beds, $baths, $area_sqft, $type, $category, $image_url, $user['name']]);
                    
                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Created Property Listing: " . $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Listing added successfully.');
                    redirect('manage-properties.php');
                } catch (Exception $e) {
                    error_log("Add Property Error: " . $e->getMessage());
                    $errors[] = "Failed to add listing to database.";
                }
            }
        }

        // UPDATE listing
        if (isset($_POST['update_property'])) {
            require_permission('properties.edit');
            $prop_id = intval($_POST['property_id'] ?? 0);
            $title = sanitize_input($_POST['title'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $location = sanitize_input($_POST['location'] ?? '');
            $beds = intval($_POST['beds'] ?? 0);
            $baths = intval($_POST['baths'] ?? 0);
            $area_sqft = intval($_POST['area_sqft'] ?? 0);
            $type = sanitize_input($_POST['type'] ?? 'sale');
            $category = sanitize_input($_POST['category'] ?? 'residential');
            $listing_status = sanitize_input($_POST['listing_status'] ?? 'active');
            $existing_image = sanitize_input($_POST['existing_image'] ?? 'assets/images/logo.png');

            $image_url = handle_image_upload('image_file', $existing_image);

            if (empty($title)) $errors[] = "Title is required.";
            if ($price <= 0) $errors[] = "Invalid price amount.";
            if (empty($location)) $errors[] = "Location is required.";

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE properties 
                        SET title = ?, description = ?, price = ?, location = ?, beds = ?, baths = ?, area_sqft = ?, type = ?, category = ?, listing_status = ?, image_url = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $price, $location, $beds, $baths, $area_sqft, $type, $category, $listing_status, $image_url, $prop_id]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Updated Property Listing ID: " . $prop_id . " (" . $title . ")", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Property listing updated successfully.');
                    redirect('manage-properties.php');
                } catch (Exception $e) {
                    error_log("Update Property Error: " . $e->getMessage());
                    $errors[] = "Failed to update property listing.";
                }
            }
        }
        
        // DELETE listing
        if (isset($_POST['delete_property'])) {
            require_permission('properties.delete');
            $prop_id = intval($_POST['property_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
                $stmt->execute([$prop_id]);

                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$user['email'], "Deleted Property Listing ID: " . $prop_id, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Listing deleted successfully.');
                redirect('manage-properties.php');
            } catch (Exception $e) {
                error_log("Delete Property Error: " . $e->getMessage());
                $errors[] = "Database deletion query failed.";
            }
        }
    }
}

// Fetch all properties
$properties = [];
try {
    $stmt = $pdo->query("SELECT * FROM properties ORDER BY id DESC");
    $properties = $stmt->fetchAll();
} catch (Exception $e) {
    $errors[] = "Properties database table not initialized yet.";
    error_log("Fetch Properties Error: " . $e->getMessage());
}

$admin_page_title = 'Property Catalog';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<?php echo display_flash_message(); ?>

<?php if (!empty($errors)): ?>
    <div class="mb-8 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
        <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- List All Properties -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm">
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-6">Current Listings</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[500px]">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Property</th>
                            <th class="py-3 px-4">Price</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($properties)): ?>
                            <tr><td colspan="5" class="py-6 text-slate-400 text-center">No properties found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($properties as $prop): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/20 transition-colors">
                                    <td class="py-3 pr-4 flex items-center space-x-3">
                                        <img src="<?php echo htmlspecialchars(resolve_image_url($prop['image_url'] ?? 'assets/images/logo.png')); ?>" alt="Cover" class="h-10 w-12 rounded-lg object-cover border border-slate-200 dark:border-slate-800 shrink-0">
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($prop['title']); ?></span>
                                            <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($prop['location']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-amber-500"><?php echo format_currency($prop['price']); ?></td>
                                    <td class="py-3 px-4 uppercase font-semibold text-slate-500 text-[10px]"><?php echo htmlspecialchars($prop['category']); ?> (<?php echo htmlspecialchars($prop['type']); ?>)</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            <?php echo htmlspecialchars($prop['listing_status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 pl-4 text-right space-x-2">
                                        <button type="button" onclick='openEditPropertyModal(<?php echo json_encode($prop, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="text-amber-600 dark:text-amber-400 font-bold hover:underline text-[10px] inline-flex items-center">
                                            <i class="bi bi-pencil-square mr-1" aria-hidden="true"></i>Edit
                                        </button>

                                        <?php if (has_permission('properties.delete')): ?>
                                            <form action="manage-properties.php" method="POST" onsubmit="return confirm('Delete this listing permanently?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="delete_property" value="1">
                                                <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                                <button type="submit" class="text-rose-500 font-bold hover:underline text-[10px]"><i class="bi bi-trash mr-1" aria-hidden="true"></i>Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Property Form Sidebar -->
    <div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm lg:sticky lg:top-24">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Create New Listing</h3>

            <form action="manage-properties.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="add_property" value="1">

                <div>
                    <label for="title" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. Maitama Executive Duplex" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="price" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Price (₦)</label>
                        <input type="number" id="price" name="price" required placeholder="180000000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label for="type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Type</label>
                        <select id="type" name="type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                            <option value="sale">For Sale</option>
                            <option value="rent">For Rent</option>
                            <option value="lease">For Lease</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="category" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Category</label>
                    <select id="category" name="category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                        <option value="industrial">Industrial</option>
                        <option value="land">Land / Agricultural</option>
                    </select>
                </div>

                <div>
                    <label for="location" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Location Address</label>
                    <input type="text" id="location" name="location" required placeholder="e.g. Maitama, Abuja" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label for="beds" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Beds</label>
                        <input type="number" id="beds" name="beds" value="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-2 text-xs">
                    </div>
                    <div>
                        <label for="baths" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Baths</label>
                        <input type="number" id="baths" name="baths" value="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-2 text-xs">
                    </div>
                    <div>
                        <label for="area_sqft" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">SqFt</label>
                        <input type="number" id="area_sqft" name="area_sqft" value="2500" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-2 text-xs">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div>
                    <label for="image_file" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Property Cover Image</label>
                    <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950">
                </div>

                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-xl hover:bg-amber-600 transition-colors shadow-md mt-4">
                    Create Listing
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Edit Property Modal -->
<div id="editPropertyModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-xl w-full shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Edit Property Listing</h3>
                <p class="text-xs text-slate-400">Update property title, price, category, status, or cover image.</p>
            </div>
            <button type="button" onclick="closeEditPropertyModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white" aria-label="Close modal" title="Close">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <form action="manage-properties.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="update_property" value="1">
            <input type="hidden" id="edit_property_id" name="property_id" value="">
            <input type="hidden" id="edit_existing_image" name="existing_image" value="">

            <div>
                <label for="edit_title" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Title / Property Name</label>
                <input type="text" id="edit_title" name="title" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit_price" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Price (₦)</label>
                    <input type="number" id="edit_price" name="price" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="edit_type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Listing Type</label>
                    <select id="edit_type" name="type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="sale">For Sale</option>
                        <option value="rent">For Rent</option>
                        <option value="lease">For Lease</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit_category" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Category</label>
                    <select id="edit_category" name="category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                        <option value="industrial">Industrial</option>
                        <option value="land">Land / Agricultural</option>
                    </select>
                </div>
                <div>
                    <label for="edit_listing_status" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</label>
                    <select id="edit_listing_status" name="listing_status" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="sold">Sold</option>
                        <option value="rented">Rented</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="edit_location" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Location Address</label>
                <input type="text" id="edit_location" name="location" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label for="edit_beds" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Beds</label>
                    <input type="number" id="edit_beds" name="beds" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-2 text-xs">
                </div>
                <div>
                    <label for="edit_baths" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Baths</label>
                    <input type="number" id="edit_baths" name="baths" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-2 text-xs">
                </div>
                <div>
                    <label for="edit_area_sqft" class="block text-[9px] font-bold text-slate-400 uppercase mb-1">SqFt</label>
                    <input type="number" id="edit_area_sqft" name="area_sqft" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-2 text-xs">
                </div>
            </div>

            <div>
                <label for="edit_description" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Description</label>
                <textarea id="edit_description" name="description" rows="3" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Current Cover Image</label>
                <div class="flex items-center space-x-3 mb-2">
                    <img id="edit_image_preview" src="" alt="Property Preview" class="h-14 w-20 object-cover rounded-xl border border-slate-700">
                    <span class="text-[11px] text-slate-400">Leave file blank to keep existing image, or upload a new image file below.</span>
                </div>
                <input type="file" id="edit_image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeEditPropertyModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
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
function openEditPropertyModal(prop) {
    document.getElementById('edit_property_id').value = prop.id;
    document.getElementById('edit_title').value = prop.title || '';
    document.getElementById('edit_price').value = prop.price || 0;
    document.getElementById('edit_type').value = prop.type || 'sale';
    document.getElementById('edit_category').value = prop.category || 'residential';
    document.getElementById('edit_listing_status').value = prop.listing_status || 'active';
    document.getElementById('edit_location').value = prop.location || '';
    document.getElementById('edit_beds').value = prop.beds || 0;
    document.getElementById('edit_baths').value = prop.baths || 0;
    document.getElementById('edit_area_sqft').value = prop.area_sqft || 0;
    document.getElementById('edit_description').value = prop.description || '';
    document.getElementById('edit_existing_image').value = prop.image_url || '';
    document.getElementById('edit_image_preview').src = resolveImageUrl(prop.image_url);
    
    document.getElementById('editPropertyModal').classList.remove('hidden');
}

function resolveImageUrl(url) {
    if (!url) return '/SarkinMota/assets/images/logo.png';
    if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0 || url.indexOf('data:') === 0) return url;
    return '/SarkinMota/' + url.replace(/^\/+/, '');
}

function closeEditPropertyModal() {
    document.getElementById('editPropertyModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
