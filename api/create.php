<?php
// create.php — Créer une partie
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/engine.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$visibility = $input['visibility'] ?? 'public';

$code = generateCode();
$token = generateToken();

$game = new SongoGame();
$games = loadTable('games');
$games[] = ['code' => $code, 'status' => 'waiting', 'visibility' => $visibility, 'createdAt' => date('c')];
saveTable('games', $games);

$states = loadTable('states');
$states[] = ['code' => $code, 'state' => $game->toArray()];
saveTable('states', $states);

$players = loadTable('players');
$players[] = ['token' => $token, 'code' => $code, 'playerNumber' => 1, 'lastSeen' => time()];
saveTable('players', $players);

setSessionToken($token);
jsonOut(['success' => true, 'gameCode' => $code, 'sessionToken' => $token, 'playerNumber' => 1]);
