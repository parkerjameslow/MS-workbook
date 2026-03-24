<?php
$secret = 'ms-workbook-deploy-2026';

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected, $signature)) {
    echo shell_exec('cd /home4/markewq4/public_html/workbook && git pull origin claude/recursing-khorana 2>&1');
    http_response_code(200);
} else {
    http_response_code(403);
    echo 'Unauthorized';
}
