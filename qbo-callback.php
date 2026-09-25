<?php
// QuickBooks Online OAuth 2.0 redirect target.
// Intuit sends the operator's browser here with ?code=&state=&realmId=
// after they authorize. We validate the CSRF state, exchange the code for
// tokens, store the single company connection, then bounce back into the app.
// Standalone (does NOT include api.php) — reads the QBO_* keys from
// api.local.php, same as the rest of the app's secrets.
session_start();

require_once __DIR__ . '/auth.php';
requireAuth(); // only a logged-in operator can complete the connect

$DB_HOST = 'localhost';
$DB_NAME = 'markewq4_workbook';
$DB_USER = 'markewq4_workbook';
$DB_PASS = 'MarketFun123';

// Pull the QBO app credentials (defined in api.local.php).
if (file_exists(__DIR__ . '/api.local.php')) require_once __DIR__ . '/api.local.php';
$CLIENT_ID     = defined('QBO_CLIENT_ID')     ? QBO_CLIENT_ID     : (getenv('QBO_CLIENT_ID') ?: '');
$CLIENT_SECRET = defined('QBO_CLIENT_SECRET') ? QBO_CLIENT_SECRET : (getenv('QBO_CLIENT_SECRET') ?: '');
$ENVIRONMENT   = defined('QBO_ENVIRONMENT')   ? QBO_ENVIRONMENT   : (getenv('QBO_ENVIRONMENT') ?: 'production');
$API_BASE      = $ENVIRONMENT === 'sandbox' ? 'https://sandbox-quickbooks.api.intuit.com' : 'https://quickbooks.api.intuit.com';
$TOKEN_URL     = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'wb.marketsculpt.com';
$baseUrl  = defined('PUBLIC_BASE_URL') ? PUBLIC_BASE_URL : "$scheme://$host";
$REDIRECT = $baseUrl . '/qbo-callback.php';

function qbo_done(string $status, string $msg = ''): void {
    // Bounce back to the app's Billings view with a status flag.
    $q = 'qbo=' . urlencode($status) . ($msg !== '' ? '&qbo_msg=' . urlencode($msg) : '');
    header('Location: /index.php#/billings?' . $q);
    echo '<!doctype html><meta charset="utf-8"><p style="font-family:sans-serif;padding:40px;">'
       . htmlspecialchars($status === 'connected' ? 'QuickBooks connected. Returning…' : ('QuickBooks: ' . $status . ' ' . $msg))
       . '</p><script>location.replace("/index.php#/billings?' . htmlspecialchars($q) . '");</script>';
    exit;
}

// Intuit can send ?error= when the user declines.
if (!empty($_GET['error'])) qbo_done('error', (string)$_GET['error']);

$code    = $_GET['code']    ?? '';
$state   = $_GET['state']   ?? '';
$realmId = $_GET['realmId'] ?? '';
if ($code === '' || $realmId === '') qbo_done('error', 'Missing code or realmId');

// CSRF: state must match the one we stashed in qbo_connect.
if (empty($_SESSION['qbo_oauth_state']) || !hash_equals((string)$_SESSION['qbo_oauth_state'], (string)$state)) {
    qbo_done('error', 'State mismatch — please try connecting again');
}
unset($_SESSION['qbo_oauth_state']);

if ($CLIENT_ID === '' || $CLIENT_SECRET === '') qbo_done('error', 'QuickBooks not configured');

// Exchange the authorization code for tokens.
$ch = curl_init($TOKEN_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'grant_type'   => 'authorization_code',
        'code'         => $code,
        'redirect_uri' => $REDIRECT,
    ]),
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode($CLIENT_ID . ':' . $CLIENT_SECRET),
    ],
    CURLOPT_TIMEOUT => 25,
]);
$resp = curl_exec($ch);
curl_close($ch);
$tok = $resp ? json_decode($resp, true) : null;
if (!$tok || empty($tok['access_token'])) {
    qbo_done('error', 'Token exchange failed');
}

// Best-effort: fetch the company name for display.
$companyName = '';
$ci = curl_init("$API_BASE/v3/company/" . rawurlencode($realmId) . '/companyinfo/' . rawurlencode($realmId) . '?minorversion=73');
curl_setopt_array($ci, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Authorization: Bearer ' . $tok['access_token']],
    CURLOPT_TIMEOUT        => 20,
]);
$ciResp = curl_exec($ci);
curl_close($ci);
if ($ciResp) {
    $ciData = json_decode($ciResp, true);
    $companyName = $ciData['CompanyInfo']['CompanyName'] ?? '';
}

// Store the single connection row.
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS qbo_connection (
        id TINYINT NOT NULL PRIMARY KEY DEFAULT 1,
        realm_id VARCHAR(64) NOT NULL,
        access_token TEXT NOT NULL,
        refresh_token TEXT NOT NULL,
        expires_at INT NOT NULL DEFAULT 0,
        company_name VARCHAR(255) NOT NULL DEFAULT '',
        connected_by VARCHAR(255) NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $expiresAt = time() + (int)($tok['expires_in'] ?? 3600) - 60;
    $who = $_SESSION['display_name'] ?? ($_SESSION['username'] ?? '');
    $pdo->prepare("INSERT INTO qbo_connection (id, realm_id, access_token, refresh_token, expires_at, company_name, connected_by)
        VALUES (1, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE realm_id=VALUES(realm_id), access_token=VALUES(access_token),
          refresh_token=VALUES(refresh_token), expires_at=VALUES(expires_at),
          company_name=IF(VALUES(company_name)<>'', VALUES(company_name), company_name),
          connected_by=VALUES(connected_by)")
        ->execute([$realmId, $tok['access_token'], $tok['refresh_token'] ?? '', $expiresAt, $companyName, $who]);
} catch (PDOException $e) {
    qbo_done('error', 'Could not store connection');
}

qbo_done('connected');
