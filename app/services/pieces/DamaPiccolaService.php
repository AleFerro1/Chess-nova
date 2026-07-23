<?php
namespace app\services\pieces;

class DamaPiccolaService{
    function __construct(){}

    function isValidMove($board, $from, $to, $turn, $piece) {
    if ($piece === null) return false;

    $fromCol  = ord($from[0]) - ord('a');
    $fromRank = 8 - (int)$from[1];
    $toCol    = ord($to[0]) - ord('a');
    $toRank   = 8 - (int)$to[1];

    $rowDir  = $toRank - $fromRank;
    $colDiff = abs($toCol - $fromCol);
    $rowDiff = abs($rowDir);
    $dir     = ($turn === 'w') ? -1 : 1;
    $isKing  = ($piece === 'W' || $piece === 'B');

    if ($board[$toRank][$toCol] !== null) return false;

    // cerca tutte le catture obbligatorie sul tabellone
    $allCaptures = $this->getAllCaptures($board, $turn);

    if (!empty($allCaptures)) {
        // c'è obbligo di cattura — la mossa deve essere una cattura valida
        foreach ($allCaptures as $cap) {
            if ($cap['toRank'] === $toRank && $cap['toCol'] === $toCol
                && $cap['fromRank'] === $fromRank && $cap['fromCol'] === $fromCol) {
                return ['capture' => true, 'captured' => $cap['captured']];
            }
        }
        return false; // mossa non è una cattura obbligatoria
    }

    // nessuna cattura obbligatoria → mossa normale
    if ($rowDiff === 1 && $colDiff === 1) {
        if (!$isKing && $rowDir !== $dir) return false;
        return true;
    }

    return false;
}

// Raccoglie tutte le catture possibili per tutti i pezzi del turno
function getAllCaptures($board, $turn) {
    $all = [];
    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            $p = $board[$r][$c];
            if ($p === null) continue;
            if (strtolower($p) !== $turn) continue;
            $caps = $this->findCaptures($board, $r, $c, $turn, $p);
            foreach ($caps as $cap) {
                $all[] = array_merge($cap, ['fromRank' => $r, 'fromCol' => $c]);
            }
        }
    }
    return $all;
}
    function isValidCapture($board, $fromRank, $fromCol, $toRank, $toCol, $turn, $piece) {
    $rowDir  = $toRank - $fromRank;
    $colDiff = abs($toCol - $fromCol);
    $rowDiff = abs($rowDir);
    $dir     = ($turn === 'w') ? -1 : 1;
    $isKing  = ($piece === 'W' || $piece === 'B');

    if ($rowDiff !== 2 || $colDiff !== 2) return false;
    if ($board[$toRank][$toCol] !== null) return false;
    if (!$isKing && $rowDir !== $dir * 2) return false;

    $midRow = ($fromRank + $toRank) / 2;
    $midCol = ($fromCol  + $toCol)  / 2;
    $mid    = $board[$midRow][$midCol];

    if ($mid === null) return false;
    if (strtoupper($mid) === strtoupper($turn)) return false;

    return true;
}

// Trova tutte le catture disponibili da una posizione (ricorsivo)
function findCaptures($board, $fromRank, $fromCol, $turn, $piece, $captured = []) {
    $results = [];
    $dirs = [[-2,-2],[-2,2],[2,-2],[2,2]];

    foreach ($dirs as [$dr, $dc]) {
        $toRank = $fromRank + $dr;
        $toCol  = $fromCol  + $dc;

        if ($toRank < 0 || $toRank > 7 || $toCol < 0 || $toCol > 7) continue;

        $midRow = ($fromRank + $toRank) / 2;
        $midCol = ($fromCol  + $toCol)  / 2;

        // già mangiato questo pezzo nella catena
        $alreadyCaptured = false;
        foreach ($captured as $c) {
            if ($c[0] === $midRow && $c[1] === $midCol) {
                $alreadyCaptured = true;
                break;
            }
        }
        if ($alreadyCaptured) continue;

        if (!$this->isValidCapture($board, $fromRank, $fromCol, $toRank, $toCol, $turn, $piece)) continue;

        // simula la cattura
        $newBoard = $board;
        $newBoard[$toRank][$toCol]   = $piece;
        $newBoard[$fromRank][$fromCol] = null;
        $newBoard[$midRow][$midCol]  = null;

        $newCaptured = array_merge($captured, [[$midRow, $midCol]]);

        // cerca catture successive
        $further = $this->findCaptures($newBoard, $toRank, $toCol, $turn, $piece, $newCaptured);

        if (empty($further)) {
            // questa è una cattura terminale
            $results[] = ['toRank' => $toRank, 'toCol' => $toCol, 'captured' => $newCaptured];
        } else {
            // aggiunge tutte le catene più lunghe
            $results = array_merge($results, $further);
        }
    }

    return $results;
}
}