<?php
/** HTTP responses that remain available even when the database is down. */
function abort_request(int $status, string $message = ''): never {
    http_response_code($status);
    $titles = [400=>'Invalid request',403=>'Access denied',404=>'Page not found',405=>'Method not allowed',409=>'Request conflict',413=>'File too large',419=>'Session expired',422=>'Check your information',429=>'Too many requests',500=>'Something went wrong',503=>'Service temporarily unavailable'];
    $title = $titles[$status] ?? 'Request could not be completed';
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'error'=>['code'=>$status,'message'=>$message ?: $title]]);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        $base = rtrim(parse_url((string)env('APP_URL','/SarkinMota'), PHP_URL_PATH) ?: '', '/');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($title).'</title><link rel="stylesheet" href="'.htmlspecialchars($base).'/assets/css/app.css"><body class="bg-slate-50 text-slate-900"><main class="max-w-xl mx-auto px-6 py-24"><p class="text-amber-700 font-bold">'. $status .'</p><h1 class="text-3xl font-bold my-4">'.htmlspecialchars($title).'</h1><p class="mb-8">'.htmlspecialchars($message ?: 'Please try again or contact the team if the problem continues.').'</p><a class="underline" href="'.htmlspecialchars($base).'/index.php">Return to home</a></main></body></html>';
    }
    exit;
}
