<?php
/**
 * 404 Page Not Found Error Page
 */
http_response_code(404);
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-24 bg-slate-50 dark:bg-gray-950 text-center min-h-[70vh] flex items-center justify-center">
    <div class="max-w-md mx-auto px-4">
        <i aria-hidden="true" class="bi bi-compass text-5xl text-amber-500 mb-4 block"></i>
        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">404 — Page Not Found</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
            The page, property catalog item, or publication you requested could not be located on our server.
        </p>
        <a href="../index.php" class="inline-block bg-amber-500 text-slate-950 font-bold text-xs px-6 py-3 rounded-xl uppercase tracking-wider shadow-md"><i class="bi bi-arrow-left ui-icon" aria-hidden="true"></i> Back to Main Website</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

