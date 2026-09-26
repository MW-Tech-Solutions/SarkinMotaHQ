<?php
/**
 * Admin thought leadership blog & CMS manager - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_role(['admin', 'staff']);
require_permission('news.manage');
$user = get_logged_in_user();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        // ADD Article
        if (isset($_POST['add_article'])) {
            $title = sanitize_input($_POST['title'] ?? '');
            $category = sanitize_input($_POST['category'] ?? 'Real Estate');
            $author = sanitize_input($_POST['author'] ?? $user['name']);
            $summary = sanitize_input($_POST['summary'] ?? '');
            $content = sanitize_input($_POST['content'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'published');
            $image_url = handle_image_upload('image_file', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80');

            if (empty($title)) $errors[] = "Title is required.";
            if (empty($content)) $errors[] = "Content is required.";
            if (empty($author)) $errors[] = "Author is required.";

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO news (title, summary, content, author, category, image_url, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$title, $summary, $content, $author, $category, $image_url, $status]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Created News Article: " . $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Article published successfully.');
                    redirect('manage-news.php');
                } catch (Exception $e) {
                    error_log("News Add Error: " . $e->getMessage());
                    $errors[] = "Failed to publish article.";
                }
            }
        }

        // UPDATE Article
        if (isset($_POST['update_article'])) {
            $art_id = intval($_POST['article_id'] ?? 0);
            $title = sanitize_input($_POST['title'] ?? '');
            $category = sanitize_input($_POST['category'] ?? 'Real Estate');
            $author = sanitize_input($_POST['author'] ?? $user['name']);
            $summary = sanitize_input($_POST['summary'] ?? '');
            $content = sanitize_input($_POST['content'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'published');
            $existing_image = sanitize_input($_POST['existing_image'] ?? '');

            $image_url = handle_image_upload('image_file', $existing_image);

            if (empty($title)) $errors[] = "Title is required.";
            if (empty($content)) $errors[] = "Content is required.";
            if (empty($author)) $errors[] = "Author is required.";

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE news SET title = ?, summary = ?, content = ?, author = ?, category = ?, image_url = ?, status = ? WHERE id = ?");
                    $stmt->execute([$title, $summary, $content, $author, $category, $image_url, $status, $art_id]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Updated News Article ID: " . $art_id . " (" . $title . ")", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Article updated successfully.');
                    redirect('manage-news.php');
                } catch (Exception $e) {
                    error_log("News Update Error: " . $e->getMessage());
                    $errors[] = "Failed to update article.";
                }
            }
        }
        
        // DELETE Article
        if (isset($_POST['delete_article'])) {
            $art_id = intval($_POST['article_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
                $stmt->execute([$art_id]);

                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$user['email'], "Deleted Article ID: " . $art_id, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Article deleted.');
                redirect('manage-news.php');
            } catch (Exception $e) {
                error_log("News Delete Error: " . $e->getMessage());
                $errors[] = "Failed to delete article.";
            }
        }
    }
}

// Fetch all news
$articles = [];
try {
    $stmt = $pdo->query("SELECT * FROM news ORDER BY id DESC");
    $articles = $stmt->fetchAll();
} catch (Exception $e) {
    $errors[] = "News database table not initialized yet.";
    error_log("News List Error: " . $e->getMessage());
}

$admin_page_title = 'Blog & CMS Publications';
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
    
    <!-- List All Articles -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm">
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-6">Published Articles</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[500px]">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Article</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Author</th>
                            <th class="py-3 pl-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($articles)): ?>
                            <tr><td colspan="5" class="py-6 text-slate-400 text-center">No news articles found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($articles as $art): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/20 transition-colors">
                                    <td class="py-3 pr-4 flex items-center space-x-3">
                                        <img src="<?php echo htmlspecialchars(resolve_image_url($art['image_url'] ?? 'assets/images/logo.png')); ?>" alt="Article cover" class="h-10 w-12 rounded-lg object-cover border border-slate-200 dark:border-slate-800 shrink-0">
                                        <span class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($art['title']); ?></span>
                                    </td>
                                    <td class="py-3 px-4 uppercase text-amber-500 font-semibold text-[10px]"><?php echo htmlspecialchars($art['category'] ?? 'General'); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"><?php echo htmlspecialchars($art['status'] ?? 'published'); ?></span></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($art['author']); ?></td>
                                    <td class="py-3 pl-4 text-right space-x-2">
                                        <button type="button" onclick='openEditArticleModal(<?php echo json_encode($art, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="text-amber-600 dark:text-amber-400 font-bold hover:underline text-[10px] inline-flex items-center">
                                            <i class="bi bi-pencil-square mr-1" aria-hidden="true"></i>Edit
                                        </button>

                                        <form action="manage-news.php" method="POST" onsubmit="return confirm('Delete article?');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="delete_article" value="1">
                                            <input type="hidden" name="article_id" value="<?php echo $art['id']; ?>">
                                            <button type="submit" class="text-rose-500 font-bold hover:underline text-[10px]"><i class="bi bi-trash mr-1" aria-hidden="true"></i>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Article Form Sidebar -->
    <div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm lg:sticky lg:top-24">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Create New Article</h3>

            <form action="manage-news.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="add_article" value="1">

                <div>
                    <label for="title" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Article Title</label>
                    <input type="text" id="title" name="title" required placeholder="Title of article..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="category" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Category</label>
                        <select id="category" name="category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                            <option value="Real Estate">Real Estate</option>
                            <option value="Agriculture">Agriculture</option>
                            <option value="Environmental">Environmental</option>
                            <option value="Development">Development</option>
                        </select>
                    </div>
                    <div>
                        <label for="author" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Author</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label for="summary" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Summary Brief</label>
                    <textarea id="summary" name="summary" rows="2" placeholder="Short intro summary..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div>
                    <label for="content" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Full Article Body</label>
                    <textarea id="content" name="content" rows="5" required placeholder="Write article body content..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div>
                    <label for="image_file" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Article Image</label>
                    <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950">
                </div>

                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-xl hover:bg-amber-600 transition-colors shadow-md mt-4">
                    Publish Article
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Edit Article Modal -->
<div id="editArticleModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-xl w-full shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Edit Article Publication</h3>
                <p class="text-xs text-slate-400">Update title, category, author, summary, content, or featured image.</p>
            </div>
            <button type="button" onclick="closeEditArticleModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white" aria-label="Close modal" title="Close">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <form action="manage-news.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="update_article" value="1">
            <input type="hidden" id="edit_article_id" name="article_id" value="">
            <input type="hidden" id="edit_existing_art_image" name="existing_image" value="">

            <div>
                <label for="edit_art_title" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Article Title</label>
                <input type="text" id="edit_art_title" name="title" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label for="edit_art_category" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Category</label>
                    <select id="edit_art_category" name="category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="Real Estate">Real Estate</option>
                        <option value="Agriculture">Agriculture</option>
                        <option value="Environmental">Environmental</option>
                        <option value="Development">Development</option>
                    </select>
                </div>
                <div>
                    <label for="edit_art_author" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Author</label>
                    <input type="text" id="edit_art_author" name="author" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label for="edit_art_status" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</label>
                    <select id="edit_art_status" name="status" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="edit_art_summary" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Summary Brief</label>
                <textarea id="edit_art_summary" name="summary" rows="2" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label for="edit_art_content" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Full Article Body</label>
                <textarea id="edit_art_content" name="content" rows="6" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Featured Article Image</label>
                <input type="file" id="edit_art_image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeEditArticleModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
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
function openEditArticleModal(art) {
    document.getElementById('edit_article_id').value = art.id;
    document.getElementById('edit_art_title').value = art.title || '';
    document.getElementById('edit_art_category').value = art.category || 'Real Estate';
    document.getElementById('edit_art_author').value = art.author || '';
    document.getElementById('edit_art_status').value = art.status || 'published';
    document.getElementById('edit_art_summary').value = art.summary || '';
    document.getElementById('edit_art_content').value = art.content || '';
    document.getElementById('edit_existing_art_image').value = art.image_url || '';
    
    document.getElementById('editArticleModal').classList.remove('hidden');
}

function closeEditArticleModal() {
    document.getElementById('editArticleModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
