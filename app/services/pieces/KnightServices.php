<?php
namespace app\services\pieces;
require_once __DIR__ . '/PieceInterface.php';
use app\services\pieces\PieceInterface;

class KnightServices 
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

        // Il cavallo si muove solo a forma di L: 2+1 o 1+2
        $isLShape = ($rowDiff === 2 && $colDiff === 1) || ($rowDiff === 1 && $colDiff === 2);
        if (!$isLShape) return false;

        // La casella di arrivo non deve contenere un pezzo alleato
        $target = $board[$toRank][$toCol];

        if ($target !== null) {
            $isWhiteTarget = ctype_upper($target);
            if ($turn === 'w' && $isWhiteTarget)  return false; // bianco non cattura bianco
            if ($turn === 'b' && !$isWhiteTarget) return false; // nero non cattura nero
        }

        return true;
    }
}