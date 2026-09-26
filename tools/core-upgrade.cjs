const fs = require('node:fs');
function edit(file, fn) { const old=fs.readFileSync(file,'utf8'); const next=fn(old); if(old!==next) fs.writeFileSync(file,next); }
edit('config/app.php',s=>s.replace("!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)","getenv($name) === false && !array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)")
 .replace("env('APP_DEBUG', true)","env('APP_DEBUG', false)")
 .replace(/env\('APP_KEY', '[^']*'\)/,"env('APP_KEY', '')")
 .replace(/env\('PAYSTACK_SECRET_KEY', '[^']*'\)/,"env('PAYSTACK_SECRET_KEY', '')")
 .replace(/env\('FLUTTERWAVE_SECRET_KEY', '[^']*'\)/,"env('FLUTTERWAVE_SECRET_KEY', '')")
 .replace(/env\('API_SECRET_KEY', '[^']*'\)/,"env('API_SECRET_KEY', '')")
 .replace("'currency' => env(","'flutterwave_webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH', ''),\n        'currency' => env("));
edit('includes/functions.php',s=>s.replace("ini_set('session.use_only_cookies', 1);","ini_set('session.use_only_cookies', 1);\n        ini_set('session.use_strict_mode', 1);")
 .replace("if ($data === null) return '';","if (!is_scalar($data)) return '';")
 .replace("!isset($_SESSION['csrf_token']) || empty($token)","!isset($_SESSION['csrf_token']) || !is_string($token) || $token === ''")
 .replace("    start_secure_session();\n    if (empty($_SESSION['csrf_token']))", "    start_secure_session();\n    if (empty($_SESSION['csrf_token']))")
 .replace("        // Re-encode image", "        $dimensions = @getimagesize($file_tmp);\n        if (!$dimensions || $dimensions[0] * $dimensions[1] > 16000000 || !is_uploaded_file($file_tmp)) return $fallback_url;\n\n        // Re-encode image")
 .replace(/        } else \{\s*if \(move_uploaded_file\(\$file_tmp, \$target_filepath\)\) \{\s*return 'uploads\/' \. \$new_filename;\s*}\s*}/,"        }")
 .replace("    // Return untouched if already absolute URL or Data URI", "    if (preg_match('~^(?:javascript|data|vbscript):|[<>\\\"\\x00-\\x1f]~i', $url) || str_contains($url, '..')) $url = $fallback;\n\n    // Return safe absolute URLs")
 .replace(" || strpos($url, 'data:') === 0",'')
 + "\nrequire_once __DIR__ . '/http.php';\nrequire_once __DIR__ . '/request_security.php';\nif (isset($pdo)) enforce_request_security($pdo);\n");
edit('includes/auth_helper.php',s=>s.replace("        session_unset();\n        session_destroy();\n        revoke_current_session();","        revoke_current_session();")
 .replace("WHERE id = ?\");\n            $stmt->execute([$_SESSION['user_id']]);", "WHERE id = ? AND session_token = ?\");\n            $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token'] ?? '']);")
 .replace("    $_SESSION['created_time'] = time();", "    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));\n    $_SESSION['created_time'] = time();")
 .replace("        redirect($redirect_to);\n    }\n}\n\n/**\n * Role specific", "        abort_request(403);\n    }\n}\n\n/**\n * Role specific"));
edit('includes/rbac_helper.php',s=>s.replace('JOIN user_roles ur ON r.id = ur.role_id\n                WHERE ur.user_id = ?', 'JOIN users u ON r.name = u.role\n                WHERE u.id = ?')
 .replace("        redirect($redirect_to);", "        abort_request(403);"));
edit('auth/forgot-password.php',s=>s.replace(/\$reset_link = .*?;/,"$reset_link = rtrim(env('APP_URL', ''), '/') . '/auth/reset-password.php?token=' . $token;"));
edit('auth/reset-password.php',s=>s.replace('(password_reset_token = ? OR password_reset_token = ?)','password_reset_token = ?').replace('[$token_hash, $token]','[$token_hash]')
 .replace('session_token = NULL WHERE id = ?', 'session_token = NULL WHERE id = ? AND password_reset_token = ? AND password_reset_expires > NOW()').replace("[$hash, $valid_user['id']]", "[$hash, $valid_user['id'], $token_hash]")
 .replace("                // Audit Log", "                if ($up->rowCount() !== 1) throw new RuntimeException('Reset link already used.');\n\n                // Audit Log"));
edit('api/theme-preference.php',s=>s.replace("$input = json_decode(file_get_contents('php://input'), true);", "$input = json_decode(file_get_contents('php://input'), true);\nif (!is_array($input) || !is_string($input['theme'] ?? null)) abort_request(422, 'Choose a valid theme.');"));
edit('api/properties.php',s=>s.replace("empty($received_key) ||", "empty($expected_key) || empty($received_key) ||"));
edit('auth/logout.php',s=>s.replace('start_secure_session();',"if ($_SERVER['REQUEST_METHOD'] !== 'POST') {\n    require_once __DIR__ . '/../includes/header.php';\n    echo '<section class=\"max-w-md mx-auto py-24 px-6\"><h1 class=\"text-2xl font-bold mb-6\">Sign out of your account?</h1><form method=\"post\"><input type=\"hidden\" name=\"csrf_token\" value=\"'.generate_csrf_token().'\"><button class=\"bg-amber-500 rounded-lg px-6 py-3\">Sign out</button></form></section>';\n    require_once __DIR__ . '/../includes/footer.php';\n    exit;\n}\nstart_secure_session();"));
for (const dir of ['auth','admin','hr']) for (const name of fs.readdirSync(dir).filter(n=>n.endsWith('.php'))) edit(dir+'/'+name,s=>s
 .replace(/if \(session_status\(\) === PHP_SESSION_NONE\) \{\s*session_start\(\);\s*}\s*/g,'')
 .replace(/(\$errors\[\] = "[^"]*)" \. \$e->getMessage\(\);/g,'$1 Please try again or contact support.";')
 .replace('<?php echo $error; ?>','<?php echo htmlspecialchars($error); ?>'));
edit('download.php',s=>s.replace(/        if \(!file_exists\(\$filepath\)\) \{[\s\S]*?\n        }/,'')
 .replace("header('Cache-Control: must-revalidate');", "header('Cache-Control: private, no-store');").replace("header('Pragma: public');", "header('X-Content-Type-Options: nosniff');"));
edit('admin/manage-properties.php',s=>s.replace("if (isset($_POST['update_property'])) {", "if (isset($_POST['update_property'])) {\n            require_permission('properties.edit');"));
edit('auth/landlord-dashboard.php',s=>s.replace("$_POST['type'] ?? 'residential'","$_POST['type'] ?? 'rent'").replace("$_POST['category'] ?? 'rent'","$_POST['category'] ?? 'residential'")
 .replace('WHERE landlord_id = ? OR agent_name = ?', 'WHERE landlord_id = ?').replace("[$user['id'], $user['name']]", "[$user['id']]")
 .replace('type, category, status, image_url', 'type, category, listing_status, image_url').replace("?, ?, ?, 'active', ?, ?, ?)", "?, ?, ?, 'pending_review', ?, ?, ?)"));
// Extract schema definitions only. Never use user rows or credentials from the legacy dump.
const dump=fs.readFileSync('database/sarkinmotahq_db_dump.sql','utf8');
const ddl=[...dump.matchAll(/CREATE TABLE `[\s\S]*?;(?=\r?\n)/g)].map(m=>m[0].replace(/ AUTO_INCREMENT=\d+/g,''));
fs.writeFileSync('database/schema.sql','-- Clean schema only; no accounts, credentials or customer data.\nSET FOREIGN_KEY_CHECKS=0;\n'+ddl.join('\n\n')+'\nSET FOREIGN_KEY_CHECKS=1;\n');
edit('database/migrate.php',s=>s.replace(/    \/\/ 1\. Execute SQL Migration v2[\s\S]*?    \$pdo->beginTransaction\(\);/,`    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    if (!$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn()) {
        $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
        foreach (['01_schema_v2.sql','02_system_settings.sql','03_hr_onboarding.sql','04_team_members.sql'] as $version) {
            if ($version === '02_system_settings.sql') $pdo->exec(file_get_contents(__DIR__.'/migrations/'.$version));
            $pdo->prepare('INSERT IGNORE INTO schema_migrations (version) VALUES (?)')->execute([$version]);
        }
    }
    foreach (glob(__DIR__.'/migrations/*.sql') as $file) {
        $check=$pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = ?');
        $check->execute([basename($file)]);
        if ($check->fetchColumn()) continue;
        $pdo->exec(file_get_contents($file));
        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([basename($file)]);
        echo '[OK] '.basename($file)."\\n";
    }
    $pdo->beginTransaction();`)
 .replace(/    \/\/ Seed or update HR Manager demo user[\s\S]*?    \$users =/, '    $users =')
 .replace(/    \/\/ 7\. Backfill Landlords[\s\S]*?    if \(\$pdo->inTransaction\(\)\)/, '    if ($pdo->inTransaction())')
 .replace('User Roles backfilled & HR Manager account seeded.', 'User roles backfilled; no accounts or demo records created.'));
console.log('Shared security and schema upgrades applied.');
