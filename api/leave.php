<?php
// leave.php — Quitter une partie
require_once __DIR__ . '/db.php';

$token = getSessionToken();
if (!$token) jsonError('Non connecté');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$code = $input['gameCode'] ?? '';

$players = loadTable('players');
$newPlayers = array_values(array_filter($players, fn($p) => $p['token'] !== $token));
saveTable('players', $newPlayers);

if ($code) {
    $remaining = array_values(array_filter($newPlayers, fn($p) => $p['code'] === $code));
    if (empty($remaining)) {
        $games = loadTable('games');
        $games = array_values(array_filter($games, fn($g) => $g['code'] !== $code));
        saveTable('games', $games);
        $states = loadTable('states');
        $states = array_values(array_filter($states, fn($s) => $s['code'] !== $code));
        saveTable('states', $states);
    }
}

jsonOut(['success' => true]);
