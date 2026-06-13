<?php
// heartbeat.php — Keep-alive
require_once __DIR__ . '/db.php';

$token = getSessionToken();
if (!$token) jsonOut(['success' => false]);

$players = loadTable('players');
foreach ($players as &$p) {
    if ($p['token'] === $token) { $p['lastSeen'] = time(); break; }
}
saveTable('players', $players);

jsonOut(['success' => true]);
