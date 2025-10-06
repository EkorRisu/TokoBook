<?php
// Simple CSRF helper
if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token_time'] = time();
    }
    return $_SESSION['_csrf_token'];
}

function csrf_input_field() {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

function csrf_validate($token) {
    if (empty($_SESSION['_csrf_token'])) return false;
    $valid = hash_equals($_SESSION['_csrf_token'], (string)$token);
    // Optional: expire token after 1 hour
    if (!empty($_SESSION['_csrf_token_time']) && (time() - $_SESSION['_csrf_token_time'] > 3600)) {
        unset($_SESSION['_csrf_token']);
        unset($_SESSION['_csrf_token_time']);
        return false;
    }
    return $valid;
}

?>
