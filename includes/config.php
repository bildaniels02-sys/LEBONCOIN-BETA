<?php
// Database configuration
$dsn = 'mysql:host=127.0.0.1;dbname=projet_annonce;charset=utf8mb4';
$dbUser = 'root';
$dbPass = 'root';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Connexion échouée : ' . $e->getMessage());
}

session_start();

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
