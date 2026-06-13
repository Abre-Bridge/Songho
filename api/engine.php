<?php
// engine.php — Moteur de jeu Songo (port PHP)
declare(strict_types=1);

class SongoGame {
    private const TO_CIRCULAR = [8,9,10,11,12,13,0,7,6,5,4,3,2,1];
    private const TO_BOARD = [6,13,12,11,10,9,8,7,0,1,2,3,4,5];
    private const BOARD_SIZE = 14;
    private const WINNING_SCORE = 40;

    public array $board;
    public int $currentTurn;
    public int $scoreP1;
    public int $scoreP2;
    public bool $gameOver;
    public ?string $winner;
    public bool $solidarityMode;
    public ?array $allowedMoves;
    public int $version;

    public function __construct() {
        $this->board = array_fill(0, 14, 5);
        $this->currentTurn = 1;
        $this->scoreP1 = 0;
        $this->scoreP2 = 0;
        $this->gameOver = false;
        $this->winner = null;
        $this->solidarityMode = false;
        $this->allowedMoves = null;
        $this->version = 0;
    }

    public function boardToCircular(int $i): int { return self::TO_CIRCULAR[$i]; }
    public function circularToBoard(int $c): int { return self::TO_BOARD[$c]; }
    public function nextCircular(int $c): int { return ($c + 1) % self::BOARD_SIZE; }
    public function prevCircular(int $c): int { return ($c - 1 + self::BOARD_SIZE) % self::BOARD_SIZE; }

    public function getCases(int $pn): array {
        return $pn === 1 ? [0,1,2,3,4,5,6] : [7,8,9,10,11,12,13];
    }

    public function getOpponentCases(int $pn): array {
        return $pn === 1 ? [7,8,9,10,11,12,13] : [0,1,2,3,4,5,6];
    }

    public function getCase7Index(int $pn): int { return $pn === 1 ? 6 : 7; }
    public function getCase1Index(int $pn): int { return $pn === 1 ? 13 : 0; }

    public function isOpponentTerritory(int $idx, int $pn): bool {
        return in_array($idx, $this->getOpponentCases($pn));
    }

    public function sow(int $src, int $pn): array {
        $seeds = $this->board[$src];
        $this->board[$src] = 0;
        $circ = $this->boardToCircular($src);
        $skip = $circ;
        $path = [];

        for ($k = 0; $k < $seeds; $k++) {
            $circ = $this->nextCircular($circ);
            if ($circ === $skip) $circ = $this->nextCircular($circ);
            $bid = $this->circularToBoard($circ);
            $this->board[$bid]++;
            $path[] = $bid;
        }

        return ['path' => $path, 'lastIndex' => $this->circularToBoard($circ), 'totalSeeds' => $seeds];
    }

    public function checkCapture(int $lastIdx, int $pn, int $totalSeeds): array {
        $captured = [];
        $indices = [];
        $single = false;

        if ($totalSeeds % self::BOARD_SIZE === 0 && $lastIdx === $this->getCase1Index($pn)) {
            $this->board[$lastIdx]--;
            if ($pn === 1) $this->scoreP1++; else $this->scoreP2++;
            return ['captured' => [1], 'indices' => [$lastIdx], 'singleCapture' => true];
        }

        if ($this->isOpponentTerritory($lastIdx, $pn) && $this->board[$lastIdx] >= 2 && $this->board[$lastIdx] <= 4) {
            $captured[] = $this->board[$lastIdx];
            $indices[] = $lastIdx;
            if ($pn === 1) $this->scoreP1 += $this->board[$lastIdx]; else $this->scoreP2 += $this->board[$lastIdx];
            $this->board[$lastIdx] = 0;

            $pc = $this->prevCircular($this->boardToCircular($lastIdx));
            while ($this->isOpponentTerritory($this->circularToBoard($pc), $pn)) {
                $idx = $this->circularToBoard($pc);
                if ($this->board[$idx] >= 2 && $this->board[$idx] <= 4) {
                    $captured[] = $this->board[$idx];
                    $indices[] = $idx;
                    if ($pn === 1) $this->scoreP1 += $this->board[$idx]; else $this->scoreP2 += $this->board[$idx];
                    $this->board[$idx] = 0;
                    $pc = $this->prevCircular($pc);
                } else break;
            }
        }

        return ['captured' => $captured, 'indices' => $indices, 'singleCapture' => false];
    }

    public function checkCase7Forbidden(int $pn): array {
        $idx = $this->getCase7Index($pn);
        $seeds = $this->board[$idx];
        if ($seeds === 1 || $seeds === 2) {
            $this->board[$idx] = 0;
            if ($pn === 1) $this->scoreP2 += $seeds; else $this->scoreP1 += $seeds;
            return ['forbidden' => true, 'seedsReturned' => $seeds];
        }
        return ['forbidden' => false];
    }

