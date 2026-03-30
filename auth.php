<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAuth() {
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
