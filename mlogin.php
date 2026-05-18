<?php
// ─────────────────────────────────────────────────────────────────────────
// mlogin.php — Mobile JSON login endpoint (STANDALONE / ADDITIVE)
//
// Why this exists: the iOS app can't use login.php because, on success,
// login.php 302-redirects to the ~1.5 MB index.php. iOS fetch chokes
// following that. This endpoint authenticates against the SAME users table
// and sets the SAME $_SESSION keys as login.php, but returns a tiny JSON
// response with NO redirect and NO HTML.
//
// SAFETY: this file does not include, modify, or affect login.php, api.php,
// index.php, or any existing page. Deleting it would simply make the mobile
// app's login fail — nothing on the website depends on it. The web login
// flow is completely untouched.
// ─────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    // Adopts the PHPSESSID the mobile client supplies (same session the app
    // then uses against api.php). Matches login.php's session behaviour.
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

// Accept a JSON body or form-encoded fields.
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = [];
$username = trim($body['username'] ?? $_POST['username'] ?? '');
$password = $body['password'] ?? $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit;
}

// Same DB credentials as login.php / api.php.
$host = 'localhost';
$db   = 'markewq4_workbook';
$user = 'markewq4_workbook';
$pass = 'MarketFun123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$row = $stmt->fetch();

if (!$row || !password_verify($password, $row['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    exit;
}

// Identical session keys to login.php so api.php's requireAuth() accepts it.
$_SESSION['user_id']      = $row['id'];
$_SESSION['username']     = $row['username'];
$_SESSION['display_name'] = $row['display_name'] ?: $row['username'];
$_SESSION['role']         = $row['role'];

try {
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$row['id']]);
} catch (PDOException $e) {
    // non-fatal — login still succeeds
}

echo json_encode([
    'success' => true,
    'user' => [
        'id'           => (int)$row['id'],
        'username'     => $row['username'],
        'display_name' => $_SESSION['display_name'],
        'role'         => $row['role'],
    ],
]);
