<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Trusted-device "remember me" ────────────────────────────────────────
// Operators (Parker, Jackson, Karen) stay signed in on a device they've
// already logged in on — Trello-style — instead of being bounced to login
// every time the PHP session expires. A long-lived cookie carries a
// selector:validator token; the validator is only ever stored hashed, so a
// leaked DB row can't be replayed, and each token slides its 90-day expiry
// on use.
define('MS_REMEMBER_COOKIE', 'ms_remember');
define('MS_REMEMBER_DAYS', 90);

// DB handle for the auth layer. We reuse the connection the caller already
// built (login.php / api.php set $GLOBALS['ms_auth_pdo']) so credentials live
// in exactly one place and are never duplicated here. When no handle is
// available (e.g. index.php's pre-DB requireAuth), token restore is skipped
// and the request falls back to login.php, which DOES have a handle and runs
// the restore there.
function ms_auth_db() {
    if (isset($GLOBALS['ms_auth_pdo']) && $GLOBALS['ms_auth_pdo'] instanceof PDO) {
        return $GLOBALS['ms_auth_pdo'];
    }
    return null;
}

function ms_auth_tokens_table($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(32) NOT NULL UNIQUE,
        validator_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used_at DATETIME NULL,
        label VARCHAR(255) DEFAULT '',
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ms_remember_cookie_params($expires) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function ms_set_remember_cookie($value, $expires) {
    @setcookie(MS_REMEMBER_COOKIE, $value, ms_remember_cookie_params($expires));
    $_COOKIE[MS_REMEMBER_COOKIE] = $value;
}

// Mint a trusted-device token after a successful password login so this
// browser stays signed in. Called from login.php.
function ms_issue_remember_token($pdo, $userId, $label = '') {
    try {
        ms_auth_tokens_table($pdo);
        $selector  = bin2hex(random_bytes(12));  // 24 chars — indexed lookup key
        $validator = bin2hex(random_bytes(32));   // 64 chars — the secret
        $expTs     = time() + MS_REMEMBER_DAYS * 86400;
        $pdo->prepare("INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at, last_used_at, label) VALUES (?, ?, ?, ?, NOW(), ?)")
            ->execute([$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expTs), substr((string)$label, 0, 250)]);
        ms_set_remember_cookie($selector . ':' . $validator, $expTs);
        return true;
    } catch (Exception $e) { return false; }
}

// Revoke THIS device's token + clear the cookie. Called from logout.php.
function ms_clear_remember_token() {
    $cookie = $_COOKIE[MS_REMEMBER_COOKIE] ?? '';
    if ($cookie !== '' && strpos($cookie, ':') !== false) {
        list($selector) = explode(':', $cookie, 2);
        $pdo = ms_auth_db();
        if ($pdo) { try { $pdo->prepare("DELETE FROM auth_tokens WHERE selector = ?")->execute([$selector]); } catch (Exception $e) {} }
    }
    @setcookie(MS_REMEMBER_COOKIE, '', ms_remember_cookie_params(time() - 3600));
    unset($_COOKIE[MS_REMEMBER_COOKIE]);
}

// No active session but a valid remember token on this device? Restore the
// session so the operator isn't forced to log in again, and slide the expiry.
function ms_try_remember_login() {
    if (!empty($_SESSION['user_id'])) return;
    $cookie = $_COOKIE[MS_REMEMBER_COOKIE] ?? '';
    if ($cookie === '' || strpos($cookie, ':') === false) return;
    list($selector, $validator) = explode(':', $cookie, 2);
    if ($selector === '' || $validator === '') return;
    $pdo = ms_auth_db();
    if (!$pdo) return;
    try {
        ms_auth_tokens_table($pdo);
        $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
        if (!$row) return;
        // Constant-time compare — a wrong validator (forged/expired) is ignored.
        if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) return;
        $u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $u->execute([$row['user_id']]);
        $userRow = $u->fetch();
        if (!$userRow) return;
        $_SESSION['user_id']      = $userRow['id'];
        $_SESSION['username']     = $userRow['username'];
        $_SESSION['display_name'] = $userRow['display_name'] ?: $userRow['username'];
        $_SESSION['role']         = $userRow['role'];
        $expTs = time() + MS_REMEMBER_DAYS * 86400;
        $pdo->prepare("UPDATE auth_tokens SET expires_at = ?, last_used_at = NOW() WHERE id = ?")
            ->execute([date('Y-m-d H:i:s', $expTs), $row['id']]);
        ms_set_remember_cookie($cookie, $expTs); // refresh the cookie's own lifetime
    } catch (Exception $e) { /* fall through to the login redirect */ }
}

function requireAuth() {
    // Before giving up, try to restore the session from a trusted-device token.
    if (empty($_SESSION['user_id'])) {
        ms_try_remember_login();
    }
    if (empty($_SESSION['user_id'])) {
        // API requests get JSON 401
        $isApi = !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Session expired. Please log in.', 'auth_redirect' => true]);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

function getSessionUser() {
    return [
        'id'           => $_SESSION['user_id']      ?? 0,
        'username'     => $_SESSION['username']     ?? '',
        'display_name' => $_SESSION['display_name'] ?? '',
        'role'         => $_SESSION['role']         ?? 'user',
    ];
}
