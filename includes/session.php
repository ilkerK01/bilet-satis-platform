<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM User WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /');
        exit;
    }
}

function login($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

function logout() {
    session_destroy();
    header('Location: /');
    exit;
}

function getUserRole() {
    $user = getCurrentUser();
    return $user ? $user['role'] : 'guest';
}

function getUserCredit() {
    $user = getCurrentUser();
    return $user ? $user['balance'] : 0;
}

function updateUserCredit($userId, $amount) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}
?>
