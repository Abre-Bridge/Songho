<?php
// join.php — Rejoindre une partie
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/engine.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$code = strtoupper(trim($input['code'] ?? ''));

if (!$code) jsonError('Code manquant');

$games = loadTable('games');
$game = null;
foreach ($games as &$g) {
    if ($g['code'] === $code) { $game = &$g; break; }
}
if (!$game) jsonError('Partie introuvable');
if ($game['status'] !== 'waiting') jsonError('Partie déjà commencée');

$token = generateToken();
$game['status'] = 'active';
saveTable('games', $games);

$players = loadTable('players');
$players[] = ['token' => $token, 'code' => $code, 'playerNumber' => 2, 'lastSeen' => time()];
saveTable('players', $players);

$states = loadTable('states');
$state = null;
foreach ($states as &$s) {
    if ($s['code'] === $code) { $state = &$s; break; }
}

setSessionToken($token);
jsonOut([
    'success' => true, 'gameCode' => $code, 'sessionToken' => $token, 'playerNumber' => 2,
    'gameState' => $state ? $state['state'] : null
]);
