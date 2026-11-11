<?php
require_once __DIR__ . '/auth.php';
// Revoke this device's trusted-login token FIRST (before the session dies),
// so "Log out" is a real sign-out, not undone by the remember cookie.
if (function_exists('ms_clear_remember_token')) {
    ms_clear_remember_token();
}
session_destroy();
$hash = $_GET['hash'] ?? '';
header('Location: login.php' . ($hash ? '?hash=' . urlencode($hash) : ''));
exit;