    public function getValidMovesRaw(int $pn): array {
        return array_values(array_filter($this->getCases($pn), fn($i) => $this->board[$i] > 0));
    }

    public function checkSolidarity(int $pn): void {
        $oppSeeds = array_reduce($this->getOpponentCases($pn), fn($s, $i) => $s + $this->board[$i], 0);
        if ($oppSeeds > 0) { $this->solidarityMode = false; $this->allowedMoves = null; return; }

        $this->solidarityMode = true;
        $moves = $this->getValidMovesRaw($pn);
        $maxSeeds = max(array_map(fn($i) => $this->board[$i], $this->getCases($pn)));
        $threshold = min(7, $maxSeeds);
        $this->allowedMoves = array_values(array_filter($moves, fn($i) => $this->board[$i] >= $threshold));
        if (empty($this->allowedMoves)) {
            $this->allowedMoves = array_values(array_filter($moves, fn($i) => $this->board[$i] >= $maxSeeds));
        }
    }

    public function getValidMoves(int $pn): array {
        if ($this->solidarityMode && $this->allowedMoves !== null) return $this->allowedMoves;
        return $this->getValidMovesRaw($pn);
    }

    public function checkGameOver(): void {
        $total = array_sum($this->board);
        if ($total < 10) { $this->gameOver = true; return; }
        if ($this->scoreP1 >= self::WINNING_SCORE) { $this->gameOver = true; $this->winner = 'P1'; return; }
        if ($this->scoreP2 >= self::WINNING_SCORE) { $this->gameOver = true; $this->winner = 'P2'; return; }
        if (empty($this->getValidMovesRaw($this->currentTurn))) { $this->gameOver = true; return; }
    }

    public function playTurn(int $src, int $pn): array {
        if ($this->gameOver) return ['valid' => false, 'message' => 'Partie terminée'];
        if (!in_array($src, $this->getCases($pn))) return ['valid' => false, 'message' => 'Case invalide'];
        if ($this->board[$src] === 0) return ['valid' => false, 'message' => 'Case vide'];
        $validMoves = $this->getValidMoves($pn);
        if (!in_array($src, $validMoves)) return ['valid' => false, 'message' => 'Coup invalide'];

        $forbidden = $this->checkCase7Forbidden($pn);
        if ($forbidden['forbidden']) {
            $this->checkGameOver();
            $this->version++;
            return ['valid' => true, 'type' => 'forbidden', 'seedsReturned' => $forbidden['seedsReturned'],
                    'gameOver' => $this->gameOver, 'board' => $this->board,
                    'scoreP1' => $this->scoreP1, 'scoreP2' => $this->scoreP2,
                    'currentTurn' => $this->currentTurn, 'version' => $this->version];
        }

        $sow = $this->sow($src, $pn);
        $cap = $this->checkCapture($sow['lastIndex'], $pn, $sow['totalSeeds']);
        $this->checkGameOver();
        if (!$this->gameOver) {
            $this->currentTurn = $this->currentTurn === 1 ? 2 : 1;
            $this->checkSolidarity($this->currentTurn);
            $this->checkGameOver();
        }
        $this->version++;

        return [
            'valid' => true, 'type' => 'sow',
            'path' => $sow['path'], 'lastIndex' => $sow['lastIndex'], 'totalSeeds' => $sow['totalSeeds'],
            'captured' => $cap['captured'], 'capturedIndices' => $cap['indices'], 'singleCapture' => $cap['singleCapture'],
            'gameOver' => $this->gameOver, 'board' => $this->board,
            'scoreP1' => $this->scoreP1, 'scoreP2' => $this->scoreP2,
            'currentTurn' => $this->currentTurn, 'version' => $this->version
        ];
    }

    public function toArray(): array {
        return [
            'board' => $this->board, 'currentTurn' => $this->currentTurn,
            'scoreP1' => $this->scoreP1, 'scoreP2' => $this->scoreP2,
            'gameOver' => $this->gameOver, 'winner' => $this->winner,
            'solidarityMode' => $this->solidarityMode, 'version' => $this->version
        ];
    }

    public static function fromArray(array $d): self {
        $g = new self();
        $g->board = $d['board'] ?? array_fill(0, 14, 5);
        $g->currentTurn = $d['currentTurn'] ?? 1;
        $g->scoreP1 = $d['scoreP1'] ?? 0;
        $g->scoreP2 = $d['scoreP2'] ?? 0;
        $g->gameOver = $d['gameOver'] ?? false;
        $g->winner = $d['winner'] ?? null;
        $g->solidarityMode = $d['solidarityMode'] ?? false;
        $g->version = $d['version'] ?? 0;
        return $g;
    }
}
