<?php
require_once __DIR__ . '/../config/db.php';

$tables = ['system_settings', 'corporate_divisions', 'corporate_projects', 'real_estate_project_details', 'automobile_project_details', 'users'];

foreach ($tables as $tbl) {
    try {
        $stmt = $pdo->query("SELECT * FROM `{$tbl}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            foreach ($r as $col => $val) {
                if (is_string($val) && strpos($val, '&amp;') !== false) {
                    echo "Found &amp; in Table '{$tbl}', Column '{$col}', ID/Key '" . ($r['id'] ?? $r['setting_key'] ?? '') . "': " . $val . "\n";
                    
                    // Decode &amp; back to &
                    $clean_val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    // If still has &amp;, decode again
                    while (strpos($clean_val, '&amp;') !== false) {
                        $clean_val = html_entity_decode($clean_val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                    
                    if (isset($r['setting_key'])) {
                        $u = $pdo->prepare("UPDATE `{$tbl}` SET setting_value = ? WHERE setting_key = ?");
                        $u->execute([$clean_val, $r['setting_key']]);
                    } elseif (isset($r['id'])) {
                        $u = $pdo->prepare("UPDATE `{$tbl}` SET `{$col}` = ? WHERE id = ?");
                        $u->execute([$clean_val, $r['id']]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "Error checking table {$tbl}: " . $e->getMessage() . "\n";
    }
}
echo "Check completed.\n";
