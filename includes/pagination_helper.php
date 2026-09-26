<?php
/**
 * Server-Side Pagination Utility Helper
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/functions.php';

function paginate_query(PDO $pdo, $sql, $params = [], $page = 1, $per_page = 10) {
    $page = max(1, intval($page));
    $per_page = max(1, min(100, intval($per_page)));
    $offset = ($page - 1) * $per_page;

    // Count total matching records
    $count_sql = "SELECT COUNT(*) FROM (" . $sql . ") AS count_tbl";
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total = intval($stmt_count->fetchColumn());

    // Append LIMIT and OFFSET
    $paged_sql = $sql . " LIMIT " . intval($per_page) . " OFFSET " . intval($offset);
    $stmt_data = $pdo->prepare($paged_sql);
    $stmt_data->execute($params);
    $data = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    $last_page = ceil($total / $per_page);
    if ($last_page < 1) $last_page = 1;

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'last_page' => $last_page
    ];
}

function render_pagination_links($meta, $url_prefix = '') {
    if ($meta['last_page'] <= 1) {
        return '';
    }

    $current = $meta['page'];
    $last = $meta['last_page'];

    $html = '<div class="flex items-center justify-between border-t border-slate-200/50 dark:border-slate-800/80 pt-6 mt-6 text-xs">';
    $html .= '<span class="text-slate-400">Showing page <strong class="text-slate-900 dark:text-white">' . $current . '</strong> of <strong class="text-slate-900 dark:text-white">' . $last . '</strong> (' . number_format($meta['total']) . ' total)</span>';
    $html .= '<div class="inline-flex space-x-1">';

    if ($current > 1) {
        $prev_url = $url_prefix . (strpos($url_prefix, '?') !== false ? '&' : '?') . 'page=' . ($current - 1);
        $html .= '<a href="' . htmlspecialchars($prev_url) . '" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 rounded-md font-bold transition-colors">&laquo; Prev</a>';
    }

    for ($i = max(1, $current - 2); $i <= min($last, $current + 2); $i++) {
        $page_url = $url_prefix . (strpos($url_prefix, '?') !== false ? '&' : '?') . 'page=' . $i;
        $active_cls = ($i === $current) ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-amber-500 hover:text-slate-950';
        $html .= '<a href="' . htmlspecialchars($page_url) . '" class="px-3 py-1.5 rounded-md font-bold transition-colors ' . $active_cls . '">' . $i . '</a>';
    }

    if ($current < $last) {
        $next_url = $url_prefix . (strpos($url_prefix, '?') !== false ? '&' : '?') . 'page=' . ($current + 1);
        $html .= '<a href="' . htmlspecialchars($next_url) . '" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 rounded-md font-bold transition-colors">Next &raquo;</a>';
    }

    $html .= '</div></div>';
    return $html;
}

