<?php
session_start();
session_destroy();
$hash = $_GET['hash'] ?? '';
header('Location: login.php' . ($hash ? '?hash=' . urlencode($hash) : ''));
exit;
