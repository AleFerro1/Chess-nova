<?php
namespace app\services\pieces;
require_once __DIR__ . '/PieceInterface.php';
use app\services\pieces\PieceInterface;

class BishopServices 
{
    public function isValidMove($board, $from, $to, $turn, $piece): bool
    {
        if ($piece === null) return false;

        $fromCol  = ord($from[0]) - ord('a');
        $fromRank = 8 - (int)$from[1];

        $toCol  = ord($to[0]) - ord('a');
        $toRank = 8 - (int)$to[1];

        $rowDiff = abs($toRank - $fromRank);
        $colDiff = abs($toCol  - $fromCol);

        // L'alfiere si muove solo in diagonale
        if ($rowDiff !== $colDiff) return false;

        // Direzione del movimento (+1 o -1)
        $stepRow = ($toRank > $fromRank) ? 1 : -1;
        $stepCol = ($toCol  > $fromCol)  ? 1 : -1;

        // Controlla le caselle intermedie (escluse partenza e arrivo)
        $row = $fromRank + $stepRow;
        $col = $fromCol  + $stepCol;

        while ($row !== $toRank && $col !== $toCol) {
            if ($board[$row][$col] !== null) return false;
            $row += $stepRow;
            $col += $stepCol;
        }

        // La casella di arrivo non deve contenere un pezzo alleato
        $target = $board[$toRank][$toCol];
        if ($target !== null) {
            $isWhiteTarget = ctype_upper($target);
            if ($turn === 'w' && $isWhiteTarget)  return false;
            if ($turn === 'b' && !$isWhiteTarget) return false;
        }

        return true;
    }
}