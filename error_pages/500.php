<?php
/**
 * 500 Internal Server Error Page
 */
http_response_code(500);
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-24 bg-slate-50 dark:bg-gray-950 text-center min-h-[70vh] flex items-center justify-center">
    <div class="max-w-md mx-auto px-4">
        <i aria-hidden="true" class="bi bi-exclamation-triangle-fill text-5xl text-rose-500 mb-4 block"></i>
        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">500 — Server Error</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
            An unexpected error occurred while processing your request. Our technical team has been notified automatically.
        </p>
        <a href="../index.php" class="inline-block bg-amber-500 text-slate-950 font-bold text-xs px-6 py-3 rounded-xl uppercase tracking-wider shadow-md">Return Home</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

