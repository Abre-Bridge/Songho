<?php
// move.php — Jouer un coup
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/engine.php';

$token = getSessionToken();
if (!$token) jsonError('Non connecté');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$code = $input['gameCode'] ?? '';
$src = (int)($input['sourceIndex'] ?? -1);

if (!$code || $src < 0 || $src > 13) jsonError('Paramètres invalides');

$players = loadTable('players');
$me = null;
foreach ($players as $p) {
    if ($p['token'] === $token) { $me = $p; break; }
}
if (!$me || $me['code'] !== $code) jsonError('Joueur non trouvé');

$states = loadTable('states');
$state = null;
$si = -1;
foreach ($states as $i => $s) {
    if ($s['code'] === $code) { $state = &$s; $si = $i; break; }
}
if (!$state) jsonError('État introuvable');

$game = SongoGame::fromArray($state['state']);
if ($game->currentTurn !== $me['playerNumber']) jsonError('Pas votre tour');

$result = $game->playTurn($src, $me['playerNumber']);
if (!$result['valid']) jsonError($result['message']);

$states[$si]['state'] = $game->toArray();
saveTable('states', $states);

jsonOut(['success' => true, 'moveResult' => $result, 'gameState' => $game->toArray()]);
