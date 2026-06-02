<?php
// Database configuration (MySQLi)
$dbHost = '127.0.0.1';
$dbName = 'projet_annonce';
$dbUser = 'root';
$dbPass = 'root';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die('Connexion échouée : ' . $mysqli->connect_error);
}

// Helper: quote and prepare SQL with named parameters (safe for values)
function db_quote($value) {
    global $mysqli;
    if ($value === null) {
        return 'NULL';
    }
    return "'" . $mysqli->real_escape_string((string)$value) . "'";
}

function db_prepare_sql(string $sql, array $params = []): string {
    if (empty($params)) return $sql;
    // Replace :name occurrences with quoted values
    foreach ($params as $key => $val) {
        $placeholder = ':' . $key;
        $sql = str_replace($placeholder, db_quote($val), $sql);
    }
    return $sql;
}

function db_query_all(string $sql, array $params = []) {
    global $mysqli;
    $final = db_prepare_sql($sql, $params);
    $res = $mysqli->query($final);
    if ($res === false) return [];
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    return $rows;
}

function db_query_one(string $sql, array $params = []) {
    global $mysqli;
    $final = db_prepare_sql($sql, $params);
    $res = $mysqli->query($final);
    if ($res === false) return false;
    $row = $res->fetch_assoc();
    $res->free();
    return $row;
}

function db_execute(string $sql, array $params = []) {
    global $mysqli;
    $final = db_prepare_sql($sql, $params);
    return $mysqli->query($final);
}

function db_last_id() {
    global $mysqli;
    return $mysqli->insert_id;
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
