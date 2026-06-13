<?php
// db.php — Stockage JSON simple
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

define('DATA_DIR', __DIR__ . '/data/');
if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

function loadTable($name) {
    $file = DATA_DIR . $name . '.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveTable($name, $data) {
    $file = DATA_DIR . $name . '.json';
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function findWhere($table, $key, $value) {
    foreach ($table as $row) {
        if (isset($row[$key]) && $row[$key] === $value) return $row;
    }
    return null;
}

function updateWhere($table, $key, $value, $updates) {
    foreach ($table as &$row) {
        if (isset($row[$key]) && $row[$key] === $value) {
            $row = array_merge($row, $updates);
            return true;
        }
    }
    return false;
}

function generateCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    return $code;
}

function generateToken() {
    return bin2hex(random_bytes(16));
}

function getSessionToken() {
    return $_COOKIE['songo_token'] ?? null;
}

function setSessionToken($token) {
    setcookie('songo_token', $token, time() + 86400 * 7, '/', '', false, false);
}

function jsonOut($data) {
    echo json_encode($data);
    exit;
}

function jsonError($msg) {
    jsonOut(['success' => false, 'message' => $msg]);
}
