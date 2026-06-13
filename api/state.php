<?php
// state.php — Polling état du jeu
require_once __DIR__ . '/db.php';

$token = getSessionToken();
if (!$token) { jsonOut(['success' => false, 'message' => 'Non connecté']); exit; }

$code = $_GET['code'] ?? '';
$since = (int)($_GET['since'] ?? 0);

if (!$code) jsonError('Code manquant');

$players = loadTable('players');
$me = null;
foreach ($players as $p) {
    if ($p['token'] === $token) { $me = $p; break; }
}
if (!$me || $me['code'] !== $code) jsonError('Joueur non trouvé');

$states = loadTable('states');
$state = null;
foreach ($states as $s) {
    if ($s['code'] === $code) { $state = $s; break; }
}
if (!$state) jsonError('État introuvable');

$gameState = $state['state'];
$modified = ($gameState['version'] ?? 0) > $since;

$opponent = null;
foreach ($players as $p) {
    if ($p['code'] === $code && $p['token'] !== $token) {
        $opponent = ['connected' => (time() - ($p['lastSeen'] ?? 0)) < 15];
    }
}

jsonOut([
    'success' => true,
    'modified' => $modified,
    'gameState' => $modified ? $gameState : null,
    'opponent' => $opponent,
    'playerNumber' => $me['playerNumber']
]);
